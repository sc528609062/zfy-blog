@extends('admin.layouts.app')
@section('title', '菜单：' . $menu->name)
@section('page_title', '菜单：' . $menu->name)
@section('content')
    <div class="grid lg:grid-cols-2 gap-4">
        <div class="card p-5">
            <h3 class="font-semibold text-ink-900 mb-3">现有菜单项</h3>
            <ul class="divide-y divide-ink-100">
                @foreach($menu->items as $item)
                    <li class="py-2.5 flex items-center justify-between">
                        <span class="text-sm text-ink-700">
                            @if($item->parent_id)<span class="text-ink-300">└</span> @endif
                            {{ $item->label }}
                            <span class="text-xs text-ink-400 ml-2">{{ $item->target_type }} · {{ $item->target_ref }}</span>
                        </span>
                        <form method="POST" action="{{ route('admin.menus.items.destroy', [$menu, $item]) }}">@csrf @method('DELETE')<button class="btn-ghost text-xs text-red-500" onclick="return confirm('删除？')">删除</button></form>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="card p-5">
            <h3 class="font-semibold text-ink-900 mb-3">添加菜单项</h3>
            <form method="POST" action="{{ route('admin.menus.items.store', $menu) }}" class="space-y-3">
                @csrf
                <input type="text" name="label" placeholder="菜单文字" class="input" required>
                <input type="text" name="icon" placeholder="图标名（可选）" class="input">
                <select name="target_type" class="input" required>
                    @foreach(['url' => '外部链接','route' => '路由名','content' => '内容','page' => '页面','category' => '分类','tag' => '标签'] as $k=>$v)
                        <option value="{{ $k }}">{{ $v }}</option>
                    @endforeach
                </select>
                <input type="text" name="target_ref" placeholder="链接 / 路由名 / slug / id" class="input">
                <select name="open_in" class="input"><option value="_self">本窗口</option><option value="_blank">新窗口</option></select>
                <select name="visibility" class="input">
                    <option value="all">所有人</option>
                    <option value="guest">仅未登录</option>
                    <option value="auth">仅已登录</option>
                    <option value="vip">仅 VIP</option>
                </select>
                <button class="btn-primary">添加</button>
            </form>
        </div>
    </div>
@endsection
