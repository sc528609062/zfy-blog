<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\CardCode;
use App\Models\Category;
use App\Models\Content;
use App\Models\LinkSubmission;
use App\Models\Order;
use App\Models\PointsStoreItem;
use App\Models\PrivateMessage;
use App\Models\Product;
use App\Models\Tag;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserRequest;
use App\Models\VipLevel;
use App\Services\CartService;
use App\Services\CommentService;
use App\Services\CommerceOperations;
use App\Services\ContentMarkdownRenderer;
use App\Services\DemoContentRepository;
use App\Services\DownloadService;
use App\Services\OrderCancellation;
use App\Services\OrderService;
use App\Services\Payment\PaymentManager;
use App\Services\PointsStoreService;
use App\Services\ProductOrderService;
use App\Services\SiteSettings;
use App\Services\ThemeManager;
use App\Services\ThemePackageLoader;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SiteController extends Controller
{
    public function __construct(
        private readonly ThemeManager $themes,
        private readonly DemoContentRepository $repository,
        private readonly ContentMarkdownRenderer $renderer,
    ) {}

    public function home()
    {
        return $this->render('home');
    }

    public function channel(string $type)
    {
        return $this->render($type.'-channel', match ($type) {
            'posts' => 'post',
            'images' => 'images',
            'files' => 'files',
            default => null,
        });
    }

    public function category(Category $category)
    {
        return $this->render('category-page', null, ['currentCategory' => $category], ['category_id' => $category->id]);
    }

    public function tag(Tag $tag)
    {
        return $this->render('tag-page', null, ['currentTag' => $tag], ['tag_id' => $tag->id]);
    }

    public function topic(Topic $topic)
    {
        abort_unless($topic->status === 'published', 404);

        return $this->render('topic-page', null, ['currentTopic' => $topic], ['topic_id' => $topic->id]);
    }

    public function content(string $slug)
    {
        $content = $this->repository->findContent($slug);
        abort_unless($content, 404);
        if (request()->routeIs('contents.legacy') && app(SiteSettings::class)->get('permalink.content_base', 'content') !== 'content') {
            return redirect()->route('contents.show', ['slug' => $slug], 301);
        }
        $this->renderFrontContent($content);
        $page = match ($content->type) {
            'files' => 'file-detail',
            'images' => 'images-detail',
            'page' => 'page-detail',
            default => 'content-detail',
        };

        return $this->render($page, null, $this->detailData($content));
    }

    public function permalink(string $contentBase, string $slug)
    {
        abort_unless($contentBase === app(SiteSettings::class)->get('permalink.content_base', 'content'), 404);

        return $this->content($slug);
    }

    public function page(string $slug)
    {
        $content = Content::published()->where('type', 'page')->where('slug', $slug)->firstOrFail();
        $this->renderFrontContent($content);

        return $this->render('page-detail', null, $this->detailData($content));
    }

    public function generic(string $page)
    {
        if ($page === 'cart') {
            return $this->render('shop', null, ['cartMode' => true, 'cartItems' => app(CartService::class)->items(auth()->user()), 'addresses' => DB::table('user_addresses')->where('user_id', auth()->id())->orderByDesc('is_default')->get()]);
        }
        if ($page === 'shop') {
            return $this->render($page, null, ['products' => Product::with('variants')->where('status', 'published')->latest()->paginate(20)]);
        }
        if ($page === 'points-store') {
            return $this->render($page, null, ['storeItems' => PointsStoreItem::where('status', 'active')->latest()->paginate(20)]);
        }
        if ((str_starts_with($page, 'user-') || $page === 'author-workspace') && ! auth()->check()) {
            return redirect()->guest(route('login'));
        }
        if (str_starts_with($page, 'user-') || $page === 'author-workspace') {
            $user = auth()->user()->load(['wallet', 'pointsAccount', 'vip.level']);
            $editingContent = null;
            if ($page === 'user-editor') {
                abort_unless($user->canSubmitContent(), 403);
                if (request()->filled('content')) {
                    $editingContent = $user->contents()->where('type', '!=', 'page')->findOrFail(request()->integer('content'));
                    $submitted = $editingContent->revisions()->where('user_id', $user->id)->whereIn('kind', ['pending', 'author_draft'])->latest('updated_at')->first();
                    if ($submitted) {
                        $editingContent->fill(Arr::only($submitted->snapshot, ['title', 'type', 'category_id', 'excerpt', 'markdown_cache']));
                    }
                }
            }

            return $this->render($page, null, [
                'accountUser' => $user,
                'privateMessages' => $page === 'user-messages' ? PrivateMessage::with(['sender:id,name', 'recipient:id,name'])->where(fn ($query) => $query->where('sender_id', $user->id)->orWhere(fn ($query) => $query->where('recipient_id', $user->id)->where('status', 'sent')))->latest('id')->paginate(20) : null,
                'accountSessions' => config('session.driver') === 'database' ? DB::table('sessions')->where('user_id', $user->id)->orderByDesc('last_activity')->get(['id', 'ip_address', 'user_agent', 'last_activity']) : collect(),
                'editingContent' => $editingContent,
                'editorCategories' => $page === 'user-editor' ? Category::where('type', '!=', 'page')->orderBy('sort_order')->get() : collect(),
                'checkedInToday' => DB::table('daily_checkins')->where('user_id', $user->id)->where('date', today()->toDateString())->exists(),
                'accountOrders' => Order::with(['items', 'refunds'])->where('user_id', $user->id)->latest()->paginate(20, ['*'], 'orders_page'),
                'accountDownloads' => DB::table('download_logs')->leftJoin('contents', 'contents.id', '=', 'download_logs.content_id')->where('download_logs.user_id', $user->id)->select('download_logs.*', 'contents.title')->orderByDesc('download_logs.id')->paginate(20, ['*'], 'downloads_page'),
                'walletTransactions' => DB::table('wallet_transactions')->where('wallet_id', $user->wallet?->id)->latest()->paginate(20, ['*'], 'wallet_page'),
                'pointsTransactions' => DB::table('points_transactions')->where('points_account_id', $user->pointsAccount?->id)->latest()->paginate(20, ['*'], 'points_page'),
                'authorContents' => $user->contents()->latest()->paginate(20, ['*'], 'contents_page'),
                'withdrawals' => DB::table('author_withdrawals')->where('author_id', $user->id)->latest()->get(),
                'authorEarnings' => DB::table('author_earnings')->where('author_id', $user->id)->latest()->paginate(20, ['*'], 'earnings_page'),
                'deliveredCards' => CardCode::whereIn('order_item_id', DB::table('order_items')->join('orders', 'orders.id', '=', 'order_items.order_id')->where('orders.user_id', $user->id)->where('orders.status', 'paid')->select('order_items.id'))->get()->groupBy('order_item_id'),
                'accountNotifications' => DB::table('notifications')->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $user->id))->latest()->take(30)->get(),
                'readNotifications' => DB::table('notification_reads')->where('user_id', $user->id)->pluck('notification_id')->all(),
                'accountRequests' => UserRequest::where('user_id', $user->id)->latest()->take(30)->get(),
                'favorites' => Content::published()->whereIn('id', DB::table('content_reactions')->where('user_id', $user->id)->where('type', 'favorite')->select('content_id'))->get(),
            ]);
        }
        if ($page === 'author-profile') {
            $author = User::where('username', request()->route('username'))->where('is_author', true)->where('author_status', 'approved')->firstOrFail();

            return $this->render($page, null, ['currentAuthor' => $author], ['author_id' => $author->id]);
        }

        return $this->render($page);
    }

    public function storeComment(Request $request, Content $content, CommentService $comments)
    {
        $comment = $comments->store($request, $content);

        return back()->with('status', $comment->status === 'pending' ? '评论已提交，等待审核。' : '评论已发表。');
    }

    public function buyVip(Request $request, VipLevel $level, OrderService $orders)
    {
        $data = $request->validate(['period' => ['required', 'in:monthly,quarterly,yearly,lifetime'], 'gateway' => ['required', 'in:balance,points,epay,alipay_official,wechat_official,hupijiao_v3']]);
        $order = $orders->createForVip($level, $request->user(), $data['period'], $data['gateway']);
        if (! in_array($data['gateway'], ['balance', 'points'], true)) {
            return $this->checkoutExternal($order);
        }
        $data['gateway'] === 'balance' ? $orders->payWithBalance($order, $request->user()) : $orders->payWithPoints($order, $request->user());

        return redirect('/user/vip')->with('status', '会员已开通。');
    }

    public function payOrder(Request $request, Order $order, OrderService $orders)
    {
        $data = $request->validate(['gateway' => ['required', 'in:balance,points,epay,alipay_official,wechat_official,hupijiao_v3'], 'points' => ['nullable', 'integer', 'between:0,100000000']]);
        if (! in_array($data['gateway'], ['balance', 'points'], true)) {
            abort_unless($order->user_id === $request->user()->id, 403);
            abort_unless($order->pay_channel === $data['gateway'], 422, '请使用订单原支付渠道，或取消后重新下单。');

            return $this->checkoutExternal($order);
        }
        $data['gateway'] === 'balance' ? $orders->payWithBalance($order, $request->user(), (int) ($data['points'] ?? 0)) : $orders->payWithPoints($order, $request->user());

        return back()->with('status', '订单已支付。');
    }

    public function cancelOrder(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        $changed = app(OrderCancellation::class)->cancel($order);

        return back()->with('status', $changed ? '订单已取消。' : '订单状态已更新。');
    }

    public function download(Request $request, Content $content, DownloadService $downloads)
    {
        return redirect()->to($downloads->authorize($request, $content)['download_url']);
    }

    public function downloadFile(Request $request, Attachment $attachment, DownloadService $downloads)
    {
        return $downloads->file($request, $attachment);
    }

    public function exchange(Request $request, PointsStoreItem $item, PointsStoreService $store)
    {
        $data = $request->validate(['request_id' => ['required', 'uuid']]);
        $store->exchange($request->user(), $item, $data['request_id']);

        return back()->with('status', '兑换成功，等待发放。');
    }

    public function submitLink(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'url' => ['required', 'url:http,https', 'max:500'], 'description' => ['nullable', 'string', 'max:2000']]);
        LinkSubmission::create([...$data, 'user_id' => $request->user()->id, 'status' => 'pending', 'ip_address' => $request->ip()]);

        return back()->with('status', '链接已提交审核。');
    }

    public function withdraw(Request $request, CommerceOperations $operations)
    {
        $data = $request->validate(['amount' => ['required', 'numeric', 'min:1', 'max:999999.99', 'decimal:0,2'], 'method' => ['required', 'in:alipay,wechat,bank'], 'account' => ['required', 'string', 'max:500']]);
        $operations->requestWithdrawal($request->user(), number_format($data['amount'], 2, '.', ''), $data['method'], $data['account']);

        return back()->with('status', '提现申请已提交。');
    }

    public function refund(Request $request, Order $order, CommerceOperations $operations)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:255'], 'amount' => ['nullable', 'numeric', 'min:0.01', 'decimal:0,2']]);
        $operations->requestRefund($request->user(), $order, $data['reason'], isset($data['amount']) ? (string) $data['amount'] : null);

        return back()->with('status', '退款申请已提交。');
    }

    public function buyProduct(Request $request, Product $product, ProductOrderService $products, OrderService $orders)
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'between:1,100'], 'variant_id' => ['nullable', 'integer'], 'gateway' => ['required', 'in:balance,points,epay,alipay_official,wechat_official,hupijiao_v3'], 'coupon' => ['nullable', 'string', 'max:80'], 'address' => ['nullable', 'string', 'max:1000']]);
        $order = $products->create($product, $request->user(), $data);
        if (! in_array($data['gateway'], ['balance', 'points'], true)) {
            return $this->checkoutExternal($order);
        }
        $data['gateway'] === 'balance' ? $orders->payWithBalance($order, $request->user()) : $orders->payWithPoints($order, $request->user());

        return redirect('/user/orders')->with('status', '商品已购买。');
    }

    public function buy(Request $request, string $slug, OrderService $orders, PaymentManager $payments)
    {
        $data = $request->validate(['gateway' => ['required', 'in:alipay_official,wechat_official,hupijiao_v3,epay,balance,points']]);
        $content = Content::published()->where('slug', $slug)->firstOrFail();
        $order = $orders->createForContent($content, $request->user(), $data['gateway']);
        if ($data['gateway'] === 'balance') {
            $order = $orders->payWithBalance($order, $request->user());
        } elseif ($data['gateway'] === 'points') {
            $order = $orders->payWithPoints($order, $request->user());
        }
        $payment = $order->status !== 'paid' ? $payments->createPayment($order, $order->pay_channel) : null;

        return view('payments.checkout', [
            'order' => $order->load('items'),
            'payment' => $payment,
            'theme' => $this->themes->active(),
        ]);
    }

    public function recharge(Request $request, OrderService $orders)
    {
        $data = $request->validate(['amount' => ['required', 'numeric', 'decimal:0,2', 'between:1,10000'], 'gateway' => ['sometimes', 'in:epay,alipay_official,wechat_official,hupijiao_v3']]);

        return $this->checkoutExternal($orders->createRecharge($request->user(), (string) $data['amount'], $data['gateway'] ?? 'epay'));
    }

    private function checkoutExternal(Order $order)
    {
        abort_unless($order->status === 'pending' && ! $order->expires_at?->isPast(), 422, '订单已关闭或过期。');
        $payment = app(PaymentManager::class)->createPayment($order, $order->pay_channel);

        return view('payments.checkout', ['order' => $order->fresh('items'), 'payment' => $payment, 'theme' => $this->themes->active()]);
    }

    private function render(string $page, ?string $type = null, array $extra = [], array $filters = [])
    {
        $theme = $this->themes->active();

        $data = array_merge(
            $this->repository->pageData($page, $type, $filters),
            $extra,
            ['theme' => $theme]
        );
        $data = zfy_apply_strict('zfy_page_data', $data, $page, $theme);
        $content = $extra['content'] ?? null;
        $candidates = $content ? ['single-'.$content->type.'-'.$content->slug, 'single-'.$content->type, 'single'] : [$page, $type ? 'archive-'.$type : 'archive'];
        $view = app(ThemePackageLoader::class)->template($theme['slug'], $candidates) ?? $theme['view'];
        $view = zfy_apply('zfy_template', $view, $page, $theme);

        return view($view, $data);
    }

    private function renderFrontContent(Content $content): void
    {
        if (app(OrderService::class)->userCanAccessContent($content, auth()->user())) {
            $this->renderer->renderContent($content, false, true);
        } else {
            $content->rendered_html = '<p>'.e($content->excerpt ?: '购买后可查看完整内容。').'</p>';
        }
    }

    private function detailData(Content $content): array
    {
        return [
            'content' => $content,
            'canAccess' => app(OrderService::class)->userCanAccessContent($content, auth()->user()),
            'comments' => $content->comments()->where('status', 'approved')->whereNull('parent_id')->with(['user', 'replies.user', 'replies.parent.user'])->latest()->take(100)->get(),
            'reactions' => auth()->check() ? DB::table('content_reactions')->where('content_id', $content->id)->where('user_id', auth()->id())->pluck('type')->all() : [],
        ];
    }
}
