<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TagController extends Controller
{
    public function index(): View
    {
        return view('admin.tags.index', [
            'tags' => Tag::orderByDesc('content_count')->paginate(40),
        ]);
    }

    public function create(): View
    {
        return view('admin.tags.edit', ['tag' => new Tag()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Tag::create($request->validate([
            'name' => 'required|string|max:80|unique:tags,name',
            'slug' => 'nullable|string|max:120|alpha_dash',
            'description' => 'nullable|string|max:500',
        ]));
        return redirect()->route('admin.tags.index')->with('status', '标签已创建');
    }

    public function edit(Tag $tag): View
    {
        return view('admin.tags.edit', ['tag' => $tag]);
    }

    public function update(Request $request, Tag $tag): RedirectResponse
    {
        $tag->update($request->validate([
            'name' => 'required|string|max:80|unique:tags,name,' . $tag->id,
            'slug' => 'nullable|string|max:120|alpha_dash',
            'description' => 'nullable|string|max:500',
        ]));
        return back()->with('status', '已保存');
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        $tag->delete();
        return back()->with('status', '已删除');
    }
}
