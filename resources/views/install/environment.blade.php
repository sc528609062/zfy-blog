@extends('install.layout')

@section('title', '环境检查')

@section('content')
    <h2 class="text-lg font-semibold text-ink-900 mb-4">环境检查</h2>

    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-ink-500 border-b border-ink-100">
                <th class="py-2">检查项</th>
                <th class="py-2">要求</th>
                <th class="py-2">当前</th>
                <th class="py-2 text-right">结果</th>
            </tr>
        </thead>
        <tbody>
            @foreach($checks as $c)
                <tr class="border-b border-ink-100/60">
                    <td class="py-2.5 text-ink-700">{{ $c['name'] }}</td>
                    <td class="py-2.5 text-ink-500">{{ $c['required'] }}</td>
                    <td class="py-2.5 text-ink-500">{{ $c['current'] }}</td>
                    <td class="py-2.5 text-right">
                        @if(($c['warn'] ?? false))
                            <span class="badge bg-vip-50 text-vip-600">推荐</span>
                        @elseif($c['pass'])
                            <span class="badge-success">通过</span>
                        @else
                            <span class="badge bg-red-50 text-red-600">未通过</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="flex justify-between mt-8">
        <a href="{{ route('install.index') }}" class="btn-secondary">返回</a>
        @if($passed)
            <a href="{{ route('install.database') }}" class="btn-primary">下一步：数据库</a>
        @else
            <button disabled class="btn-secondary opacity-60 cursor-not-allowed">请先解决上述问题</button>
        @endif
    </div>
@endsection
