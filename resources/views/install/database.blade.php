@extends('install.layout')

@section('title', '数据库配置')

@section('content')
    <h2 class="text-lg font-semibold text-ink-900 mb-4">数据库配置</h2>
    <p class="text-sm text-ink-500 mb-6">请提供数据库连接信息。zfy-blog 推荐使用 MySQL 8.0+ 或 MariaDB 10.6+。</p>

    <form method="POST" action="{{ route('install.database.store') }}" class="space-y-4">
        @csrf
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="text-sm text-ink-600 block mb-1">驱动</label>
                <select name="connection" class="input">
                    <option value="mysql"   {{ $old['connection']=='mysql'   ? 'selected':'' }}>MySQL / MariaDB</option>
                    <option value="pgsql"   {{ $old['connection']=='pgsql'   ? 'selected':'' }}>PostgreSQL</option>
                    <option value="sqlite"  {{ $old['connection']=='sqlite'  ? 'selected':'' }}>SQLite</option>
                </select>
            </div>
            <div>
                <label class="text-sm text-ink-600 block mb-1">主机</label>
                <input type="text" name="host" value="{{ $old['host'] }}" class="input">
            </div>
            <div>
                <label class="text-sm text-ink-600 block mb-1">端口</label>
                <input type="text" name="port" value="{{ $old['port'] }}" class="input">
            </div>
            <div>
                <label class="text-sm text-ink-600 block mb-1">数据库名</label>
                <input type="text" name="database" value="{{ $old['database'] }}" class="input">
            </div>
            <div>
                <label class="text-sm text-ink-600 block mb-1">用户名</label>
                <input type="text" name="username" value="{{ $old['username'] }}" class="input">
            </div>
            <div>
                <label class="text-sm text-ink-600 block mb-1">密码</label>
                <input type="password" name="password" value="{{ $old['password'] }}" class="input">
            </div>
        </div>

        <div class="flex justify-between mt-8">
            <a href="{{ route('install.environment') }}" class="btn-secondary">返回</a>
            <button type="submit" class="btn-primary">测试并下一步</button>
        </div>
    </form>
@endsection
