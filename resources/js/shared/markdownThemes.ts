import themes from 'juejin-markdown-themes';

interface MarkdownThemePayload {
    style: string;
    highlight?: string;
}

const themeRegistry = themes as unknown as Record<string, MarkdownThemePayload>;
const localThemeStyles: Record<string, string> = {
    koi: [
        '.markdown-body{word-break:break-word;line-height:1.85;font-size:16px;color:#27352f}',
        '.markdown-body h1,.markdown-body h2,.markdown-body h3,.markdown-body h4,.markdown-body h5,.markdown-body h6{margin:1.6em 0 .7em;color:#156b52;line-height:1.45}',
        '.markdown-body h2{padding:8px 12px;border-left:4px solid #d9485f;background:#f4faf7}',
        '.markdown-body p{margin:1em 0}',
        '.markdown-body a{color:#d9485f;text-decoration:none;border-bottom:1px solid currentColor}',
        '.markdown-body blockquote{margin:1.2em 0;padding:10px 16px;border-left:4px solid #e6b44a;background:#fffaf0;color:#5d5544}',
        '.markdown-body code{padding:2px 5px;border-radius:3px;background:#f3f5f4;color:#c43b54}',
        '.markdown-body pre{padding:16px;overflow:auto;border-radius:6px;background:#17231f;color:#dce9e3}',
        '.markdown-body table{width:100%;border-collapse:collapse}.markdown-body td,.markdown-body th{padding:8px 10px;border:1px solid #dce8e2}',
        '.markdown-body img{max-width:100%}',
    ].join(''),
};

export function normalizeMarkdownTheme(value: unknown, fallback = 'juejin'): string {
    const key = String(value || '');

    return resolvedThemeStyle(key) ? key : fallback;
}

export function markdownThemeStyle(theme: string): string {
    const normalized = normalizeMarkdownTheme(theme);
    const style = resolvedThemeStyle(normalized) || resolvedThemeStyle('juejin');
    const escaped = normalized.replace(/["\\]/g, '\\$&');

    return style.replaceAll('.markdown-body', `.markdown-body[data-markdown-theme="${escaped}"]`);
}

export function defaultCodeThemeFor(markdownTheme: string): string {
    return themeRegistry[normalizeMarkdownTheme(markdownTheme)]?.highlight || 'atom-one-dark';
}

function resolvedThemeStyle(theme: string): string {
    const style = themeRegistry[theme]?.style || '';

    return style && style !== '404:Not Found' ? style : localThemeStyles[theme] || '';
}
