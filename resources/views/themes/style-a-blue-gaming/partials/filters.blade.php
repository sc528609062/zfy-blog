<aside class="a-filter-panel">
    <h3>全部分类</h3>
    @foreach($categories->take(8) as $category)
        <a href="/c/{{ $category->slug }}"><span>{{ mb_substr($category->name, 0, 1) }}</span>{{ $category->name }}</a>
    @endforeach
    <div class="a-filter-group">
        <strong>资源筛选</strong>
        @foreach(['全部','免费','付费','RAR','ZIP','Windows','macOS','10GB以上'] as $filter)
            <button class="{{ $loop->first ? 'active' : '' }}">{{ $filter }}</button>
        @endforeach
    </div>
</aside>
