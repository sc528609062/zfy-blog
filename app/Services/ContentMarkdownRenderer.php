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

        if (trim($html) === '') {
            return true;
        }

        return preg_match($this->shortcodeTokenPattern(), $html) === 1
            || Str::contains(Str::lower($html), ['<joe-', 'joe_', '<blockquote', 'zfy-shortcode-quote zfy-quote quote_q', '/storage/media/'])
            || preg_match('/<pre\b(?![^>]*\bwp-block-zibllblock-enlighter\b)[^>]*>\s*<code\b/i', $html) === 1;
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
        preg_match_all('/([a-zA-Z0-9_-]+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s]+))/', $source, $matches, PREG_SET_ORDER);

        $attributes = [];
        foreach ($matches as $match) {
            $attributes[strtolower($match[1])] = $match[2] !== '' ? $match[2] : ($match[3] !== '' ? $match[3] : ($match[4] ?? ''));
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
            'zfy-alert' => $this->wrapShortcode('alert', $tone, $titleHtml.$this->bodyHtml($innerHtml)),
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

        if ($name === 'zfy-mp3' && $url !== '#') {
            $cover = $this->safeHref($attributes['cover'] ?? '');
            $coverHtml = $cover !== '#'
                ? '<img class="zfy-media-cover" src="'.e($cover).'" alt="'.e($title).'">'
                : '<span class="zfy-media-icon">'.$this->mediaIcon($type).'</span>';

            return '<div class="zfy-shortcode zfy-shortcode-media zfy-shortcode-media-mp3 zfy-shortcode-audio">'.$coverHtml.'<div><div class="zfy-shortcode-title">'.e($title).'</div><small>'.e($url).'</small><audio class="zfy-audio-player" src="'.e($url).'" controls preload="metadata"></audio></div></div>';
        }

        if (in_array($name, ['zfy-music', 'zfy-music-list'], true)) {
            $id = trim($attributes['id'] ?? '');
            $meta = $id !== '' ? '网易云 ID：'.$id : ($url === '#' ? '未填写地址' : $url);

            return '<div class="zfy-shortcode zfy-shortcode-media zfy-shortcode-media-'.$type.'"><span class="zfy-media-icon">'.$this->mediaIcon($type).'</span><div><div class="zfy-shortcode-title">'.e($title).'</div><small>'.e($meta).'</small></div>'.($url !== '#' ? '<a href="'.e($url).'" target="_blank" rel="noopener noreferrer nofollow">打开</a>' : '').'</div>';
        }

        return '<div class="zfy-shortcode zfy-shortcode-media zfy-shortcode-media-'.$type.'"><span class="zfy-media-icon">'.$this->mediaIcon($type).'</span><div><div class="zfy-shortcode-title">'.e($title).'</div><small>'.e($url === '#' ? '未填写地址' : $url).'</small></div><a href="'.e($url).'" target="_blank" rel="noopener noreferrer nofollow">打开</a></div>';
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
        $title = trim($attributes['title'] ?? '下载资源');
        $url = $this->safeHref($attributes['url'] ?? '');
        $type = $this->cloudTypeLabel($attributes['type'] ?? 'default');
        $password = trim($attributes['password'] ?? $attributes['code'] ?? '');
        $meta = $type.($password !== '' ? ' | 提取码：'.$password : '');

        return '<div class="zfy-shortcode zfy-shortcode-cloud"><span class="zfy-cloud-logo">云</span><div><div class="zfy-shortcode-title">'.e($title).'</div><small>'.e($meta).'</small></div><a href="'.e($url).'" target="_blank" rel="noopener noreferrer nofollow">下载</a></div>';
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
        $icon = trim($attributes['icon'] ?? '');
        $iconHtml = $icon !== '' ? '<span class="zfy-shortcode-button-icon">'.e($icon).'</span>' : '';

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
        $icon = trim($attributes['icon'] ?? '');
        $iconHtml = $icon !== '' ? '<span class="zfy-shortcode-note-icon">'.e($icon).'</span>' : '';

        return '<p class="zfy-shortcode-button-wrap"><a class="zfy-shortcode-note zfy-shortcode-note-'.$type.'" href="'.e($url).'" target="_blank" rel="noopener noreferrer nofollow">'.$iconHtml.e($title).'</a></p>';
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function renderDotted(array $attributes): string
    {
        $start = $this->safeCssColor($attributes['startcolor'] ?? '#ff6c6c') ?: '#ff6c6c';
        $end = $this->safeCssColor($attributes['endcolor'] ?? '#1989fa') ?: '#1989fa';

        return '<div class="zfy-shortcode zfy-shortcode-dotted"><span style="background-image: repeating-linear-gradient(-45deg, '.$start.' 0, '.$start.' 20%, transparent 0, transparent 25%, '.$end.' 0, '.$end.' 45%, transparent 0, transparent 50%);"></span></div>';
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

    private function cloudTypeLabel(string $type): string
    {
        return [
            'default' => '默认网盘',
            '360' => '360 网盘',
            'bd' => '百度网盘',
            'ty' => '天翼网盘',
            'ct' => '城通网盘',
            'wy' => '微云网盘',
            'github' => 'Github 仓库',
            'lz' => '蓝奏云网盘',
        ][$this->safeToken($type)] ?? '默认网盘';
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
