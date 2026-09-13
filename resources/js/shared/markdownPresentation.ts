import { baseStyle, codeStyles } from 'virtual:zfy-markdown-themes';
import { markdownThemeStyle, normalizeMarkdownTheme } from './markdownThemes';

export interface MarkdownPresentationOptions {
    markdownTheme?: string;
    codeTheme?: string;
}

const styles = new Map<string, { element: HTMLStyleElement; users: number }>();

export function applyMarkdownPresentation(root: HTMLElement, options: MarkdownPresentationOptions = {}): () => void {
    const markdownTheme = normalizeMarkdownTheme(options.markdownTheme || root.dataset.markdownTheme);
    const requestedCodeTheme = options.codeTheme || root.dataset.codeTheme || '';
    const codeTheme = Object.hasOwn(codeStyles, requestedCodeTheme) ? requestedCodeTheme : 'atom-one-dark';
    root.classList.add('markdown-body');
    root.dataset.markdownTheme = markdownTheme;
    root.dataset.codeTheme = codeTheme;

    const releases = [
        retainStyle('zfy-markdown-base', baseStyle),
        retainStyle(`zfy-markdown-theme-${markdownTheme}`, markdownThemeStyle(markdownTheme)),
        retainStyle(`zfy-code-theme-${codeTheme.replaceAll('/', '-')}`, codeStyles[codeTheme]),
    ];
    return () => releases.forEach((release) => release());
}

function retainStyle(id: string, css: string): () => void {
    let entry = styles.get(id);
    if (!entry) {
        const element = document.createElement('style');
        element.id = id;
        element.textContent = css;
        document.head.appendChild(element);
        entry = { element, users: 0 };
        styles.set(id, entry);
    }
    entry.users++;
    let released = false;
    return () => {
        if (released) return;
        released = true;
        if (--entry.users === 0) {
            entry.element.remove();
            styles.delete(id);
        }
    };
}
