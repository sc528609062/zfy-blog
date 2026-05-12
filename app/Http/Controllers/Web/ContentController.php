<?php

namespace App\Http\Controllers\Web;

use App\Domain\Content\ContentAccessService;
use App\Http\Controllers\Controller;
use App\Models\Content;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContentController extends Controller
{
    public function __construct(protected ContentAccessService $access) {}

    public function show(Content $content, Request $request): View
    {
        if ($content->type === Content::TYPE_PAGE) {
            return view('page-show', ['content' => $content]);
        }

        $providedPassword = $request->session()->get("content.unlock.{$content->id}.password");
        $decision = $this->access->check($content, $request->user(), $providedPassword);

        $content->loadMissing(['author', 'category', 'tags', 'images', 'downloads']);

        // 异步增加浏览量（暂时同步，Sprint 2 转 Job）
        if ($decision->allowed) {
            $content->increment('view_count');
        }

        return view('content-show', [
            'content'  => $content,
            'decision' => $decision,
        ]);
    }

    public function unlock(Content $content, Request $request): RedirectResponse
    {
        $request->validate([
            'password' => 'required|string|max:100',
        ]);

        if ($content->visibility !== Content::VISIBILITY_PASSWORD) {
            return back();
        }

        if ($request->password === $content->access_password) {
            $request->session()->put("content.unlock.{$content->id}.password", $request->password);
            return redirect()->route('content.show', $content->slug);
        }

        return back()->withErrors(['password' => '密码错误']);
    }
}
