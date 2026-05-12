<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.categories.index', [
            'categories' => Category::orderBy('sort_order')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.categories.edit', [
            'category' => new Category(),
            'parents'  => Category::whereNull('parent_id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        Category::create($data);
        return redirect()->route('admin.categories.index')->with('status', '分类已创建');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.edit', [
            'category' => $category,
            'parents'  => Category::whereNull('parent_id')->where('id', '!=', $category->id)->get(),
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $category->update($this->validateData($request));
        return back()->with('status', '已保存');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();
        return back()->with('status', '已删除');
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'parent_id'   => 'nullable|exists:categories,id',
            'name'        => 'required|string|max:100',
            'slug'        => 'nullable|string|max:120|alpha_dash',
            'description' => 'nullable|string|max:500',
            'icon'        => 'nullable|string|max:80',
            'cover'       => 'nullable|string|max:255',
            'sort_order'  => 'nullable|integer',
        ]);
    }
}
