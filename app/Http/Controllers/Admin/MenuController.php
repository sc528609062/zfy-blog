<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function index(): View
    {
        return view('admin.menus.index', [
            'menus' => Menu::with('items')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.menus.edit', ['menu' => new Menu()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Menu::create($request->validate([
            'name'        => 'required|string|max:100',
            'location'    => 'required|string|max:100|unique:menus,location',
            'description' => 'nullable|string|max:255',
        ]));
        return redirect()->route('admin.menus.index')->with('status', '菜单已创建');
    }

    public function show(Menu $menu): View
    {
        return view('admin.menus.show', ['menu' => $menu->load('items')]);
    }

    public function edit(Menu $menu): View
    {
        return view('admin.menus.show', ['menu' => $menu->load('items')]);
    }

    public function update(Request $request, Menu $menu): RedirectResponse
    {
        $menu->update($request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]));
        return back()->with('status', '已保存');
    }

    public function destroy(Menu $menu): RedirectResponse
    {
        $menu->delete();
        return redirect()->route('admin.menus.index')->with('status', '已删除');
    }

    public function storeItem(Request $request, Menu $menu): RedirectResponse
    {
        $data = $this->validateItem($request);
        $menu->items()->create($data);
        return back()->with('status', '菜单项已添加');
    }

    public function updateItem(Request $request, Menu $menu, MenuItem $item): RedirectResponse
    {
        $item->update($this->validateItem($request));
        return back()->with('status', '菜单项已更新');
    }

    public function destroyItem(Menu $menu, MenuItem $item): RedirectResponse
    {
        $item->delete();
        return back()->with('status', '菜单项已删除');
    }

    public function reorderItems(Request $request, Menu $menu): JsonResponse
    {
        $items = $request->validate(['items' => 'array', 'items.*.id' => 'integer', 'items.*.sort_order' => 'integer', 'items.*.parent_id' => 'nullable|integer']);
        foreach ($items['items'] ?? [] as $row) {
            MenuItem::where('id', $row['id'])->where('menu_id', $menu->id)->update([
                'sort_order' => $row['sort_order'],
                'parent_id'  => $row['parent_id'] ?? null,
            ]);
        }
        return response()->json(['ok' => true]);
    }

    protected function validateItem(Request $request): array
    {
        return $request->validate([
            'parent_id'   => 'nullable|integer',
            'label'       => 'required|string|max:100',
            'icon'        => 'nullable|string|max:80',
            'target_type' => 'required|in:url,content,category,tag,page,route',
            'target_ref'  => 'nullable|string|max:500',
            'open_in'     => 'required|in:_self,_blank',
            'visibility'  => 'required|string|max:30',
            'sort_order'  => 'nullable|integer',
            'enabled'     => 'sometimes|boolean',
        ]);
    }
}
