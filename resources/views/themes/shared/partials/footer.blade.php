<footer class="a-footer">
    <div>
        <a class="site-footer-brand" href="/">{{ data_get($theme, 'settings.global.logo_text', $siteName) }}</a>
        <p>© {{ date('Y') }} {{ data_get($theme, 'settings.global.footer_text', $siteName) }}</p>
        @if(filled(data_get($theme, 'settings.global.icp_number')))
            <a class="site-icp" href="https://beian.miit.gov.cn/" target="_blank" rel="noopener noreferrer">{{ data_get($theme, 'settings.global.icp_number') }}</a>
        @endif
    </div>
    @if(data_get($theme, 'settings.global.footer_navigation', true))
    <nav aria-label="页脚导航">
        @if(isset($navigation) && $navigation->has('footer'))
            @include('themes.shared.partials.navigation-items', ['items' => $navigation->get('footer')->items])
        @else
            <a href="/posts">文章</a><a href="/files">资源</a><a href="/authors">创作者</a><a href="/links">友情链接</a><a href="/vip">会员中心</a>
        @endif
    </nav>
    @endif
</footer>
