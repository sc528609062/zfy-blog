@extends('admin.layouts.app')

@section('title', $content->exists ? '编辑内容' : '新建内容')
@section('page_title', $content->exists ? '编辑内容' : '新建内容')

@section('content')
    <form method="POST" action="{{ $content->exists ? route('admin.contents.update', $content) : route('admin.contents.store') }}" class="grid lg:grid-cols-3 gap-6">
        @csrf
        @if($content->exists)@method('PUT')@endif

        <div class="lg:col-span-2 space-y-4">
            <div class="card p-5">
                <input type="text" name="title" value="{{ old('title', $content->title) }}"
                       placeholder="给你的内容起个名字..."
                       class="w-full text-2xl font-bold text-ink-900 border-0 focus:ring-0 placeholder-ink-300 px-0">
                <input type="text" name="slug" value="{{ old('slug', $content->slug) }}"
                       placeholder="自动生成，可手动修改"
                       class="w-full text-xs text-ink-400 border-0 focus:ring-0 mt-2 px-0">
            </div>

            <div class="card p-0 overflow-hidden">
                <div class="px-5 py-3 border-b border-ink-100 text-sm text-ink-700 flex items-center justify-between">
                    <span>正文（M2 完整接入 TipTap 三模式编辑器）</span>
                    <span class="badge-muted">Sprint 1 / M2</span>
                </div>
                <div class="p-5">
                    <textarea name="rendered_html" rows="14" class="input font-mono text-xs"
                              placeholder="暂时使用 HTML 文本框，Sprint 1/M2 将切换到块编辑器">{{ old('rendered_html', $content->rendered_html) }}</textarea>
                    <p class="text-xs text-ink-400 mt-2">提示：HTML 在保存时会经 HTMLPurifier 清洗。Sprint 1/M2 上线后，此处替换为 TipTap 编辑器。</p>
                </div>
            </div>

            <div class="card p-5">
                <h3 class="font-semibold text-ink-900 mb-3">摘要</h3>
                <textarea name="excerpt" rows="3" class="input">{{ old('excerpt', $content->excerpt) }}</textarea>
            </div>

            <div class="card p-5">
                <h3 class="font-semibold text-ink-900 mb-3">SEO</h3>
                <div class="space-y-3">
                    <input type="text" name="seo_title" value="{{ old('seo_title', $content->seo_title) }}" class="input" placeholder="SEO 标题（默认使用主标题）">
                    <input type="text" name="seo_keywords" value="{{ old('seo_keywords', $content->seo_keywords) }}" class="input" placeholder="关键词（逗号分隔）">
                    <textarea name="seo_description" rows="2" class="input" placeholder="SEO 描述">{{ old('seo_description', $content->seo_description) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-4">
            <div class="card p-5">
                <h3 class="font-semibold text-ink-900 mb-3">发布</h3>
                <div class="space-y-3">
                    <label class="text-sm text-ink-600 block">类型
                        <select name="type" class="input mt-1">
                            @foreach(config('zfy.content_types') as $k => $cfg)
                                <option value="{{ $k }}" {{ old('type', $content->type) === $k ? 'selected' : '' }}>{{ $cfg['label'] }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-sm text-ink-600 block">状态
                        <select name="status" class="input mt-1">
                            <option value="draft">草稿</option>
                            <option value="pending">待审</option>
                            <option value="published" {{ old('status', $content->status) === 'published' ? 'selected' : '' }}>已发布</option>
                            <option value="private">私密</option>
                        </select>
                    </label>
                </div>
                <button class="btn-primary w-full mt-4">{{ $content->exists ? '保存' : '创建' }}</button>
            </div>

            <div class="card p-5">
                <h3 class="font-semibold text-ink-900 mb-3">分类与标签</h3>
                <select name="category_id" class="input">
                    <option value="">— 无分类 —</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('category_id', $content->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
                <input type="text" name="tag_names_text" value="{{ $content->tags ? $content->tags->pluck('name')->join(',') : '' }}"
                       class="input mt-3" placeholder="逗号分隔的标签">
                <p class="text-xs text-ink-400 mt-1">M2 将切换为标签选择器</p>
            </div>

            <div class="card p-5">
                <h3 class="font-semibold text-ink-900 mb-3">封面</h3>
                <input type="url" name="cover_url" value="{{ old('cover_url', $content->cover_url) }}" class="input" placeholder="图片 URL">
            </div>

            <div class="card p-5">
                <h3 class="font-semibold text-ink-900 mb-3">访问与定价</h3>
                <select name="visibility" class="input">
                    @foreach(['public'=>'公开','logged_in'=>'登录可见','vip'=>'VIP','paid'=>'付费','commented'=>'评论后','password'=>'密码','points'=>'积分'] as $k => $v)
                        <option value="{{ $k }}" {{ old('visibility', $content->visibility) === $k ? 'selected' : '' }}>{{ $v }}</option>
                    @endforeach
                </select>
                <input type="text" name="access_password" value="{{ old('access_password', $content->access_password) }}" class="input mt-3" placeholder="访问密码（可见性=密码时）">
                <div class="grid grid-cols-2 gap-2 mt-3">
                    <input type="number" step="0.01" name="price" value="{{ old('price', $content->price) }}" class="input" placeholder="价格 ¥">
                    <input type="number" step="1" name="points_price" value="{{ old('points_price', $content->points_price) }}" class="input" placeholder="积分">
                </div>
            </div>
        </div>
    </form>
@endsection
