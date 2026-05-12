<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Content\PublishContent;
use App\Actions\Content\SaveContent;
use App\Actions\Content\TrashContent;
use App\Actions\Content\UnpublishContent;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Content;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Content::query()
            ->with(['author', 'category'])
            ->latest();

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($keyword = $request->input('q')) {
            $query->where('title', 'like', "%{$keyword}%");
        }

        $contents = $query->paginate(20)->withQueryString();

        return view('admin.contents.index', compact('contents'));
    }

    public function create(): View
    {
        return view('admin.contents.edit', [
            'content'    => new Content(['type' => Content::TYPE_POST, 'status' => Content::STATUS_DRAFT]),
            'categories' => Category::all(),
        ]);
    }

    public function store(Request $request, SaveContent $action): RedirectResponse
    {
        $content = $action->execute($request->all(), $request->user());
        return redirect()
            ->route('admin.contents.edit', $content)
            ->with('status', '内容已保存');
    }

    public function edit(Content $content): View
    {
        return view('admin.contents.edit', [
            'content'    => $content->load(['tags', 'images', 'downloads']),
            'categories' => Category::all(),
        ]);
    }

    public function update(Request $request, Content $content, SaveContent $action): RedirectResponse
    {
        $action->execute($request->all(), $request->user(), $content);
        return back()->with('status', '内容已更新');
    }

    public function destroy(Content $content, TrashContent $action): RedirectResponse
    {
        $action->execute($content, request()->user());
        return redirect()->route('admin.contents.index')->with('status', '已移入回收站');
    }

    public function publish(Content $content, PublishContent $action): RedirectResponse
    {
        $action->execute($content, request()->user());
        return back()->with('status', '已发布');
    }

    public function unpublish(Content $content, UnpublishContent $action): RedirectResponse
    {
        $action->execute($content, request()->user());
        return back()->with('status', '已撤下');
    }

    public function trash(Content $content, TrashContent $action): RedirectResponse
    {
        $action->execute($content, request()->user());
        return back()->with('status', '已移入回收站');
    }
}
