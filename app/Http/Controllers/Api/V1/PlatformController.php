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
use App\Models\Tag;
use App\Models\Theme;
use App\Models\User;
use App\Models\VipLevel;
use App\Services\DemoContentRepository;
use App\Services\NeteaseMusicService;
use App\Services\OrderService;
use App\Services\SystemHealthService;
use App\Services\ThemeManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PlatformController extends Controller
{
    public function token(Request $request)
    {
        $data = $request->validate([
            'login' => ['nullable', 'string', 'max:160', 'required_without:email'],
            'email' => ['nullable', 'string', 'max:160', 'required_without:login'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:80'],
        ]);

        $login = (string) ($data['login'] ?? $data['email'] ?? '');
        $user = User::findForLogin($login);

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['login' => '账号或密码不正确。']);
        }

        return $this->ok([
            'token_type' => 'Bearer',
            'access_token' => $user->createToken($data['device_name'] ?? 'zfy-blog-api')->plainTextToken,
            'user' => $user->only(['id', 'name', 'email', 'username', 'avatar_url']),
        ]);
    }

    public function home(DemoContentRepository $repository, ThemeManager $themes)
    {
        return $this->ok([
            'theme' => $themes->active(),
            'data' => $repository->pageData(),
        ]);
    }

    public function contents(Request $request)
    {
        $contents = Content::query()
            ->with(['author:id,name,username,avatar_url', 'category:id,name,slug'])
            ->when($request->type, fn ($query, $type) => $query->where('type', $type))
            ->when($request->q, fn ($query, $q) => $query->where('title', 'like', "%{$q}%"))
            ->where('status', 'published')
            ->latest('published_at')
            ->paginate($request->integer('per_page', 12));

        return $this->ok($contents);
    }

    public function content(string $slug)
    {
        return $this->ok(Content::with(['author', 'category', 'tags'])->where('slug', $slug)->firstOrFail());
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
        return $this->ok(Category::withCount('contents')->orderBy('sort_order')->paginate(30));
    }

    public function tags()
    {
        return $this->ok(Tag::withCount('contents')->latest()->paginate(50));
    }

    public function media(Request $request)
    {
        return $this->ok(Media::query()
            ->when($request->type, fn ($query, $type) => $query->where('type', $type))
            ->latest()
            ->paginate($request->integer('per_page', 24)));
    }

    public function comments(Request $request)
    {
        return $this->ok(Comment::query()
            ->when($request->content_id, fn ($query, $contentId) => $query->where('content_id', $contentId))
            ->where('status', $request->string('status', 'approved'))
            ->latest()
            ->paginate($request->integer('per_page', 20)));
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
            'period' => ['nullable', 'string', 'in:monthly,yearly'],
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

        return $this->ok($orders->payWithBalance($order, $request->user()));
    }

    public function payOrderWithPoints(Request $request, Order $order, OrderService $orders)
    {
        abort_unless($order->user_id === $request->user()?->id, 403);

        return $this->ok($orders->payWithPoints($order, $request->user()));
    }

    public function storeComment(Request $request, Content $content)
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ]);

        $comment = $content->comments()->create([
            'user_id' => $request->user()->id,
            'parent_id' => $data['parent_id'] ?? null,
            'status' => 'pending',
            'body' => $data['body'],
            'ip_address' => $request->ip(),
            'meta' => ['user_agent' => $request->userAgent()],
        ]);

        $content->increment('comment_count');

        return $this->ok($comment);
    }

    public function download(Request $request, Content $content, OrderService $orders)
    {
        $allowed = $orders->userCanAccessContent($content, $request->user());

        DB::table('download_logs')->insert([
            'content_id' => $content->id,
            'user_id' => $request->user()->id,
            'media_id' => null,
            'ip_address' => $request->ip(),
            'device_hash' => sha1((string) $request->userAgent()),
            'status' => $allowed ? 'allowed' : 'denied',
            'meta' => json_encode(['reason' => $allowed ? 'access_granted' : 'payment_required'], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        abort_unless($allowed, 403, '当前账号没有下载权限。');

        $content->increment('download_count');

        return $this->ok([
            'content_id' => $content->id,
            'download_url' => route('contents.show', ['slug' => $content->slug]),
            'message' => '下载权限已通过，真实文件地址由附件存储适配器签发。',
        ]);
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
            ->where('status', 'published')
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($subQuery) use ($keyword) {
                    $subQuery->where('title', 'like', "%{$keyword}%")
                        ->orWhere('excerpt', 'like', "%{$keyword}%")
                        ->orWhere('markdown_cache', 'like', "%{$keyword}%");
                });
            })
            ->latest('published_at')
            ->paginate($request->integer('per_page', 12)));
    }

    public function themes()
    {
        return $this->ok(Theme::all());
    }

    public function plugins()
    {
        return $this->ok(Plugin::all());
    }

    public function pageBuilder(string $scope = 'home')
    {
        return $this->ok(PageLayout::where('scope', $scope)->firstOrFail());
    }

    public function health(SystemHealthService $health)
    {
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
        return $this->ok(User::where('is_author', true)->select('id', 'name', 'username', 'avatar_url', 'bio')->paginate());
    }

    private function ok(mixed $data): array
    {
        return ['code' => 0, 'message' => 'ok', 'data' => $data];
    }
}
