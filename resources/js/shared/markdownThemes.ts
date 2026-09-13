import { highlights, markdownStyles } from 'virtual:zfy-markdown-themes';

export function normalizeMarkdownTheme(value: unknown, fallback = 'juejin'): string {
    const key = String(value || '');

    return Object.hasOwn(markdownStyles, key) ? key : Object.hasOwn(markdownStyles, fallback) ? fallback : 'juejin';
}

export function markdownThemeStyle(theme: string): string {
    return markdownStyles[normalizeMarkdownTheme(theme)];
}

export function defaultCodeThemeFor(markdownTheme: string): string {
    return highlights[normalizeMarkdownTheme(markdownTheme)] || 'atom-one-dark';
}
