@extends('admin.layouts.app')
@section('title', '设置')
@section('page_title', '系统设置')
@section('content')
    <form method="POST" action="{{ route('admin.settings.update') }}" class="grid lg:grid-cols-2 gap-4 max-w-5xl">
        @csrf
        <div class="card p-5">
            <h3 class="font-semibold text-ink-900 mb-4">站点基础</h3>
            <div class="space-y-3">
                <div><label class="text-sm text-ink-600 block mb-1">站点名称</label><input type="text" name="site.name" value="{{ $settings['site.name'] ?? '' }}" class="input" required></div>
                <div><label class="text-sm text-ink-600 block mb-1">Slogan</label><input type="text" name="site.tagline" value="{{ $settings['site.tagline'] ?? '' }}" class="input"></div>
                <div><label class="text-sm text-ink-600 block mb-1">版权信息</label><input type="text" name="site.copyright" value="{{ $settings['site.copyright'] ?? '' }}" class="input"></div>
                <div><label class="text-sm text-ink-600 block mb-1">ICP 备案号</label><input type="text" name="site.icp" value="{{ $settings['site.icp'] ?? '' }}" class="input"></div>
                <div><label class="text-sm text-ink-600 block mb-1">Logo URL</label><input type="text" name="site.logo" value="{{ $settings['site.logo'] ?? '' }}" class="input"></div>
            </div>
        </div>

        <div class="card p-5">
            <h3 class="font-semibold text-ink-900 mb-4">注册与评论</h3>
            <div class="space-y-3">
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="reg.allow" value="1" {{ ($settings['reg.allow'] ?? '1') === '1' ? 'checked' : '' }}> 允许注册</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="reg.require_email_verify" value="1" {{ ($settings['reg.require_email_verify'] ?? '0') === '1' ? 'checked' : '' }}> 注册需邮件验证</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="comment.allow_guest" value="1" {{ ($settings['comment.allow_guest'] ?? '1') === '1' ? 'checked' : '' }}> 允许游客评论</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="comment.audit" value="1" {{ ($settings['comment.audit'] ?? '0') === '1' ? 'checked' : '' }}> 评论需审核</label>
            </div>
        </div>

        <div class="lg:col-span-2"><button class="btn-primary">保存设置</button></div>
    </form>
@endsection
