<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Content;
use App\Models\Order;
use App\Models\Plugin;
use App\Models\Tag;
use App\Models\Theme;
use App\Models\User;
use App\Services\DemoContentRepository;
use App\Services\ThemeManager;
use Illuminate\Http\Request;

class PlatformController extends Controller
{
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

    public function me(Request $request)
    {
        return $this->ok($request->user()?->load(['wallet', 'pointsAccount', 'vip.level']));
    }

    public function orders(Request $request)
    {
        return $this->ok(Order::where('user_id', $request->user()?->id)->latest()->paginate());
    }

    public function themes()
    {
        return $this->ok(Theme::all());
    }

    public function plugins()
    {
        return $this->ok(Plugin::all());
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
