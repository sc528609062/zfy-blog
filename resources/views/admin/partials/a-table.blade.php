@php($rows = collect($rows ?? [])->take(!empty($large) ? 12 : 5))
<table class="a-admin-table">
    <thead><tr><th>ID</th><th>标题/对象</th><th>状态</th><th>类型</th><th>时间</th><th>操作</th></tr></thead>
    <tbody>
    @forelse($rows as $row)
        <tr>
            <td>{{ $row->id }}</td>
            <td>{{ $row->title ?? $row->order_no ?? '系统对象' }}</td>
            <td><span>{{ $row->status ?? 'published' }}</span></td>
            <td>{{ $row->type ?? $row->pay_channel ?? '资源' }}</td>
            <td>{{ $row->created_at?->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i') }}</td>
            <td><a>编辑</a><a>审核</a><a>日志</a></td>
        </tr>
    @empty
        <tr><td colspan="6">暂无数据</td></tr>
    @endforelse
    </tbody>
</table>
