<?php

namespace App\Services;

class CoreBlockRegistry
{
    public function register(): void
    {
        $text = fn ($key, $label, $type = 'text') => compact('key', 'label', 'type');
        $title = $text('title', '标题');
        $body = $text('body', '内容 (Markdown)', 'textarea');
        $color = $text('color', '颜色', 'color');
        $url = $text('url', '地址');
        $definitions = [
            'alert' => ['消息提示', [$title, $body, $color]],
            'callout' => ['备注', [$title, $body]],
            'progress' => ['进度条', [$text('value', '进度', 'number'), $color]],
            'tabs' => ['标签栏', [$title, $body]],
            'card-list' => ['列表卡片', [$body]],
            'card-default' => ['标题卡片', [$title, $body, $color]],
            'card-describe' => ['说明卡片', [$title, $body]],
            'timeline' => ['时间轴', [$body]],
            'collapse' => ['折叠内容', [$title, $body]],
            'copy' => ['点击复制', [$title, $body]],
            'dotted' => ['彩色分隔线', [$text('startcolor', '起始颜色'), $text('endcolor', '结束颜色')]],
            'lamp' => ['跑马条', [$title]],
            'button' => ['彩色按钮', [$title, $url, $color]],
            'anote' => ['便条按钮', [$title, $url]],
            'time' => ['时间', [$text('format', '日期格式')]],
            'mp3' => ['音频播放器', [$title, $url, $text('cover', '封面地址')]],
            'dplayer' => ['视频播放器', [$title, $url]],
            'bilibili' => ['Bilibili 视频', [$text('bvid', 'BV 号'), $text('page', '分集', 'number')]],
            'music' => ['网易云单曲', [$text('id', '歌曲 ID')]],
            'music-list' => ['网易云歌单', [$text('id', '歌单 ID')]],
            'cloud' => ['网盘下载', [$title, $url, $text('password', '提取码')]],
        ];
        foreach ($definitions as $name => [$label, $fields]) {
            $rules = [];
            $defaults = [];
            foreach ($fields as $field) {
                $key = $field['key'];
                $rules[$key] = $field['type'] === 'number' ? ['nullable', 'integer', 'between:0,100'] : ['nullable', 'string', 'max:10000'];
                $defaults[$key] = $field['type'] === 'number' ? 0 : '';
            }
            $defaults['body'] = match ($name) {
                'tabs' => "{zfy-tab title=\"标签一\"}内容一{/zfy-tab}\n{zfy-tab title=\"标签二\"}内容二{/zfy-tab}",
                'timeline' => '{zfy-timeline-item title="阶段一"}正文{/zfy-timeline-item}',
                default => '',
            };
            if (in_array($name, ['tabs', 'timeline', 'collapse', 'card-list'], true)) {
                $fields = array_map(fn ($field) => $field['key'] === 'body' ? [...$field, 'unless' => 'items'] : $field, $fields);
                $fields[] = ['key' => 'items', 'label' => '子项', 'type' => 'repeater', 'max' => 30,
                    'fields' => [$title, $body, ...($name === 'timeline' ? [$color] : []), ...($name === 'collapse' ? [$text('open', '默认展开', 'boolean')] : [])],
                    'defaults' => ['title' => '', 'body' => '', 'color' => '', 'open' => false],
                ];
                $defaults['body'] = '';
                $defaults['items'] = [['title' => '子项一', 'body' => '', 'color' => '', 'open' => true]];
                $rules += [
                    'items' => ['sometimes', 'array', 'max:30'],
                    'items.*' => ['array:title,body,color,open'],
                    'items.*.title' => ['nullable', 'string', 'max:180'],
                    'items.*.body' => ['nullable', 'string', 'max:10000'],
                    'items.*.color' => ['nullable', 'regex:/^#[a-fA-F0-9]{3,8}$/'],
                    'items.*.open' => ['sometimes', 'boolean'],
                ];
            }
            zfy_register_block('core-'.$name, ['label' => $label, 'fields' => $fields, 'defaults' => $defaults, 'rules' => $rules,
                'render' => fn ($values, $context) => app(ContentMarkdownRenderer::class)->renderComponent('zfy-'.$name, $values, $context['content'] ?? null, $context['user'] ?? null),
            ]);
        }
        zfy_register_block('core-gallery', ['label' => '图集', 'fields' => [$text('images', '图片地址，每行一张', 'textarea')], 'defaults' => ['images' => ''], 'rules' => ['images' => ['required', 'string', 'max:10000']], 'render' => function ($values) {
            $html = '<div class="zfy-native-gallery">';
            foreach (array_slice(preg_split('/\R/', $values['images']), 0, 50) as $url) {
                $url = trim($url);
                if (preg_match('~^(https?://|/(?!/))~', $url) && ! preg_match('~\.svg(?:[?#]|$)~i', $url)) {
                    $html .= '<img src="'.e($url).'" alt="" loading="lazy">';
                }
            }

            return $html.'</div>';
        }]);
        zfy_register_block('core-iframe', ['label' => '外部嵌入', 'fields' => [$url], 'defaults' => ['url' => ''], 'rules' => ['url' => ['required', 'url', 'max:2000']], 'render' => function ($values) {
            $host = strtolower(parse_url($values['url'], PHP_URL_HOST) ?? '');
            $allowed = app(SiteSettings::class)->get('media.iframe_hosts', 'player.bilibili.com');
            $hosts = preg_split('/[\s,]+/', (string) $allowed, -1, PREG_SPLIT_NO_EMPTY);
            if (parse_url($values['url'], PHP_URL_SCHEME) !== 'https' || ! in_array($host, $hosts, true)) {
                return '<p>此嵌入来源未获允许</p>';
            }

            return '<iframe src="'.e($values['url']).'" width="100%" height="420"></iframe>';
        }]);
    }
}
