@php
    $appearance = data_get($theme, 'settings.global', []);
    $number = fn ($key, $default, $min, $max) => max($min, min($max, is_numeric($appearance[$key] ?? null) ? (float) $appearance[$key] : $default));
    $ratio = in_array($appearance['cover_ratio'] ?? '', ['16/10', '16/9', '4/3', '1/1'], true) ? $appearance['cover_ratio'] : '16/10';
    $favicon = $appearance['favicon_url'] ?? '';
@endphp
@if(filled($favicon) && app(\App\Services\ThemeConfiguration::class)->safeUrl($favicon))<link rel="icon" href="{{ $favicon }}">@endif
<style>
    :root {
        --a-primary: {{ $accent }};
        --a-on-primary: {{ $onAccent }};
        --a-primary-dark: {{ $accentHover }};
        --a-primary-text: {{ $accentText }};
        --site-width: {{ $number('page_width', 1280, 1040, 1600) }}px;
        --site-sidebar: {{ $number('sidebar_width', 288, 240, 360) }}px;
        --site-radius: {{ $number('card_radius', 6, 0, 8) }}px;
        --site-gap: {{ ($appearance['content_density'] ?? '') === 'compact' ? 16 : 24 }}px;
        --site-cover-ratio: {{ $ratio }};
        --site-article-font: {{ $number('article_font_size', 16, 15, 20) }}px;
        --site-article-leading: {{ $number('article_line_height', 1.85, 1.6, 2.2) }};
    }
</style>
