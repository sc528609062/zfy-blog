<?php

namespace App\Services;

use App\Models\Content;
use Illuminate\Support\Str;
use Mews\Purifier\Facades\Purifier;

class ContentMarkdownRenderer
{
    private const DEFAULT_TIME_FORMAT = 'YYYY-MM-DD HH:mm:ss';

    private const TIME_FORMATS = [
        'YYYY-MM-DD HH:mm:ss',
        'YYYY-MM-DD HH:mm',
        'YYYY-MM-DD',
        'YYYY年MM月DD日 HH:mm:ss',
        'MM月DD日 HH:mm:ss',
        'HH:mm:ss',
    ];

    private const CLOUD_TYPES = [
        'default' => ['label' => '默认网盘', 'icon' => 'default'],
        '360' => ['label' => '360 网盘', 'icon' => '360'],
        'bd' => ['label' => '百度网盘', 'icon' => 'baidu'],
        'ty' => ['label' => '天翼网盘', 'icon' => 'tianyi'],
        'ct' => ['label' => '城通网盘', 'icon' => 'chengtong'],
        'wy' => ['label' => '微云网盘', 'icon' => 'weiyun'],
        'github' => ['label' => 'GitHub 仓库', 'icon' => 'github'],
        'lz' => ['label' => '蓝奏云网盘', 'icon' => 'lanzou'],
    ];

    private const SHORTCODE_NAMES = [
        'zfy-alert',
        'zfy-callout',
        'zfy-quote',
        'zfy-mtitle',
        'zfy-dplayer',
        'zfy-bilibili',
        'zfy-music-list',
        'zfy-music',
        'zfy-mp3',
        'zfy-button',
        'zfy-abtn',
        'zfy-anote',
        'zfy-dotted',
        'zfy-hide',
        'zfy-card-default',
        'zfy-card-list',
        'zfy-card-list-item',
        'zfy-message',
        'zfy-progress',
        'zfy-tabs',
        'zfy-tabs-pane',
        'zfy-tab',
        'zfy-collapse',
        'zfy-collapse-item',
        'zfy-timeline',
        'zfy-timeline-item',
        'zfy-grid',
        'zfy-grid-item',
        'zfy-copy',
        'zfy-card-describe',
        'zfy-lamp',
        'zfy-cloud',
        'zfy-html',
        'zfy-hr',
        'zfy-time',
        'alert',
        'callout',
        'quote',
        'mtitle',
        'dplayer',
        'bilibili',
        'music-list',
        'music',
        'mlist',
        'mp3',
        'button',
        'abtn',
        'anote',
        'dotted',
        'hide',
        'card-default',
        'card-list',
        'card-list-item',
        'message',
        'progress',
        'tabs',
        'tabs-pane',
        'tab',
        'collapse',
        'collapse-item',
        'timeline',
        'timeline-item',
        'grid',
        'grid-item',
        'gird',
        'gird-item',
        'copy',
        'card-describe',
        'lamp',
        'cloud',
        'html',
        'hr',
        'time',
    ];

    private const SHORTCODE_ALIASES = [
        'alert' => 'zfy-alert',
        'callout' => 'zfy-callout',
        'quote' => 'zfy-quote',
        'mtitle' => 'zfy-mtitle',
        'dplayer' => 'zfy-dplayer',
        'bilibili' => 'zfy-bilibili',
        'music-list' => 'zfy-music-list',
        'mlist' => 'zfy-music-list',
        'music' => 'zfy-music',
        'mp3' => 'zfy-mp3',
        'button' => 'zfy-button',
        'abtn' => 'zfy-abtn',
        'anote' => 'zfy-anote',
        'dotted' => 'zfy-dotted',
        'hide' => 'zfy-hide',
        'card-default' => 'zfy-card-default',
        'card-list' => 'zfy-card-list',
        'card-list-item' => 'zfy-card-list-item',
        'message' => 'zfy-message',
        'progress' => 'zfy-progress',
        'tabs' => 'zfy-tabs',
        'tabs-pane' => 'zfy-tabs-pane',
        'tab' => 'zfy-tab',
        'collapse' => 'zfy-collapse',
        'collapse-item' => 'zfy-collapse-item',
        'timeline' => 'zfy-timeline',
        'timeline-item' => 'zfy-timeline-item',
        'grid' => 'zfy-grid',
        'grid-item' => 'zfy-grid-item',
        'gird' => 'zfy-grid',
        'gird-item' => 'zfy-grid-item',
        'copy' => 'zfy-copy',
        'card-describe' => 'zfy-card-describe',
        'lamp' => 'zfy-lamp',
        'cloud' => 'zfy-cloud',
        'html' => 'zfy-html',
        'hr' => 'zfy-hr',
        'time' => 'zfy-time',
    ];

    private const EMOJI_MAP = [
        ':smile:' => '😄',
        ':rocket:' => '🚀',
        ':sparkles:' => '✨',
    ];

    /**
     * @return array<int, string>
     */
    public function extractShortcodes(string $markdown): array
    {
        preg_match_all($this->shortcodeTokenPattern(), $markdown, $matches);

        return collect($matches['name'] ?? [])
            ->map(fn (string $name) => $this->normalizeShortcodeName($name))
            ->unique()
            ->values()
            ->all();
    }

    public function containsRawHtmlMarkup(string $markdown): bool
    {
        return preg_match('/<(?!!--)(?:\/?[a-z][a-z0-9:-]*)(?:\s[^>]*)?>/i', $markdown) === 1;
    }

    public function containsMarkedHtmlBlock(string $markdown): bool
    {
        return preg_match('/\{(?:zfy-html|html)(?:\s[^}]*)?\}[\s\S]*?\{\/\s*(?:zfy-html|html)\s*\}/i', $markdown) === 1;
    }

    public function render(string $markdown, bool $allowRawHtml = false): string
    {
        $context = ['allow_raw_html' => $allowRawHtml];
        $markdown = (string) zfy_apply('zfy_markdown_before_render', $markdown, $context);
        $markdown = $this->normalizeMarkdown($markdown);
        $markdown = $this->replaceEmojiCodes($markdown);

        $shortcodes = [];
        $markdown = $this->extractPairedShortcodes($markdown, $shortcodes, $allowRawHtml);
        $markdown = $this->extractSingleShortcodes($markdown, $shortcodes);
        $markdown = $this->escapeUnmarkedRawHtmlLines($markdown);

        $html = Str::markdown($markdown, $this->markdownOptions($allowRawHtml));

        foreach ($shortcodes as $placeholder => $replacement) {
            $html = str_replace('<p>'.$placeholder.'</p>', $replacement, $html);
            $html = str_replace($placeholder, $replacement, $html);
        }

        $html = $this->renderEnlighterCodeBlocks($html);
        $html = $this->replaceTaskListInputs($html);
        $html = $this->cleanupRenderedHtml($html);
        $html = Purifier::clean($html);
        $html = $this->cleanupRenderedHtml($html);
        $html = $this->restoreDottedStyles($html);
        $html = $this->restoreInlineSvgIcons($html);
        $html = $this->renderMarkdownBlockquotes($html);
        $html = $this->applyQuoteStyleVariables($html);
        $html = $this->normalizeMediaAssetUrls($html);

        return $this->restoreEnlighterCodeBlocks((string) zfy_apply('zfy_rendered_html', $html, $markdown, $context));
    }

    public function renderContent(Content $content, bool $persist = false, bool $allowRawHtml = true): string
    {
        $html = $this->renderCachedContent($content->markdown_cache, $content->rendered_html, $allowRawHtml);

        if ($html !== (string) ($content->rendered_html ?? '')) {
            $content->rendered_html = $html;

            if ($persist && $content->exists) {
                $content->saveQuietly();
            }
        }

        return $html;
    }

    public function renderCachedContent(?string $markdown, ?string $html, bool $allowRawHtml = true): string
    {
        $markdown = trim((string) $markdown);
        $html = (string) ($html ?? '');

        if ($markdown !== '' && ($this->containsMarkedHtmlBlock($markdown) || $this->containsRawHtmlMarkup($markdown) || $this->shouldRefreshCachedHtml($html))) {
            return $this->render($markdown, $allowRawHtml);
        }

        return $html;
    }

    public function shouldRefreshCachedHtml(?string $html): bool
    {
        $html = (string) ($html ?? '');
        $lowerHtml = Str::lower($html);

        if (trim($html) === '') {
            return true;
        }

        return preg_match($this->shortcodeTokenPattern(), $html) === 1
            || Str::contains($lowerHtml, ['<blockquote', 'zfy-shortcode-quote zfy-quote quote_q', '/storage/media/'])
            || $this->containsStaleCloudMarkup($lowerHtml)
            || $this->containsStaleAudioMarkup($lowerHtml)
            || $this->containsStaleJoeElementMarkup($lowerHtml)
            || $this->containsStaleJoeClassMarkup($lowerHtml)
            || $this->containsStaleNeteaseMarkup($lowerHtml)
            || preg_match('/<pre\b(?![^>]*\bwp-block-zibllblock-enlighter\b)[^>]*>\s*<code\b/i', $html) === 1;
    }

    private function containsStaleJoeElementMarkup(string $html): bool
    {
        if (! Str::contains($html, '<joe-')) {
            return false;
        }

        return ! Str::contains($html, ['<joe-mlist', '<joe-mp3', '<joe-music']);
    }

    private function containsStaleJoeClassMarkup(string $html): bool
    {
        if (! Str::contains($html, 'joe_')) {
            return false;
        }

        if (Str::contains($html, 'joe_dotted')) {
            return ! Str::contains($html, 'background-image: repeating-linear-gradient(90deg');
        }

        return true;
    }

    private function containsStaleNeteaseMarkup(string $html): bool
    {
        if (Str::contains($html, ['zfy-shortcode-netease-music-list', 'zfy-shortcode-netease-music', 'zfy-netease-frame'])) {
            return true;
        }

        if (Str::contains($html, 'zfy-shortcode-media-music-list')) {
            return true;
        }

        if (Str::contains($html, 'zfy-shortcode-media-music')) {
            return true;
        }

        return false;
    }

    private function containsStaleCloudMarkup(string $html): bool
    {
        if (! Str::contains($html, 'zfy-shortcode-cloud')) {
            return false;
        }

        if (! Str::contains($html, 'zfy-cloud-provider-')
            || Str::contains($html, '<span class="zfy-cloud-password">')
            || Str::contains($html, 'zfy-cloud-copy-label')) {
            return true;
        }

        return Str::contains($html, 'zfy-cloud-properties')
            && preg_match('/<\/div>\s*<\/div>\s*<div class="zfy-cloud-properties">/', $html) !== 1;
    }

    private function containsStaleAudioMarkup(string $html): bool
    {
        return Str::contains($html, 'zfy-shortcode-audio')
            && ! Str::contains($html, 'zfy-audio-content');
    }

    /**
     * @param  array<string, string>  $shortcodes
     */
    private function extractPairedShortcodes(string $markdown, array &$shortcodes, bool $allowRawHtml): string
    {
        return preg_replace_callback(
            $this->pairedShortcodePattern(),
            function (array $matches) use (&$shortcodes, $allowRawHtml): string {
                $name = $this->normalizeShortcodeName($matches['name']);

                if ($name === 'zfy-html' && ! $allowRawHtml) {
                    return $matches[0];
                }

                $placeholder = $this->placeholder(count($shortcodes));
                $attributes = $this->normalizeAttributes($this->parseAttributes($matches['attributes'] ?? ''));
                $inner = trim($matches['body'] ?? '');
                $innerHtml = $this->renderMarkdownFragment($inner, $allowRawHtml);

                $shortcodes[$placeholder] = $this->renderPairedShortcode($name, $attributes, $innerHtml, $inner, $allowRawHtml);

                return "\n\n{$placeholder}\n\n";
            },
            $markdown
        ) ?? $markdown;
    }

    /**
     * @param  array<string, string>  $shortcodes
     */
    private function extractSingleShortcodes(string $markdown, array &$shortcodes): string
    {
        return preg_replace_callback(
            $this->singleShortcodePattern(),
            function (array $matches) use (&$shortcodes): string {
                $placeholder = $this->placeholder(count($shortcodes));
                $name = $this->normalizeShortcodeName($matches['name']);
                $attributes = $this->normalizeAttributes($this->parseAttributes($matches['attributes'] ?? ''));

                $shortcodes[$placeholder] = $this->renderSingleShortcode($name, $attributes);

                return "\n\n{$placeholder}\n\n";
            },
            $markdown
        ) ?? $markdown;
    }

    /**
     * @return array<string, string>
     */
    private function parseAttributes(string $source): array
    {
        preg_match_all(
            '/([a-zA-Z0-9_-]+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s]+))/',
            $source,
            $matches,
            PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL
        );

        $attributes = [];
        foreach ($matches as $match) {
            $attributes[strtolower($match[1])] = (string) ($match[2] ?? $match[3] ?? $match[4] ?? '');
        }

        $remaining = preg_replace('/([a-zA-Z0-9_-]+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s]+))/', ' ', $source) ?? $source;
        preg_match_all('/(?:^|\s)([a-zA-Z0-9_-]+)(?=\s|$)/', $remaining, $booleanMatches);

        foreach ($booleanMatches[1] ?? [] as $name) {
            $key = strtolower($name);
            $attributes[$key] ??= 'true';
        }

        return $attributes;
    }

    /**
     * @return array<string, string>
     */
    private function normalizeAttributes(array $attributes): array
    {
        $normalized = [];

        foreach ($attributes as $key => $value) {
            $normalized[strtolower($key)] = (string) $value;
        }

        foreach ([
            'showText' => 'showtext',
            'show_text' => 'showtext',
            'copyText' => 'copytext',
            'copy_text' => 'copytext',
            'iconSvg' => 'iconsvg',
            'icon_svg' => 'iconsvg',
            'startColor' => 'startcolor',
            'start_color' => 'startcolor',
            'endColor' => 'endcolor',
            'end_color' => 'endcolor',
            'percentage' => 'value',
            'percent' => 'value',
            'label' => 'title',
            'content' => 'title',
            'href' => 'url',
            'src' => 'url',
            'name' => 'title',
            'theme' => 'color',
        ] as $source => $target) {
            $sourceKey = strtolower($source);
            if (array_key_exists($sourceKey, $normalized) && ! array_key_exists($target, $normalized)) {
                $normalized[$target] = $normalized[$sourceKey];
            }
        }

        return $normalized;
    }

    private function normalizeShortcodeName(string $name): string
    {
        $name = strtolower(trim($name));
        if (Str::startsWith($name, 'zfy-')) {
            $base = Str::after($name, 'zfy-');

            return self::SHORTCODE_ALIASES[$base] ?? $name;
        }

        return self::SHORTCODE_ALIASES[$name] ?? 'zfy-'.$name;
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function renderPairedShortcode(string $name, array $attributes, string $innerHtml, string $innerMarkdown, bool $allowRawHtml): string
    {
        $type = Str::after($name, 'zfy-');
        $tone = $this->safeToken($attributes['type'] ?? $attributes['tone'] ?? 'info');
        $title = trim($attributes['title'] ?? '');
        $titleHtml = $title !== '' ? '<div class="zfy-shortcode-title">'.e($title).'</div>' : '';

        return match ($name) {
            'zfy-html' => $this->renderMarkedHtmlBlock($innerMarkdown),
            'zfy-alert' => $this->renderAlert($attributes, $innerHtml, $tone, $titleHtml),
            'zfy-callout' => $this->renderCallout($attributes, $innerHtml, $tone, $titleHtml),
            'zfy-quote' => $this->renderQuote($attributes, $innerHtml),
            'zfy-mtitle' => $this->renderMtitle($attributes, $innerHtml),
            'zfy-card-default' => $this->renderCardDefault($attributes, $innerHtml),
            'zfy-card-list' => $this->renderCardList($innerMarkdown, $innerHtml, $allowRawHtml),
            'zfy-card-describe' => $this->renderCardDescribe($attributes, $innerHtml),
            'zfy-message' => $this->renderMessage($attributes, $innerHtml, $tone),
            'zfy-collapse' => $this->renderCollapse($attributes, $innerMarkdown, $innerHtml, $allowRawHtml),
            'zfy-tabs' => $this->renderTabs($attributes, $innerMarkdown, $innerHtml, $allowRawHtml),
            'zfy-timeline' => $this->renderTimeline($innerMarkdown, $innerHtml, $allowRawHtml),
            'zfy-copy' => $this->renderCopy($attributes, $innerHtml),
            'zfy-grid' => $this->renderGrid($attributes, $innerMarkdown, $innerHtml, $allowRawHtml),
            'zfy-hide' => $this->renderHide($attributes),
            'zfy-dotted' => $this->renderDotted($attributes),
            default => $this->wrapShortcode($this->safeToken($type), $tone, $titleHtml.$this->bodyHtml($innerHtml)),
        };
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function renderSingleShortcode(string $name, array $attributes): string
    {
        $type = Str::after($name, 'zfy-');

        return match ($name) {
            'zfy-hr' => '<hr class="zfy-shortcode-rule">',
            'zfy-time' => $this->renderTime($attributes),
            'zfy-mtitle' => $this->renderMtitle($attributes, ''),
            'zfy-message' => $this->renderMessage($attributes, '<p>'.e($attributes['content'] ?? $attributes['title'] ?? '消息内容').'</p>', $this->safeToken($attributes['type'] ?? 'success')),
            'zfy-progress' => $this->renderProgress($attributes),
            'zfy-bilibili', 'zfy-dplayer', 'zfy-mp3', 'zfy-music', 'zfy-music-list' => $this->renderMedia($name, $attributes),
            'zfy-cloud' => $this->renderCloud($attributes),
            'zfy-button', 'zfy-abtn' => $this->renderButton($attributes),
            'zfy-anote' => $this->renderNoteButton($attributes),
            'zfy-dotted' => $this->renderDotted($attributes),
            'zfy-copy' => $this->renderCopySingle($attributes),
            'zfy-lamp' => $this->renderLamp($attributes),
            default => '<div class="zfy-shortcode zfy-shortcode-'.$this->safeToken($type).'"><span>'.e($attributes['title'] ?? $attributes['label'] ?? $type).'</span></div>',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function markdownOptions(bool $allowRawHtml): array
    {
        return [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'renderer' => [
                'soft_break' => "<br>\n",
            ],
        ];
    }

    private function renderMarkdownFragment(string $markdown, bool $allowRawHtml): string
    {
        return Str::markdown($this->replaceEmojiCodes($this->normalizeMarkdown($markdown)), $this->markdownOptions($allowRawHtml));
    }

    private function renderMarkedHtmlBlock(string $html): string
    {
        return trim($this->normalizeMarkdown($html));
    }

    private function escapeUnmarkedRawHtmlLines(string $markdown): string
    {
        $lines = explode("\n", $markdown);
        $inFence = false;
        $fenceMarker = '';

        foreach ($lines as $index => $line) {
            if (preg_match('/^\s*(```|~~~)/', $line, $matches) === 1) {
                $marker = $matches[1];

                if (! $inFence) {
                    $inFence = true;
                    $fenceMarker = $marker;
                } elseif ($marker === $fenceMarker) {
                    $inFence = false;
                    $fenceMarker = '';
                }

                continue;
            }

            if ($inFence || ! $this->containsRawHtmlMarkup($line)) {
                continue;
            }

            $line = htmlspecialchars($line, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');

            if (isset($lines[$index + 1]) && trim($lines[$index + 1]) !== '') {
                $line .= '  ';
            }

            $lines[$index] = $line;
        }

        return implode("\n", $lines);
    }

    private function normalizeMarkdown(string $markdown): string
    {
        return str_replace(["\r\n", "\r", "\u{3000}"], ["\n", "\n", '&emsp;'], $markdown);
    }

    private function replaceEmojiCodes(string $markdown): string
    {
        return strtr($markdown, self::EMOJI_MAP);
    }

    private function replaceTaskListInputs(string $html): string
    {
        return preg_replace_callback(
            '/<input\b(?=[^>]*type=["\']?checkbox["\']?)[^>]*>/i',
            fn (array $matches): string => str_contains(strtolower($matches[0]), 'checked')
                ? '<span class="zfy-taskbox is-checked"></span>'
                : '<span class="zfy-taskbox"></span>',
            $html
        ) ?? $html;
    }

    private function cleanupRenderedHtml(string $html): string
    {
        $html = preg_replace('/<p>\s*<\/p>/i', '', $html) ?? $html;
        $html = preg_replace('/<\/(blockquote|ul|ol|pre|table)>\s*<br\s*\/?>/i', '</$1>', $html) ?? $html;
        $html = preg_replace('/(?:<br\s*\/?>\s*){2,}(?=<(?:blockquote|ul|ol|pre|table|hr|div)\b)/i', '', $html) ?? $html;

        return trim($html);
    }

    private function renderEnlighterCodeBlocks(string $html): string
    {
        return preg_replace_callback(
            '/<pre(?<preAttributes>[^>]*)>\s*<code(?<codeAttributes>[^>]*)>(?<code>[\s\S]*?)<\/code>\s*<\/pre>/i',
            function (array $matches): string {
                $language = $this->enlighterLanguageFromAttributes(($matches['preAttributes'] ?? '').' '.($matches['codeAttributes'] ?? ''));
                $rawCode = html_entity_decode($matches['code'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');

                return $this->renderEnlighterCodeBlock($rawCode, $language);
            },
            $html
        ) ?? $html;
    }

    private function renderEnlighterCodeBlock(string $rawCode, string $language): string
    {
        $language = $this->safeToken($language ?: 'generic');
        $escapedRaw = $this->escapeCodeText($rawCode);
        $lines = $this->enlighterLinesHtml($rawCode);

        return '<pre class="wp-block-zibllblock-enlighter">'
            .'<div class="enlighter-default enlighter-v-standard enlighter-t-enlighter enlighter-hover enlighter-linenumbers enlighter-overflow-scroll">'
            .'<div class="enlighter-toolbar"><div class="enlighter-btn enlighter-btn-raw"></div><div class="enlighter-btn enlighter-btn-copy"></div><div class="enlighter-btn enlighter-btn-window"></div></div>'
            .'<div class="enlighter" style="">'.$lines.'</div>'
            .'<pre class="enlighter-raw">'.$escapedRaw.'</pre>'
            .'</div>'
            .'<code class="gl enlighter-origin" data-enlighter-language="'.e($language).'" data-enlighter-theme="" data-enlighter-highlight="" data-enlighter-linenumbers="" data-enlighter-lineoffset="" data-enlighter-title="" data-enlighter-group="">'.$escapedRaw.'</code>'
            .'</pre>';
    }

    private function enlighterLanguageFromAttributes(string $attributes): string
    {
        if (preg_match('/\blanguage-([a-zA-Z0-9_+-]+)/', $attributes, $match)) {
            return $this->safeToken($match[1]);
        }

        if (preg_match('/\blang(?:uage)?-([a-zA-Z0-9_+-]+)/', $attributes, $match)) {
            return $this->safeToken($match[1]);
        }

        return 'generic';
    }

    private function enlighterLinesHtml(string $rawCode): string
    {
        $visualCode = rtrim(str_replace(["\r\n", "\r"], "\n", $rawCode), "\n");
        $lines = $visualCode === '' ? [''] : explode("\n", $visualCode);

        return collect($lines)
            ->map(fn (string $line): string => '<div class=""><div>'.$this->enlighterSyntaxHtml($line).'</div></div>')
            ->implode('');
    }

    private function restoreEnlighterCodeBlocks(string $html): string
    {
        return preg_replace_callback(
            '/<pre class="wp-block-zibllblock-enlighter"><\/pre>(<div class="enlighter-default[\s\S]*?<\/code>)/i',
            static function (array $matches): string {
                $block = preg_replace('/<div class="enlighter"(?![^>]*style=)/i', '<div class="enlighter" style=""', $matches[1], 1) ?? $matches[1];
                $block = str_replace('<div><div><span', '<div class=""><div><span', $block);

                return '<pre class="wp-block-zibllblock-enlighter">'.$block.'</pre>';
            },
            $html
        ) ?? $html;
    }

    private function enlighterSyntaxHtml(string $line): string
    {
        if ($line === '') {
            return '<span class="enlighter-text"></span>';
        }

        $pattern = '~(?<comment>(?<![A-Za-z0-9:/])//[^\n]*|/\*[\s\S]*?\*/)|(?<string>"(?:\\\\.|[^"\\\\])*"|\'(?:\\\\.|[^\'\\\\])*\')|(?<selector>\.[A-Za-z_][A-Za-z0-9_-]*)|(?<keyword>\bclass\b)|(?<hex>#[0-9a-fA-F]{3,8}\b)|(?<number>\b0\d+\b|\b\d+(?:\.\d+)?\b)|(?<brace>[<>{}])~i';
        preg_match_all($pattern, $line, $matches, PREG_OFFSET_CAPTURE);

        $html = '';
        $offset = 0;
        $buffer = '';

        foreach ($matches[0] ?? [] as [$token, $position]) {
            if ($position > $offset) {
                $buffer .= substr($line, $offset, $position - $offset);
            }

            $class = $this->enlighterTokenClass($token);
            if ($class === 'enlighter-m3' && str_starts_with($token, '.') && ! $this->isSelectorHighlightable($line, $position)) {
                $buffer .= $token;
                $offset = $position + strlen($token);

                continue;
            }

            if ($buffer !== '') {
                $html .= '<span class="enlighter-text">'.$this->escapeCodeText($buffer).'</span>';
                $buffer = '';
            }

            if ($class === 'enlighter-m3' && str_starts_with($token, '.')) {
                $html .= '<span class="enlighter-text">.</span>';
                $html .= '<span class="enlighter-m3">'.$this->escapeCodeText(substr($token, 1)).'</span>';
            } else {
                $html .= '<span class="'.$class.'">'.$this->escapeCodeText($token).'</span>';
            }
            $offset = $position + strlen($token);
        }

        if ($offset < strlen($line)) {
            $buffer .= substr($line, $offset);
        }

        if ($buffer !== '') {
            $html .= '<span class="enlighter-text">'.$this->escapeCodeText($buffer).'</span>';
        }

        return $html;
    }

    private function enlighterTokenClass(string $token): string
    {
        if (str_starts_with($token, '//') || str_starts_with($token, '/*')) {
            return 'enlighter-c0';
        }

        if (preg_match('/^(?:"|\')/', $token)) {
            return 'enlighter-s0';
        }

        if (str_starts_with($token, '.')) {
            return 'enlighter-m3';
        }

        if ($token === 'class') {
            return 'enlighter-k1';
        }

        if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $token)) {
            return 'enlighter-c0';
        }

        if (preg_match('/^\d/', $token)) {
            if (preg_match('/^0\d+$/', $token)) {
                return 'enlighter-n4';
            }

            return 'enlighter-n1';
        }

        if (preg_match('/^(?:<|>|{|})$/', $token)) {
            return 'enlighter-g1';
        }

        return 'enlighter-text';
    }

    private function isSelectorHighlightable(string $line, int $position): bool
    {
        if ($position <= 0) {
            return false;
        }

        $previous = $line[$position - 1] ?? '';

        return $previous !== '' && (ctype_alnum($previous) || in_array($previous, [')', ']', '}'], true));
    }

    private function escapeCodeText(string $value): string
    {
        return htmlspecialchars($value, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function wrapShortcode(string $name, string $tone, string $html): string
    {
        return '<div class="zfy-shortcode zfy-shortcode-'.$this->safeToken($name).' zfy-shortcode-tone-'.$this->safeToken($tone).'">'.$html.'</div>';
    }

    private function renderMarkdownBlockquotes(string $html): string
    {
        return preg_replace_callback(
            '/<blockquote(?<attributes>[^>]*)>(?<body>[\s\S]*?)<\/blockquote>/i',
            function (array $matches): string {
                $attributes = [];
                if (preg_match('/\bdata-color=(["\'])(#[0-9a-fA-F]{3,8})\1/i', $matches['attributes'] ?? '', $colorMatch)) {
                    $attributes['color'] = $colorMatch[2];
                }

                return $this->renderQuote($attributes, trim($matches['body'] ?? ''), true);
            },
            $html
        ) ?? $html;
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function renderQuote(array $attributes, string $innerHtml, bool $allowInlineColorMarker = false): string
    {
        $color = $this->safeCssColor($attributes['color'] ?? $attributes['data-color'] ?? '') ?: '#af870d';
        $innerHtml = trim($innerHtml);

        if ($allowInlineColorMarker) {
            [$color, $innerHtml] = $this->extractQuoteInlineColor($innerHtml, $color);
        }

        return '<div class="quote_q zfy-shortcode zfy-shortcode-quote zfy-quote" data-color="'.e($color).'"><i class="fa fa-quote-left"></i>'.$innerHtml.'</div>';
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function extractQuoteInlineColor(string $html, string $fallbackColor): array
    {
        $color = $fallbackColor;
        $patterns = [
            '/(<p[^>]*>\s*)\[color=(#[0-9a-fA-F]{3,8})\]\s*/i',
            '/(<p[^>]*>\s*)\{color=(?:"|\')?(#[0-9a-fA-F]{3,8})(?:"|\')?\}\s*/i',
        ];

        foreach ($patterns as $pattern) {
            $replaced = preg_replace_callback($pattern, function (array $matches) use (&$color): string {
                $color = $this->safeCssColor($matches[2] ?? '') ?: $color;

                return $matches[1];
            }, $html, 1, $count);

            if ($count > 0) {
                return [$color, $replaced ?? $html];
            }
        }

        return [$color, $html];
    }

    private function applyQuoteStyleVariables(string $html): string
    {
        return preg_replace_callback(
            '/<div\b(?<attributes>[^>]*\bzfy-quote\b[^>]*)>/i',
            function (array $matches): string {
                $attributes = $matches['attributes'] ?? '';
                if (! preg_match('/\bdata-color=(["\'])(#[0-9a-fA-F]{3,8})\1/i', $attributes, $colorMatch)) {
                    return $matches[0];
                }

                $color = $this->safeCssColor($colorMatch[2] ?? '');
                if ($color === '') {
                    return $matches[0];
                }

                $style = '--quote-color:'.$color.';';
                if (preg_match('/\bstyle=(["\'])(.*?)\1/i', $attributes)) {
                    $attributes = preg_replace('/\bstyle=(["\'])(.*?)\1/i', 'style="$2 '.$style.'"', $attributes, 1) ?? $attributes;
                } else {
                    $attributes .= ' style="'.$style.'"';
                }

                return '<div'.$attributes.'>';
            },
            $html
        ) ?? $html;
    }

    private function normalizeMediaAssetUrls(string $html): string
    {
        return preg_replace_callback(
            '/\b(?<attribute>src|href)=(?<quote>["\'])(?<url>[^"\']*\/storage\/media\/[^"\']+)\k<quote>/i',
            function (array $matches): string {
                $url = html_entity_decode($matches['url'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $path = parse_url($url, PHP_URL_PATH) ?: $url;
                $position = stripos($path, '/storage/media/');

                if ($position === false) {
                    return $matches[0];
                }

                $relativePath = substr($path, $position + strlen('/storage/media/'));
                $relativePath = $this->normalizeMediaRelativePath($relativePath);
                if ($relativePath === '') {
                    return $matches[0];
                }

                $mediaRoot = trim((string) config('zfy.editor.media.storage_root', 'media'), '/');
                $normalizedUrl = url('/'.$mediaRoot.'/'.$relativePath);
                $query = parse_url($url, PHP_URL_QUERY);
                if (is_string($query) && $query !== '') {
                    $normalizedUrl .= '?'.$query;
                }

                return $matches['attribute'].'='.$matches['quote'].e($normalizedUrl).$matches['quote'];
            },
            $html
        ) ?? $html;
    }

    private function normalizeMediaRelativePath(string $path): string
    {
        $path = rawurldecode($path);
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('#\.\.+#', '', $path) ?? '';
        $path = preg_replace('#/+#', '/', $path) ?? '';

        return trim($path, '/');
    }

    private function bodyHtml(string $innerHtml): string
    {
        return '<div class="zfy-shortcode-body">'.$innerHtml.'</div>';
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function renderMtitle(array $attributes, string $innerHtml): string
    {
        $title = trim($attributes['title'] ?? $attributes['label'] ?? '小标题');
        $body = trim($innerHtml) !== '' ? $this->bodyHtml($innerHtml) : '';

        return '<div class="zfy-shortcode zfy-shortcode-mtitle"><span>'.e($title).'</span>'.$body.'</div>';
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function renderCardDefault(array $attributes, string $innerHtml): string
    {
        $title = trim($attributes['title'] ?? $attributes['label'] ?? '卡片标题');
        $width = $this->safeCssSize($attributes['width'] ?? '100%');
        $style = $width !== '' ? ' style="width: '.$width.';"' : '';

        return '<div class="zfy-shortcode zfy-shortcode-card-default"'.$style.'><div class="zfy-shortcode-title">'.e($title).'</div>'.$this->bodyHtml($innerHtml).'</div>';
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function renderCardDescribe(array $attributes, string $innerHtml): string
    {
        $title = trim($attributes['title'] ?? '卡片描述');

        return '<div class="zfy-shortcode zfy-shortcode-card-describe"><div class="zfy-shortcode-title">'.e($title).'</div>'.$this->bodyHtml($innerHtml).'</div>';
    }

    private function renderCardList(string $innerMarkdown, string $fallbackHtml, bool $allowRawHtml): string
    {
        $items = [];
        preg_match_all('/\{(?:zfy-card-list-item|card-list-item)([^}]*)\}([\s\S]*?)\{\/(?:zfy-card-list-item|card-list-item)\}/i', $innerMarkdown, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $items[] = $this->renderMarkdownFragment(trim($match[2] ?? ''), $allowRawHtml);
        }

        if ($items === []) {
            $items = $this->extractHtmlItems($fallbackHtml) ?: $this->extractParagraphs($fallbackHtml) ?: [$fallbackHtml];
        }

        $html = '';
        foreach ($items as $item) {
            $html .= '<div class="zfy-card-list-item">'.$item.'</div>';
        }

        return '<div class="zfy-shortcode zfy-shortcode-card-list">'.$html.'</div>';
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function renderAlert(array $attributes, string $innerHtml, string $tone, string $titleHtml): string
    {
        $color = $this->alertColorToken($attributes['color'] ?? $attributes['type'] ?? $attributes['tone'] ?? $tone);
        $customColor = $this->safeCssColor($attributes['color'] ?? '');
        $isCustomColor = $color === 'custom' && $customColor !== '';
        $colorClass = $isCustomColor ? 'jb-custom' : 'jb-'.$color;
        $style = $isCustomColor ? ' style="--zfy-alert-color: '.$customColor.';"' : '';
        $icon = $this->alertIconToken($attributes['icon'] ?? '', $color);
        $iconClass = $icon ?? 'custom';
        $iconHtml = match (true) {
            $icon === 'none' => '',
            $icon !== null => '<span class="zfy-alert-icon zfy-alert-icon-'.$icon.'" aria-hidden="true"></span>',
            default => $this->renderInlineIcon($attributes, 'zfy-alert-icon zfy-alert-icon-custom'),
        };

        return '<div class="wp-block-zibllblock-alert alert-dismissible fade in zfy-shortcode zfy-shortcode-alert zfy-alert-color-'.$color.' zfy-alert-icon-'.$iconClass.'"><div class="alert '.$colorClass.'" data-isclose="" role="alert"'.$style.'>'.$iconHtml.'<div class="zfy-alert-content">'.$titleHtml.$innerHtml.'</div></div></div>';
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function renderCallout(array $attributes, string $innerHtml, string $tone, string $titleHtml): string
    {
        $color = $this->safeCssColor($attributes['color'] ?? '');
        $style = $color !== '' ? ' style="border-color: '.$color.';"' : '';

        return '<div class="zfy-shortcode zfy-shortcode-callout zfy-shortcode-tone-'.$this->safeToken($tone).'"'.$style.'>'.$titleHtml.$this->bodyHtml($innerHtml).'</div>';
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function renderMessage(array $attributes, string $innerHtml, string $tone): string
    {
        $tone = $this->safeToken($attributes['type'] ?? $tone);

        return $this->wrapShortcode('message', $tone, '<span class="zfy-shortcode-mark"></span>'.$this->bodyHtml($innerHtml));
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function renderCollapse(array $attributes, string $innerMarkdown, string $fallbackHtml, bool $allowRawHtml): string
    {
        $items = [];
        preg_match_all('/\{(?:zfy-collapse-item|collapse-item)([^}]*)\}([\s\S]*?)\{\/(?:zfy-collapse-item|collapse-item)\}/i', $innerMarkdown, $matches, PREG_SET_ORDER);

        foreach ($matches as $index => $match) {
            $itemAttributes = $this->normalizeAttributes($this->parseAttributes($match[1] ?? ''));
            $items[] = [
                'title' => trim($itemAttributes['title'] ?? $itemAttributes['label'] ?? '折叠标题 '.($index + 1)),
                'open' => $this->truthy($itemAttributes['open'] ?? null) || ($index === 0 && ! array_key_exists('open', $itemAttributes)),
                'html' => $this->renderMarkdownFragment(trim($match[2] ?? ''), $allowRawHtml),
            ];
        }

        if ($items === []) {
            $items[] = [
                'title' => trim($attributes['title'] ?? $attributes['label'] ?? '展开内容'),
                'open' => true,
                'html' => $fallbackHtml,
            ];
        }

        $html = '';
        foreach ($items as $item) {
            $open = $item['open'] ? ' is-open' : '';
            $html .= '<div class="zfy-collapse-item'.$open.'"><div class="zfy-shortcode-title">'.e($item['title']).'<span></span></div>'.$this->bodyHtml($item['html']).'</div>';
        }

        return '<div class="zfy-shortcode zfy-shortcode-collapse">'.$html.'</div>';
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function renderTabs(array $attributes, string $innerMarkdown, string $fallbackHtml, bool $allowRawHtml): string
    {
        $tabs = [];
        preg_match_all('/\{(?:zfy-tabs-pane|zfy-tab|tabs-pane|tab)([^}]*)\}([\s\S]*?)\{\/(?:zfy-tabs-pane|zfy-tab|tabs-pane|tab)\}/i', $innerMarkdown, $matches, PREG_SET_ORDER);

        foreach ($matches as $index => $match) {
            $tabAttributes = $this->normalizeAttributes($this->parseAttributes($match[1] ?? ''));
            $tabs[] = [
                'title' => trim($tabAttributes['title'] ?? $tabAttributes['label'] ?? '标签 '.($index + 1)),
                'html' => $this->renderMarkdownFragment(trim($match[2] ?? ''), $allowRawHtml),
            ];
        }

        if ($tabs === []) {
            $tabs[] = [
                'title' => trim($attributes['title'] ?? $attributes['label'] ?? '内容'),
                'html' => $fallbackHtml,
            ];
        }

        $heads = '';
        $bodies = '';
        foreach ($tabs as $index => $tab) {
            $active = $index === 0 ? ' is-active' : '';
            $heads .= '<span class="zfy-tabs-head-item'.$active.'">'.e($tab['title']).'</span>';
            $bodies .= '<div class="zfy-tabs-body-item'.$active.'">'.$tab['html'].'</div>';
        }

        return '<div class="zfy-shortcode zfy-shortcode-tabs"><div class="zfy-tabs-head">'.$heads.'</div><div class="zfy-tabs-body">'.$bodies.'</div></div>';
    }

    private function renderTimeline(string $innerMarkdown, string $fallbackHtml, bool $allowRawHtml): string
    {
        $items = [];
        preg_match_all('/\{(?:zfy-timeline-item|timeline-item)([^}]*)\}([\s\S]*?)\{\/(?:zfy-timeline-item|timeline-item)\}/i', $innerMarkdown, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $itemAttributes = $this->normalizeAttributes($this->parseAttributes($match[1] ?? ''));
            $items[] = [
                'html' => $this->renderMarkdownFragment(trim($match[2] ?? ''), $allowRawHtml),
                'color' => $this->safeCssColor($itemAttributes['color'] ?? ''),
            ];
        }

        if ($items === []) {
            $items = collect($this->extractHtmlItems($fallbackHtml) ?: $this->extractParagraphs($fallbackHtml) ?: [$fallbackHtml])
                ->map(fn (string $item) => ['html' => $item, 'color' => ''])
                ->all();
        }

        $html = '';
        foreach ($items as $item) {
            $style = $item['color'] !== '' ? ' style="border-color: '.$item['color'].';"' : '';
            $html .= '<div class="zfy-timeline-item"><span class="zfy-timeline-dot"'.$style.'></span><div class="zfy-timeline-content">'.$item['html'].'</div></div>';
        }

        return '<div class="zfy-shortcode zfy-shortcode-timeline">'.$html.'</div>';
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function renderGrid(array $attributes, string $innerMarkdown, string $fallbackHtml, bool $allowRawHtml): string
    {
        $items = [];
        preg_match_all('/\{(?:zfy-grid-item|zfy-gird-item|grid-item|gird-item)([^}]*)\}([\s\S]*?)\{\/(?:zfy-grid-item|zfy-gird-item|grid-item|gird-item)\}/i', $innerMarkdown, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $items[] = $this->renderMarkdownFragment(trim($match[2] ?? ''), $allowRawHtml);
        }

        if ($items === []) {
            $items = $this->extractHtmlItems($fallbackHtml) ?: $this->extractParagraphs($fallbackHtml) ?: [$fallbackHtml];
        }

        $columns = max(1, min(4, (int) ($attributes['column'] ?? $attributes['columns'] ?? 0)));
        $columnClass = $columns > 1 ? ' zfy-grid-columns-'.$columns : '';
        $gap = max(0, min(48, (int) ($attributes['gap'] ?? 0)));
        $style = $gap > 0 ? ' style="gap: '.$gap.'px;"' : '';
        $html = '';
        foreach ($items as $item) {
            $html .= '<div class="zfy-grid-item">'.$item.'</div>';
        }

        return '<div class="zfy-shortcode zfy-shortcode-grid'.$columnClass.'"'.$style.'>'.$html.'</div>';
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function renderCopy(array $attributes, string $innerHtml): string
    {
        $title = trim($attributes['showtext'] ?? $attributes['title'] ?? $attributes['label'] ?? '复制内容');

        return '<div class="zfy-shortcode zfy-shortcode-copy"><div class="zfy-shortcode-title">'.e($title).'<span class="zfy-copy-trigger">复制</span></div><div class="zfy-copy-body">'.$innerHtml.'</div></div>';
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function renderCopySingle(array $attributes): string
    {
        $text = trim($attributes['copytext'] ?? $attributes['copy_text'] ?? $attributes['text'] ?? $attributes['showtext'] ?? $attributes['show_text'] ?? '');
        if ($text === '') {
            $text = trim($attributes['title'] ?? '可复制文本');
        }

        return $this->renderCopy($attributes, '<p>'.e($text).'</p>');
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function renderHide(array $attributes): string
    {
        $title = trim($attributes['title'] ?? $attributes['label'] ?? '登录后可见');

        return '<div class="zfy-shortcode zfy-shortcode-hide"><span>'.e($title).'</span><small>此处内容需要满足访问条件后查看</small></div>';
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function renderProgress(array $attributes): string
    {
        $value = max(0, min(100, (int) rtrim((string) ($attributes['value'] ?? $attributes['percentage'] ?? 60), '%')));
        $color = $this->safeCssColor($attributes['color'] ?? '');
        $style = 'width: '.$value.'%;'.($color !== '' ? ' background-color: '.$color.';' : '');

        return '<div class="zfy-shortcode zfy-shortcode-progress"><div class="zfy-progress-track"><div class="zfy-progress-bar" style="'.$style.'"></div></div><span>'.$value.'%</span></div>';
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function renderTime(array $attributes): string
    {
        $format = $this->safeTimeFormat($attributes['format'] ?? self::DEFAULT_TIME_FORMAT);

        return '<div class="zfy-shortcode zfy-shortcode-time" data-zfy-time-format="'.e($format).'">'.e($this->formatCurrentTime($format)).'</div>';
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function renderMedia(string $name, array $attributes): string
    {
        $type = $this->safeToken(Str::after($name, 'zfy-'));
        $labels = [
            'bilibili' => '视频',
            'dplayer' => '视频',
            'mp3' => '音频',
            'music' => '网易云单曲',
            'music-list' => '网易云歌单',
        ];
        $title = trim($attributes['title'] ?? $attributes['name'] ?? $labels[$type] ?? '媒体');
        $url = $this->safeHref($attributes['url'] ?? $attributes['src'] ?? '');

        if ($name === 'zfy-bilibili' && trim($attributes['bvid'] ?? '') !== '') {
            return $this->renderBilibili($attributes, $title);
        }

        if ($name === 'zfy-dplayer' && $url !== '#') {
            return '<div class="zfy-shortcode zfy-shortcode-media zfy-shortcode-media-dplayer zfy-shortcode-video"><div class="zfy-shortcode-title">'.e($title).'</div><video class="zfy-media-player" src="'.e($url).'" controls preload="metadata"></video></div>';
        }

        if ($name === 'zfy-mp3') {
            $cover = $this->safeHref($attributes['cover'] ?? '');
            $theme = $this->safeCssColor($attributes['theme'] ?? $attributes['color'] ?? '') ?: '#1989fa';
            $autoplay = $this->truthyAttribute($attributes['autoplay'] ?? $attributes['auto'] ?? '') ? ' autoplay="autoplay"' : '';
            $coverAttribute = $cover !== '#' ? ' cover="'.e($cover).'"' : '';
            $urlAttribute = $url !== '#' ? ' url="'.e($url).'"' : '';

            return '<joe-mp3 name="'.e($title).'"'.$urlAttribute.$coverAttribute.' theme="'.e($theme).'"'.$autoplay.'></joe-mp3>';
        }

        if (in_array($name, ['zfy-music', 'zfy-music-list'], true)) {
            $id = $this->neteaseMusicId($attributes);
            $meta = $id !== '' ? '网易云 ID：'.$id : ($url === '#' ? '未填写地址' : $url);

            if ($id !== '') {
                return $this->renderJoeMusicElement($type, $id, $attributes);
            }

            return '<div class="zfy-shortcode zfy-shortcode-media zfy-shortcode-media-'.$type.'"><span class="zfy-media-icon">'.$this->mediaIcon($type).'</span><div><div class="zfy-shortcode-title">'.e($title).'</div><small>'.e($meta).'</small></div>'.($url !== '#' ? '<a href="'.e($url).'" target="_blank" rel="noopener noreferrer nofollow">打开</a>' : '').'</div>';
        }

        return '<div class="zfy-shortcode zfy-shortcode-media zfy-shortcode-media-'.$type.'"><span class="zfy-media-icon">'.$this->mediaIcon($type).'</span><div><div class="zfy-shortcode-title">'.e($title).'</div><small>'.e($url === '#' ? '未填写地址' : $url).'</small></div><a href="'.e($url).'" target="_blank" rel="noopener noreferrer nofollow">打开</a></div>';
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function renderJoeMusicElement(string $type, string $id, array $attributes): string
    {
        $tag = $type === 'music-list' ? 'joe-mlist' : 'joe-music';
        $color = $this->safeCssColor($attributes['color'] ?? '') ?: '#1989fa';
        $autoplay = $this->truthyAttribute($attributes['autoplay'] ?? $attributes['auto'] ?? '') ? ' autoplay="autoplay"' : '';

        return '<'.$tag.' id="'.e($id).'" color="'.e($color).'"'.$autoplay.'></'.$tag.'>';
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function neteaseMusicId(array $attributes): string
    {
        $source = trim($attributes['id'] ?? '');

        if ($source === '') {
            $source = trim($attributes['url'] ?? $attributes['src'] ?? '');
        }

        if ($source === '') {
            return '';
        }

        if (preg_match('/(?:id=|playlist\/|song\/)(\d+)/i', $source, $matches) === 1) {
            return $matches[1];
        }

        return preg_match('/^\d+$/', $source) === 1 ? $source : '';
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function renderBilibili(array $attributes, string $title): string
    {
        $bvid = preg_replace('/[^a-zA-Z0-9]/', '', $attributes['bvid'] ?? '') ?: '';
        $page = max(1, (int) ($attributes['page'] ?? 1));

        if ($bvid === '') {
            return $this->renderMedia('zfy-bilibili', ['title' => $title, 'url' => $attributes['url'] ?? '']);
        }

        $src = 'https://player.bilibili.com/player.html?bvid='.rawurlencode($bvid).'&page='.$page.'&high_quality=1';

        return '<div class="zfy-shortcode zfy-shortcode-bilibili"><div class="zfy-shortcode-title">'.e($title).'</div><iframe class="zfy-bilibili-frame" src="'.e($src).'" width="100%" height="420"></iframe></div>';
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function renderCloud(array $attributes): string
    {
        $title = trim($attributes['title'] ?? '下载资源') ?: '下载资源';
        $url = $this->safeHref($attributes['url'] ?? '');
        $type = $this->cloudTypeKey($attributes['type'] ?? 'default');
        $provider = self::CLOUD_TYPES[$type];
        $password = trim($attributes['password'] ?? $attributes['code'] ?? '');
        $passwordHtml = $password !== ''
            ? '<button type="button" class="zfy-cloud-password" data-copy-text="'.e($password).'" aria-label="复制提取码 '.e($password).'" title="点击复制提取码"><span>提取码</span><code>'.e($password).'</code></button>'
            : '<span class="zfy-cloud-password is-empty">无需提取码</span>';
        $properties = $this->cloudProperties($attributes);
        $propertiesHtml = $properties === []
            ? ''
            : '<div class="zfy-cloud-properties">'.implode('', array_map(
                fn (array $property): string => '<div class="zfy-cloud-property"><span class="zfy-cloud-property-name">'.e($property['name']).'</span><span class="zfy-cloud-property-value">'.e($property['value']).'</span></div>',
                $properties
            )).'</div>';
        $icon = '/assets/zfy/cloud/'.$provider['icon'].'.svg';

        return '<div class="zfy-shortcode zfy-shortcode-cloud zfy-cloud-provider-'.$type.'"><span class="zfy-cloud-logo" aria-hidden="true"><img src="'.e($icon).'" alt="" width="28" height="28"></span><div class="zfy-cloud-content"><div class="zfy-shortcode-title">'.e($title).'</div><div class="zfy-cloud-meta"><span class="zfy-cloud-provider">'.e($provider['label']).'</span>'.$passwordHtml.'</div></div>'.$propertiesHtml.'<a class="zfy-cloud-download" href="'.e($url).'" target="_blank" rel="noopener noreferrer nofollow"><span>立即下载</span><span class="zfy-cloud-download-arrow" aria-hidden="true">→</span></a></div>';
    }

    /**
     * @param  array<string, string>  $attributes
     * @return array<int, array{name: string, value: string}>
     */
    private function cloudProperties(array $attributes): array
    {
        $encoded = trim($attributes['attributes'] ?? $attributes['attrs'] ?? '');
        if ($encoded === '' || strlen($encoded) > 24000) {
            return [];
        }

        $decoded = json_decode(rawurldecode($encoded), true);
        if (! is_array($decoded)) {
            return [];
        }

        return collect($decoded)
            ->take(30)
            ->filter(fn ($property): bool => is_array($property))
            ->map(function (array $property): array {
                $name = Str::limit(trim((string) ($property['name'] ?? $property['key'] ?? '')), 80, '');
                $value = Str::limit(trim((string) ($property['value'] ?? '')), 300, '');

                return compact('name', 'value');
            })
            ->filter(fn (array $property): bool => $property['name'] !== '')
            ->values()
            ->all();
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function renderButton(array $attributes): string
    {
        $title = trim($attributes['title'] ?? $attributes['label'] ?? $attributes['content'] ?? '访问链接');
        $url = $this->safeHref($attributes['url'] ?? $attributes['href'] ?? '');
        $color = $this->safeCssColor($attributes['color'] ?? '');
        $radius = $this->safeCssSize($attributes['radius'] ?? '');
        $style = trim($color !== '' ? 'background-color: '.$color.';' : '');
        $styleAttribute = $style !== '' ? ' style="'.$style.'"' : '';
        $iconHtml = $this->renderInlineIcon($attributes, 'zfy-shortcode-button-icon');

        return '<p class="zfy-shortcode-button-wrap"><a class="zfy-shortcode-button" href="'.e($url).'" target="_blank" rel="noopener noreferrer nofollow"'.$styleAttribute.'>'.$iconHtml.e($title).'</a></p>';
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function renderNoteButton(array $attributes): string
    {
        $title = trim($attributes['title'] ?? $attributes['label'] ?? $attributes['content'] ?? '便条按钮');
        $url = $this->safeHref($attributes['url'] ?? $attributes['href'] ?? '');
        $type = $this->safeToken($attributes['type'] ?? 'secondary');
        $iconHtml = $this->renderInlineIcon($attributes, 'zfy-shortcode-note-icon');

        return '<p class="zfy-shortcode-button-wrap"><a class="zfy-shortcode-note zfy-shortcode-note-'.$type.'" href="'.e($url).'" target="_blank" rel="noopener noreferrer nofollow">'.$iconHtml.e($title).'</a></p>';
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function renderInlineIcon(array $attributes, string $class): string
    {
        $icon = trim($attributes['icon'] ?? '');
        $encodedSvg = trim($attributes['iconsvg'] ?? '');

        if ($encodedSvg === '' && Str::startsWith($icon, 'svg:')) {
            $encodedSvg = substr($icon, 4);
        }

        if ($encodedSvg !== '') {
            $svg = $this->sanitizeInlineSvgIcon($this->decodeSvgIconPayload($encodedSvg));

            if ($svg !== '') {
                return '<span class="'.$class.' zfy-shortcode-icon-svg" aria-hidden="true">'.$this->inlineSvgIconPlaceholder($svg).'</span>';
            }
        }

        if (Str::startsWith(Str::lower($icon), '<svg')) {
            $svg = $this->sanitizeInlineSvgIcon($icon);

            if ($svg !== '') {
                return '<span class="'.$class.' zfy-shortcode-icon-svg" aria-hidden="true">'.$this->inlineSvgIconPlaceholder($svg).'</span>';
            }
        }

        if ($icon === '') {
            return '';
        }

        $faName = $this->fontAwesomeIconName($icon);

        if ($faName !== '') {
            return '<span class="'.$class.' fa '.$faName.'" aria-hidden="true">'.e($this->fontAwesomeIconGlyph($faName)).'</span>';
        }

        return '<span class="'.$class.'">'.e($icon).'</span>';
    }

    private function fontAwesomeIconName(string $icon): string
    {
        if (! preg_match('/\bfa-([a-z0-9-]+)\b/i', $icon, $match)) {
            return '';
        }

        return 'fa-'.$this->safeToken($match[1]);
    }

    private function fontAwesomeIconGlyph(string $icon): string
    {
        return [
            'fa-search' => '⌕',
            'fa-heart' => '♥',
            'fa-heart-o' => '♡',
            'fa-star' => '★',
            'fa-star-o' => '☆',
            'fa-user' => '👤',
            'fa-home' => '⌂',
            'fa-info-circle' => 'i',
            'fa-check-circle' => '✓',
            'fa-exclamation-triangle' => '!',
            'fa-times-circle' => '×',
            'fa-question-circle' => '?',
            'fa-check' => '✓',
            'fa-times' => '×',
            'fa-plus' => '+',
            'fa-minus' => '-',
            'fa-eye' => '◉',
            'fa-comment' => '💬',
            'fa-fire' => '🔥',
            'fa-gift' => '🎁',
            'fa-shopping-cart' => '🛒',
            'fa-download' => '↓',
            'fa-upload' => '↑',
            'fa-tag' => '🏷',
            'fa-clock-o' => '◷',
            'fa-lock' => '🔒',
            'fa-bell' => '🔔',
            'fa-handshake-o' => '🤝',
            'fa-book' => '📘',
            'fa-bookmark' => '🔖',
            'fa-file-text-o' => '▤',
            'fa-folder' => '▣',
            'fa-image-o' => '▧',
            'fa-picture-o' => '▧',
            'fa-camera' => '◉',
            'fa-music' => '♪',
            'fa-play' => '▶',
            'fa-pause' => 'Ⅱ',
            'fa-code' => '</>',
            'fa-calendar' => '□',
            'fa-map-marker' => '⌖',
            'fa-link' => '↗',
            'fa-paperclip' => '⌘',
            'fa-copy' => '⧉',
            'fa-money' => '¥',
            'fa-credit-card' => '▰',
            'fa-diamond' => '◆',
            'fa-trophy' => '🏆',
            'fa-truck' => '▱',
            'fa-archive' => '▥',
            'fa-database' => '◫',
            'fa-cloud' => '☁',
            'fa-shield' => '⬟',
            'fa-key' => '⚿',
            'fa-cog' => '⚙',
            'fa-wrench' => '⌘',
            'fa-qq' => 'QQ',
            'fa-weixin' => '微',
            'fa-weibo' => 'W',
            'fa-github' => 'GH',
            'fa-wordpress' => 'W',
            'fa-google' => 'G',
            'fa-twitter' => 'X',
            'fa-facebook' => 'f',
            'fa-instagram' => '◎',
            'fa-youtube-play' => '▶',
            'fa-telegram' => '✈',
            'fa-reddit' => 'R',
        ][$icon] ?? 'FA';
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function renderDotted(array $attributes): string
    {
        $start = $this->safeCssColor($attributes['startcolor'] ?? '#ff6c6c') ?: '#ff6c6c';
        $end = $this->safeCssColor($attributes['endcolor'] ?? '#1989fa') ?: '#1989fa';
        $gradient = $this->dottedGradient($start, $end);

        return '<div class="zfy-shortcode zfy-shortcode-dotted" data-start-color="'.e($start).'" data-end-color="'.e($end).'"><span class="joe_dotted" style="background-image: '.$gradient.';"></span></div>';
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function renderLamp(array $attributes): string
    {
        $title = trim($attributes['title'] ?? $attributes['label'] ?? '');
        $label = $title !== '' ? '<span>'.e($title).'</span>' : '';

        return '<div class="zfy-shortcode zfy-shortcode-lamp"><i></i>'.$label.'</div>';
    }

    /**
     * @return array<int, string>
     */
    private function extractHtmlItems(string $html): array
    {
        preg_match_all('/<li[^>]*>([\s\S]*?)<\/li>/i', $html, $matches);

        return collect($matches[1] ?? [])
            ->map(fn (string $item) => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function extractParagraphs(string $html): array
    {
        preg_match_all('/<p[^>]*>([\s\S]*?)<\/p>/i', $html, $matches);

        return collect($matches[1] ?? [])
            ->map(fn (string $item) => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    private function restoreInlineSvgIcons(string $html): string
    {
        return preg_replace_callback(
            '/%%ZFY_INLINE_SVG_([A-Za-z0-9_-]+)%%/',
            fn (array $matches) => $this->sanitizeInlineSvgIcon($this->decodeSvgIconPayload($matches[1] ?? '')),
            $html,
        ) ?? $html;
    }

    private function inlineSvgIconPlaceholder(string $svg): string
    {
        return '%%ZFY_INLINE_SVG_'.$this->encodeSvgIconPayload($svg).'%%';
    }

    private function encodeSvgIconPayload(string $svg): string
    {
        return rtrim(strtr(base64_encode($svg), '+/', '-_'), '=');
    }

    private function decodeSvgIconPayload(string $payload): string
    {
        $payload = trim($payload);

        if (Str::startsWith($payload, 'svg:')) {
            $payload = substr($payload, 4);
        }

        if ($payload === '' || ! preg_match('/^[A-Za-z0-9_-]+$/', $payload)) {
            return '';
        }

        $normalized = strtr($payload, '-_', '+/');
        $normalized .= str_repeat('=', (4 - strlen($normalized) % 4) % 4);
        $decoded = base64_decode($normalized, true);

        return is_string($decoded) ? $decoded : '';
    }

    private function sanitizeInlineSvgIcon(string $svg): string
    {
        $svg = trim(html_entity_decode($svg, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if ($svg === '' || strlen($svg) > 20000 || ! Str::startsWith(Str::lower($svg), '<svg')) {
            return '';
        }

        if (preg_match('/<\s*(script|iframe|object|embed|foreignobject|style)\b/i', $svg) === 1) {
            return '';
        }

        if (preg_match('/\son[a-z0-9_-]+\s*=/i', $svg) === 1 || preg_match('/(?:javascript|data)\s*:/i', $svg) === 1) {
            return '';
        }

        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $loaded = $dom->loadXML($svg, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded || ! $dom->documentElement || strtolower($dom->documentElement->tagName) !== 'svg') {
            return '';
        }

        $this->sanitizeSvgNode($dom->documentElement);
        $dom->documentElement->setAttribute('width', '1em');
        $dom->documentElement->setAttribute('height', '1em');
        $dom->documentElement->setAttribute('aria-hidden', 'true');
        $dom->documentElement->setAttribute('focusable', 'false');

        return $dom->saveXML($dom->documentElement) ?: '';
    }

    private function sanitizeSvgNode(\DOMNode $node): void
    {
        $allowedTags = ['svg', 'g', 'path', 'circle', 'rect', 'line', 'polyline', 'polygon', 'ellipse', 'title'];

        for ($index = $node->childNodes->length - 1; $index >= 0; $index--) {
            $child = $node->childNodes->item($index);

            if ($child instanceof \DOMElement) {
                if (! in_array(strtolower($child->tagName), $allowedTags, true)) {
                    $node->removeChild($child);

                    continue;
                }

                $this->sanitizeSvgElement($child);
                $this->sanitizeSvgNode($child);

                continue;
            }

            if ($child && ($child->nodeType !== XML_TEXT_NODE || trim((string) $child->textContent) === '')) {
                $node->removeChild($child);
            }
        }

        if ($node instanceof \DOMElement) {
            $this->sanitizeSvgElement($node);
        }
    }

    private function sanitizeSvgElement(\DOMElement $element): void
    {
        $allowedAttributes = [
            'viewbox',
            'width',
            'height',
            'fill',
            'stroke',
            'stroke-width',
            'stroke-linecap',
            'stroke-linejoin',
            'fill-rule',
            'clip-rule',
            'opacity',
            'transform',
            'd',
            'cx',
            'cy',
            'r',
            'x',
            'y',
            'x1',
            'y1',
            'x2',
            'y2',
            'rx',
            'ry',
            'points',
            'xmlns',
        ];

        for ($index = $element->attributes->length - 1; $index >= 0; $index--) {
            $attribute = $element->attributes->item($index);

            if (! $attribute) {
                continue;
            }

            $name = strtolower($attribute->name);
            if (! in_array($name, $allowedAttributes, true) || ! $this->safeSvgAttributeValue($attribute->value)) {
                $element->removeAttributeNode($attribute);
            }
        }
    }

    private function safeSvgAttributeValue(string $value): bool
    {
        return strlen($value) <= 4000
            && preg_match('/[<>]/', $value) !== 1
            && preg_match('/(?:javascript|data)\s*:/i', $value) !== 1
            && preg_match('/url\s*\(/i', $value) !== 1
            && preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $value) !== 1;
    }

    private function safeHref(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '#';
        }

        return Str::startsWith(Str::lower($url), ['http://', 'https://', '/', '#']) ? $url : '#';
    }

    private function safeCssColor(string $color): string
    {
        $color = trim($color);

        return preg_match('/^#[0-9a-fA-F]{3,8}$/', $color) ? $color : '';
    }

    private function dottedGradient(string $start, string $end): string
    {
        return 'repeating-linear-gradient(90deg, '.$end.' 0, '.$end.' 14px, transparent 14px, transparent 18px, '.$start.' 18px, '.$start.' 32px, transparent 32px, transparent 36px)';
    }

    private function restoreDottedStyles(string $html): string
    {
        return preg_replace_callback(
            '/(<div\b[^>]*\bzfy-shortcode-dotted\b[^>]*>)(\s*<span\b[^>]*\bjoe_dotted\b[^>]*)(><\/span>\s*<\/div>)/i',
            function (array $matches): string {
                $start = $this->safeCssColor($this->htmlAttributeValue($matches[1], 'data-start-color')) ?: '#ff6c6c';
                $end = $this->safeCssColor($this->htmlAttributeValue($matches[1], 'data-end-color')) ?: '#1989fa';
                $span = preg_replace('/\sstyle=(["\']).*?\1/i', '', $matches[2]) ?? $matches[2];

                return $matches[1].$span.' style="background-image: '.$this->dottedGradient($start, $end).';"'.$matches[3];
            },
            $html
        ) ?? $html;
    }

    private function htmlAttributeValue(string $tag, string $attribute): string
    {
        if (preg_match('/\s'.preg_quote($attribute, '/').'=(["\'])(.*?)\1/i', $tag, $matches) !== 1) {
            return '';
        }

        return html_entity_decode($matches[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function alertColorToken(string $color): string
    {
        $token = $this->safeToken($color);
        $aliases = [
            'info' => 'blue',
            'primary' => 'blue',
            'success' => 'green',
            'warning' => 'yellow',
            'warn' => 'yellow',
            'error' => 'red',
            'danger' => 'red',
            'default' => 'blue',
        ];
        $token = $aliases[$token] ?? $token;

        if (in_array($token, ['blue', 'cyan', 'green', 'yellow', 'red', 'purple', 'gray'], true)) {
            return $token;
        }

        return $this->safeCssColor($color) !== '' ? 'custom' : 'blue';
    }

    private function alertIconToken(string $icon, string $color): ?string
    {
        $token = $this->safeToken($icon);
        if ($token === 'default') {
            $token = [
                'green' => 'check',
                'yellow' => 'warning',
                'red' => 'error',
            ][$color] ?? 'info';
        }

        $aliases = [
            'success' => 'check',
            'ok' => 'check',
            'danger' => 'error',
            'warn' => 'warning',
            'notice' => 'info',
            'light' => 'lamp',
            'hot' => 'fire',
        ];
        $token = $aliases[$token] ?? $token;

        return in_array($token, ['info', 'check', 'warning', 'error', 'bell', 'lamp', 'fire', 'star', 'none'], true)
            ? $token
            : null;
    }

    private function safeCssSize(string $size): string
    {
        $size = trim($size);

        return preg_match('/^(?:\d{1,4}(?:\.\d{1,2})?(?:px|%|rem|em)|auto)$/', $size) ? $size : '';
    }

    private function safeTimeFormat(string $format): string
    {
        $format = trim($format);

        return in_array($format, self::TIME_FORMATS, true) ? $format : self::DEFAULT_TIME_FORMAT;
    }

    private function truthyAttribute(string $value): bool
    {
        $value = Str::lower(trim($value));

        return in_array($value, ['1', 'true', 'yes', 'on', 'autoplay'], true);
    }

    private function formatCurrentTime(string $format): string
    {
        return now()->format(strtr($format, [
            'YYYY' => 'Y',
            'MM' => 'm',
            'DD' => 'd',
            'HH' => 'H',
            'mm' => 'i',
            'ss' => 's',
        ]));
    }

    private function truthy(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        return in_array(strtolower($value), ['1', 'true', 'yes', 'on', 'open'], true);
    }

    private function cloudTypeKey(string $type): string
    {
        $type = $this->safeToken($type);

        return array_key_exists($type, self::CLOUD_TYPES) ? $type : 'default';
    }

    private function mediaIcon(string $type): string
    {
        return [
            'bilibili' => 'B',
            'dplayer' => 'V',
            'mp3' => 'M',
            'music' => '♪',
            'music-list' => '♫',
        ][$type] ?? 'M';
    }

    private function placeholder(int $index): string
    {
        return '%%ZFY_SHORTCODE_PLACEHOLDER_'.$index.'%%';
    }

    private function shortcodeNameAlternation(): string
    {
        $names = collect(self::SHORTCODE_NAMES)
            ->sortByDesc(fn (string $name) => strlen($name))
            ->map(fn (string $name) => preg_quote($name, '/'))
            ->implode('|');

        return '(?:'.$names.')';
    }

    private function shortcodeTokenPattern(): string
    {
        return '/\{\/?(?<name>'.$this->shortcodeNameAlternation().')(?=\s|\/|\})(?:\s[^}]*)?\/?\}/i';
    }

    private function pairedShortcodePattern(): string
    {
        return '/\{(?<name>'.$this->shortcodeNameAlternation().')(?<attributes>[^}]*)\}(?<body>[\s\S]*?)\{\/\s*\k<name>\s*\}/i';
    }

    private function singleShortcodePattern(): string
    {
        return '/\{(?<name>'.$this->shortcodeNameAlternation().')(?<attributes>[^}]*)\/\}/i';
    }

    private function safeToken(string $value): string
    {
        return Str::of($value)->lower()->replaceMatches('/[^a-z0-9_-]+/', '-')->trim('-')->value() ?: 'default';
    }
}
