<?php

namespace App\Services;

use App\Models\Theme;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ThemeConfiguration
{
    public function groups(): array
    {
        return [
            ['key' => 'brand', 'label' => '品牌导航'],
            ['key' => 'layout', 'label' => '配色布局'],
            ['key' => 'home', 'label' => '首页内容'],
            ['key' => 'cards', 'label' => '列表卡片'],
            ['key' => 'article', 'label' => '文章阅读'],
            ['key' => 'footer', 'label' => '页脚信息'],
            ['key' => 'extension', 'label' => '主题扩展'],
        ];
    }

    public function builtInFields(string $slug): array
    {
        $creative = $slug === 'style-c-creative';
        $market = $slug === 'style-b-marketplace';
        $text = fn ($key, $label, $group, $default = '', $max = 180) => compact('key', 'label', 'group', 'default') + ['type' => 'text', 'rules' => ['nullable', 'string', 'max:'.$max]];
        $toggle = fn ($key, $label, $group, $default = true) => compact('key', 'label', 'group', 'default') + ['type' => 'boolean'];
        $select = fn ($key, $label, $group, $default, $options) => compact('key', 'label', 'group', 'default', 'options') + ['type' => 'select'];
        $number = fn ($key, $label, $group, $default, $min, $max, $step = 1) => compact('key', 'label', 'group', 'default', 'min', 'max', 'step') + ['type' => 'number', 'rules' => ['required', $step < 1 ? 'numeric' : 'integer', 'between:'.$min.','.$max]];
        $image = fn ($key, $label, $group) => compact('key', 'label', 'group') + ['type' => 'image', 'default' => ''];

        return [
            ['key' => 'logo_text', 'label' => '站点标识', 'group' => 'brand', 'type' => 'text', 'default' => 'zfy-blog', 'rules' => ['required', 'string', 'max:80']],
            $image('logo_url', '网站 Logo', 'brand'),
            $image('favicon_url', '浏览器图标', 'brand'),
            $text('tagline', '站点副标题', 'brand', $creative ? '灵感 · 设计 · 创作' : ($market ? '资源 · 工具 · 教程' : '发现好内容，分享新体验'), 80),
            $toggle('sticky_header', '固定顶部导航', 'brand'),
            $toggle('header_search', '显示导航搜索', 'brand'),
            $text('search_placeholder', '搜索框提示', 'brand', '搜索文章、资源、教程', 80) + ['depends_on' => 'header_search'],
            $toggle('header_publish', '显示投稿入口', 'brand'),
            $toggle('header_vip', '显示会员入口', 'brand'),
            ['key' => 'primary_color', 'label' => '强调色', 'group' => 'layout', 'type' => 'color', 'default' => data_get(config('zfy.themes.'.$slug), 'accent', '#1684ff'), 'rules' => ['required', 'regex:/^#[a-fA-F0-9]{6}$/']],
            $number('page_width', '页面最大宽度', 'layout', $creative ? 1360 : 1280, 1040, 1600, 20),
            $number('sidebar_width', '侧栏宽度', 'layout', 288, 240, 360, 8),
            $number('card_radius', '卡片圆角', 'layout', $creative ? 8 : 6, 0, 8),
            $select('content_density', '内容间距', 'layout', 'comfortable', ['comfortable' => '舒适', 'compact' => '紧凑']),
            $select('sidebar_position', '侧栏位置', 'layout', 'right', ['right' => '右侧', 'left' => '左侧']),
            $toggle('hero_enabled', '显示首页横幅', 'home'),
            $text('hero_title', '横幅标题', 'home', '', 100) + ['depends_on' => 'hero_enabled', 'placeholder' => '留空使用推荐内容标题'],
            $text('hero_subtitle', '横幅摘要', 'home', '', 240) + ['depends_on' => 'hero_enabled', 'placeholder' => '留空使用推荐内容摘要'],
            $image('hero_image', '横幅图片', 'home') + ['depends_on' => 'hero_enabled'],
            $text('hero_button', '横幅按钮文字', 'home', '查看内容', 30) + ['depends_on' => 'hero_enabled'],
            ['key' => 'hero_url', 'label' => '横幅跳转地址', 'group' => 'home', 'type' => 'url', 'default' => '', 'depends_on' => 'hero_enabled', 'placeholder' => '留空跳转到推荐内容'],
            $toggle('announcement_enabled', '显示站点公告', 'home', false),
            $text('announcement_text', '公告内容', 'home', '', 300) + ['depends_on' => 'announcement_enabled'],
            ['key' => 'announcement_url', 'label' => '公告链接', 'group' => 'home', 'type' => 'url', 'default' => '', 'depends_on' => 'announcement_enabled'],
            $toggle('channels_enabled', '显示首页分类', 'home'),
            $toggle('sidebar_enabled', '显示首页侧栏', 'home'),
            $toggle('home_profile', '显示站点资料', 'home') + ['depends_on' => 'sidebar_enabled'],
            $toggle('home_vip', '显示会员推荐', 'home') + ['depends_on' => 'sidebar_enabled'],
            $text('feed_title', '首页内容标题', 'home', $market ? '精选资源' : ($creative ? '最新创作' : '最新发布'), 60),
            $number('home_count', '首页内容数量', 'home', 12, 4, 12),
            $select('feed_layout', '首页内容样式', 'cards', $market || $creative ? 'grid' : 'list', ['list' => '图文列表', 'grid' => '卡片网格']),
            $select('archive_layout', '频道内容样式', 'cards', $market ? 'list' : 'grid', ['list' => '图文列表', 'grid' => '卡片网格']),
            $select('cover_ratio', '封面比例', 'cards', $creative ? '4/3' : '16/10', ['16/10' => '16:10', '16/9' => '16:9', '4/3' => '4:3', '1/1' => '1:1']),
            $image('default_cover', '默认内容封面', 'cards'),
            $toggle('card_excerpt', '显示卡片摘要', 'cards', ! $creative),
            $toggle('card_author', '显示卡片作者', 'cards'),
            $toggle('card_stats', '显示浏览和评论数', 'cards'),
            $toggle('archive_sidebar', '显示频道侧栏', 'cards'),
            $toggle('detail_sidebar', '显示文章侧栏', 'article'),
            $toggle('show_author_card', '显示作者资料', 'article') + ['depends_on' => 'detail_sidebar'],
            $toggle('show_related', '显示相关内容', 'article') + ['depends_on' => 'detail_sidebar'],
            $toggle('detail_cover', '显示文章封面', 'article'),
            $toggle('detail_excerpt', '显示文章摘要', 'article'),
            $toggle('detail_tags', '显示文章标签', 'article'),
            $toggle('detail_date', '显示发布时间', 'article'),
            $toggle('detail_views', '显示文章阅读量', 'article'),
            $number('article_font_size', '正文字号', 'article', 16, 15, 20),
            $number('article_line_height', '正文行距', 'article', 1.85, 1.6, 2.2, 0.05),
            $text('footer_text', '页脚文字', 'footer', 'zfy-blog 内容商业平台', 500) + ['rows' => 3],
            $text('icp_number', 'ICP备案号', 'footer', '', 80),
            $toggle('footer_navigation', '显示页脚导航', 'footer'),
            $toggle('back_to_top', '显示返回顶部', 'footer'),
        ];
    }

    public function defaults(Theme $theme): array
    {
        $defaults = app(ThemeManager::class)->defaultSettings($theme->slug)['global'] ?? [];

        return collect($this->fields($theme))->mapWithKeys(fn ($field) => [
            $field['key'] => $defaults[$field['key']] ?? $field['default'] ?? null,
        ])->all();
    }

    public function fields(Theme $theme): array
    {
        $fields = array_key_exists($theme->slug, config('zfy.themes', []))
            ? collect($this->builtInFields($theme->slug))->keyBy('key')->all() : [];
        foreach (($theme->settings_schema['global'] ?? []) as $field) {
            if (! is_array($field) || ! preg_match('/^[a-z][a-z0-9_]{0,79}$/', $field['key'] ?? '')) {
                continue;
            }
            $type = $field['type'] ?? 'text';
            if (! in_array($type, ['text', 'textarea', 'color', 'boolean', 'number', 'select', 'image', 'url'], true)) {
                continue;
            }
            $base = $fields[$field['key']] ?? [];
            $group = $field['group'] ?? $base['group'] ?? 'extension';
            $group = in_array($group, array_column($this->groups(), 'key'), true) ? $group : 'extension';
            $fields[$field['key']] = [...$base, ...$field, 'group' => $group, 'type' => $type, 'label' => $field['label'] ?? $base['label'] ?? $field['key']];
        }

        return array_values($fields);
    }

    public function validate(Theme $theme, array $values): array
    {
        $fields = collect($this->fields($theme))->keyBy('key');
        if (array_diff(array_keys($values), $fields->keys()->all())) {
            throw ValidationException::withMessages(['theme' => '包含未注册的主题设置。']);
        }
        $result = [];
        foreach ($values as $key => $value) {
            $field = $fields[$key];
            if ($value === null && in_array($field['type'], ['text', 'textarea', 'image', 'url'], true)) {
                $value = '';
            }
            if (in_array($field['type'], ['image', 'url'], true) && filled($value) && ! $this->safeUrl($value)) {
                throw ValidationException::withMessages([$key => $field['label'].'必须使用站内路径或 HTTP/HTTPS 地址。']);
            }
            $rules = $field['rules'] ?? match ($field['type']) {
                'boolean' => ['required', 'boolean'],
                'number' => ['nullable', 'numeric', 'between:-1000000,1000000'],
                'color' => ['nullable', 'regex:/^#[a-fA-F0-9]{6}$/'],
                'select' => ['nullable', Rule::in(array_keys($field['options'] ?? []))],
                default => ['nullable', 'string', 'max:4000'],
            };
            $result[$key] = Validator::make(['value' => $value], ['value' => $rules], [], ['value' => $field['label']])->validate()['value'];
        }

        return $result;
    }

    public function safeUrl(mixed $value): bool
    {
        if (! is_string($value) || preg_match('/[\s\\\\<>"\x00-\x1f]/', $value)) {
            return false;
        }

        return (str_starts_with($value, '/') && ! str_starts_with($value, '//'))
            || (in_array(strtolower(parse_url($value, PHP_URL_SCHEME) ?: ''), ['http', 'https'], true) && filter_var($value, FILTER_VALIDATE_URL));
    }
}
