<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Content;
use App\Models\Media;
use App\Models\Order;
use App\Models\PageLayout;
use App\Models\Plugin;
use App\Models\PointsStoreItem;
use App\Models\Product;
use App\Models\Tag;
use App\Models\Theme;
use App\Models\User;
use App\Models\VipLevel;
use App\Services\CommentService;
use App\Services\CommerceOperations;
use App\Services\ContentMarkdownRenderer;
use App\Services\DemoContentRepository;
use App\Services\DownloadService;
use App\Services\GalleryService;
use App\Services\NeteaseMusicService;
use App\Services\OrderCancellation;
use App\Services\OrderService;
use App\Services\Payment\PaymentManager;
use App\Services\PointsStoreService;
use App\Services\SystemHealthService;
use App\Services\ThemeManager;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PlatformController extends Controller
{
    public function sessions(Request $request)
    {
        abort_unless(config('session.driver') === 'database', 422, '当前会话驱动不支持设备管理。');

        return $this->ok(DB::table('sessions')->where('user_id', $request->user()->id)->latest('last_activity')->get(['id', 'ip_address', 'user_agent', 'last_activity']));
    }

    public function exchange(Request $request, PointsStoreItem $item, PointsStoreService $store)
    {
        $data = $request->validate(['request_id' => ['required', 'uuid']]);

        return $this->ok($store->exchange($request->user(), $item, $data['request_id']));
    }

    public function token(Request $request)
    {
        $data = $request->validate([
            'login' => ['nullable', 'string', 'max:160', 'required_without:email'],
            'email' => ['nullable', 'string', 'max:160', 'required_without:login'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:80'],
            'abilities' => ['sometimes', 'array', 'min:1', 'max:5'],
            'abilities.*' => ['string', 'distinct', 'in:read,orders,download,comment,profile'],
            'expires_in' => ['sometimes', 'integer', 'min:300', 'max:2592000'],
        ]);

        $login = (string) ($data['login'] ?? $data['email'] ?? '');
        $user = User::findForLogin($login);

        if (! $user || $user->is_banned || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['login' => '账号或密码不正确。']);
        }

        $expiresAt = now()->addSeconds($data['expires_in'] ?? 86400);
        $abilities = $data['abilities'] ?? ['read', 'orders', 'download', 'comment', 'profile'];

        return $this->ok([
            'token_type' => 'Bearer',
            'access_token' => $user->createToken($data['device_name'] ?? 'zfy-blog-api', $abilities, $expiresAt)->plainTextToken,
            'expires_at' => $expiresAt->toIso8601String(),
            'abilities' => $abilities,
            'user' => $user->only(['id', 'name', 'email', 'username', 'avatar_url']),
        ]);
    }

    public function tokens(Request $request)
    {
        return $this->ok($request->user()->tokens()->get(['id', 'name', 'abilities', 'last_used_at', 'expires_at', 'created_at']));
    }

    public function revokeToken(Request $request, int $token)
    {
        $request->user()->tokens()->whereKey($token)->firstOrFail()->delete();

        return $this->ok(null);
    }

    public function home(DemoContentRepository $repository, ThemeManager $themes)
    {
        $theme = $themes->active();
        $data = $repository->pageData();

        return $this->ok([
            'theme' => Arr::only($theme, ['slug', 'name', 'version', 'accent']),
            'data' => Arr::only($data, ['page', 'contents', 'pagination', 'featured', 'resources', 'posts', 'images', 'categories', 'rankings', 'siteName', 'stats']),
        ]);
    }

    public function contents(Request $request)
    {
        $contents = Content::query()
            ->with(['author:id,name,username,avatar_url', 'category:id,name,slug'])
            ->when($request->type, fn ($query, $type) => $query->where('type', $type))
            ->when($request->q, fn ($query, $q) => $query->matchingPublicText((string) $q))
            ->published()
            ->latest('published_at')
            ->paginate(max(1, min(100, $request->integer('per_page', 12))));

        return $this->ok($contents);
    }

    public function content(Request $request, string $slug, OrderService $orders, ContentMarkdownRenderer $renderer)
    {
        $content = Content::with(['author:id,name,username,avatar_url', 'category', 'tags'])->published()->where('slug', $slug)->firstOrFail();
        $allowed = $orders->userCanAccessContent($content, $request->user('sanctum'));
        if ($allowed) {
            $renderer->renderContent($content, false, true);
            $content->makeVisible('rendered_html');
        }

        return $this->ok([...$content->toArray(), 'can_access' => $allowed, 'gallery' => $allowed ? app(GalleryService::class)->items($content, $request->user('sanctum')) : []]);
    }

    public function taxonomy()
    {
        return $this->ok([
            'categories' => Category::orderBy('sort_order')->get(),
            'tags' => Tag::latest()->take(50)->get(),
        ]);
    }

    public function categories()
    {
        return $this->ok(Category::withCount(['contents' => fn ($query) => $query->published()])->orderBy('sort_order')->paginate(30));
    }

    public function tags()
    {
        return $this->ok(Tag::withCount(['contents' => fn ($query) => $query->published()])->latest()->paginate(50));
    }

    public function media(Request $request)
    {
        abort_unless($request->user('sanctum')?->can('manage contents'), 403);

        return $this->ok(Media::query()
            ->when($request->type, fn ($query, $type) => $query->where('type', $type))
            ->latest()
            ->paginate(max(1, min(100, $request->integer('per_page', 24)))));
    }

    public function comments(Request $request)
    {
        return $this->ok(Comment::query()
            ->when($request->content_id, fn ($query, $contentId) => $query->where('content_id', $contentId))
            ->where('status', 'approved')
            ->whereHas('content', fn ($query) => $query->published())
            ->latest()
            ->paginate(max(1, min(100, $request->integer('per_page', 20)))));
    }

    public function me(Request $request)
    {
        return $this->ok($request->user()?->load(['wallet', 'pointsAccount', 'vip.level']));
    }

    public function orders(Request $request)
    {
        return $this->ok(Order::with(['items', 'payments'])->where('user_id', $request->user()?->id)->latest()->paginate());
    }

    public function order(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()?->id || $request->user()?->hasAnyRole(['SUPER_ADMIN', 'ADMIN']), 403);

        return $this->ok($order->load(['items', 'payments']));
    }

    public function createContentOrder(Request $request, Content $content, OrderService $orders)
    {
        $data = $request->validate([
            'gateway' => ['nullable', 'string', 'in:alipay_official,wechat_official,hupijiao_v3,epay,balance,points'],
        ]);

        return $this->ok($orders->createForContent($content, $request->user(), $data['gateway'] ?? 'alipay_official')->load('items'));
    }

    public function createVipOrder(Request $request, VipLevel $vipLevel, OrderService $orders)
    {
        $data = $request->validate([
            'period' => ['nullable', 'string', 'in:monthly,quarterly,yearly,lifetime'],
            'gateway' => ['nullable', 'string', 'in:alipay_official,wechat_official,hupijiao_v3,epay,balance,points'],
        ]);

        return $this->ok($orders->createForVip(
            $vipLevel,
            $request->user(),
            $data['period'] ?? 'yearly',
            $data['gateway'] ?? 'alipay_official'
        )->load('items'));
    }

    public function payOrderWithBalance(Request $request, Order $order, OrderService $orders)
    {
        abort_unless($order->user_id === $request->user()?->id, 403);

        $data = $request->validate(['points' => ['sometimes', 'integer', 'between:0,100000000']]);

        return $this->ok($orders->payWithBalance($order, $request->user(), (int) ($data['points'] ?? 0)));
    }

    public function payOrderWithPoints(Request $request, Order $order, OrderService $orders)
    {
        abort_unless($order->user_id === $request->user()?->id, 403);

        return $this->ok($orders->payWithPoints($order, $request->user()));
    }

    public function products(Request $request)
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:120'], 'per_page' => ['sometimes', 'integer', 'between:1,100']]);

        return $this->ok(Product::where('status', 'published')
            ->select(['id', 'content_id', 'title', 'slug', 'type', 'price', 'sale_price'])
            ->with(['variants' => fn ($query) => $query->where('status', 'active')->select(['id', 'product_id', 'sku', 'title', 'price', 'stock', 'attributes'])])
            ->when($data['q'] ?? '', fn ($query, $q) => $query->where('title', 'like', '%'.$q.'%'))->latest()->paginate($data['per_page'] ?? 20));
    }

    public function recharge(Request $request, OrderService $orders)
    {
        $data = $request->validate(['amount' => ['required', 'numeric', 'decimal:0,2', 'between:1,10000'], 'gateway' => ['required', 'in:epay,alipay_official,wechat_official,hupijiao_v3']]);

        return $this->ok($orders->createRecharge($request->user(), (string) $data['amount'], $data['gateway']));
    }

    public function cancelOrder(Request $request, Order $order, OrderCancellation $cancellation)
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        $cancellation->cancel($order);

        return $this->ok($order->fresh());
    }

    public function requestRefund(Request $request, Order $order, CommerceOperations $operations)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:255'], 'amount' => ['nullable', 'numeric', 'min:0.01', 'decimal:0,2']]);

        return $this->ok($operations->requestRefund($request->user(), $order, $data['reason'], isset($data['amount']) ? (string) $data['amount'] : null));
    }

    public function checkoutPayment(Request $request, Order $order, PaymentManager $payments)
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        $payment = $payments->createPayment($order, $order->pay_channel);

        return $this->ok(['payment_id' => $payment->id, 'status' => $payment->status, 'checkout_url' => data_get($payment->response_payload, 'checkout_url'), 'qr_code' => data_get($payment->response_payload, 'qr_code')]);
    }

    public function queryPayment(Request $request, Order $order, PaymentManager $payments)
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        $payment = $order->payments()->latest('id')->firstOrFail();

        return $this->ok($payments->queryPayment($payment));
    }

    public function withdrawal(Request $request, CommerceOperations $operations)
    {
        $data = $request->validate(['amount' => ['required', 'numeric', 'decimal:0,2', 'between:1,100000'], 'method' => ['required', 'in:alipay,wechat,bank'], 'account' => ['required', 'string', 'max:255']]);

        return $this->ok(['id' => $operations->requestWithdrawal($request->user(), (string) $data['amount'], $data['method'], $data['account'])]);
    }

    public function storeComment(Request $request, Content $content, CommentService $comments)
    {
        return $this->ok($comments->store($request, $content));
    }

    public function download(Request $request, Content $content, OrderService $orders)
    {
        return $this->ok(app(DownloadService::class)->authorize($request, $content));
    }

    public function wallet(Request $request)
    {
        return $this->ok([
            'wallet' => $request->user()?->wallet,
            'transactions' => DB::table('wallet_transactions')
                ->where('wallet_id', $request->user()?->wallet?->id)
                ->latest()
                ->paginate(20),
        ]);
    }

    public function points(Request $request)
    {
        return $this->ok([
            'account' => $request->user()?->pointsAccount,
            'transactions' => DB::table('points_transactions')
                ->where('points_account_id', $request->user()?->pointsAccount?->id)
                ->latest()
                ->paginate(20),
            'store' => DB::table('points_store_items')->where('status', 'active')->latest()->paginate(20),
        ]);
    }

    public function downloads(Request $request)
    {
        return $this->ok(DB::table('download_logs')
            ->where('user_id', $request->user()?->id)
            ->latest()
            ->paginate(20));
    }

    public function notifications(Request $request)
    {
        return $this->ok(DB::table('notifications')
            ->where(function ($query) use ($request) {
                $query->whereNull('user_id')->orWhere('user_id', $request->user()?->id);
            })
            ->latest()
            ->paginate(20));
    }

    public function vip()
    {
        return $this->ok(VipLevel::orderBy('level')->get());
    }

    public function search(Request $request)
    {
        $keyword = trim((string) $request->query('q', ''));

        return $this->ok(Content::query()
            ->with(['author:id,name,username,avatar_url', 'category:id,name,slug'])
            ->published()
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->matchingPublicText($keyword);
            })
            ->latest('published_at')
            ->paginate(max(1, min(100, $request->integer('per_page', 12)))));
    }

    public function themes()
    {
        return $this->ok(Theme::all());
    }

    public function plugins()
    {
        abort_unless(request()->user('sanctum')?->can('manage plugins'), 403);

        return $this->ok(Plugin::all());
    }

    public function pageBuilder(string $scope = 'home')
    {
        return $this->ok(PageLayout::where('scope', $scope)->where('status', 'published')->firstOrFail());
    }

    public function health(SystemHealthService $health)
    {
        abort_unless(request()->user('sanctum')?->can('manage system'), 403);

        return $this->ok($health->report());
    }

    public function neteasePlaylist(string $id, NeteaseMusicService $music)
    {
        return $this->ok($music->playlist($id));
    }

    public function neteaseSong(string $id, NeteaseMusicService $music)
    {
        return $this->ok($music->song($id));
    }

    public function neteaseSongStream(Request $request, string $id, NeteaseMusicService $music)
    {
        return $music->stream($id, $request);
    }

    public function neteaseSongLyric(string $id, NeteaseMusicService $music)
    {
        return response($music->lyric($id), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    public function upgrade()
    {
        return $this->ok([
            'current_version' => config('zfy.version'),
            'latest_version' => config('zfy.version'),
            'channel' => 'local',
            'online_upgrade' => [
                'enabled' => false,
                'message' => '首版保留在线升级中心入口，生产环境接入签名升级包后启用。',
            ],
            'migration' => [
                'command' => 'php artisan migrate --force',
                'backup_required' => true,
            ],
        ]);
    }

    public function authors()
    {
        return $this->ok(User::where('is_author', true)->where('author_status', 'approved')->where('is_banned', false)->select('id', 'name', 'username', 'avatar_url', 'bio')->paginate());
    }

    private function ok(mixed $data): array
    {
        return ['code' => 0, 'message' => 'ok', 'data' => $data];
    }
}
