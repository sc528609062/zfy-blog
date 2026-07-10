import hljs from 'highlight.js/lib/core';
import bash from 'highlight.js/lib/languages/bash';
import cpp from 'highlight.js/lib/languages/cpp';
import csharp from 'highlight.js/lib/languages/csharp';
import css from 'highlight.js/lib/languages/css';
import diff from 'highlight.js/lib/languages/diff';
import go from 'highlight.js/lib/languages/go';
import ini from 'highlight.js/lib/languages/ini';
import java from 'highlight.js/lib/languages/java';
import javascript from 'highlight.js/lib/languages/javascript';
import json from 'highlight.js/lib/languages/json';
import kotlin from 'highlight.js/lib/languages/kotlin';
import markdown from 'highlight.js/lib/languages/markdown';
import php from 'highlight.js/lib/languages/php';
import python from 'highlight.js/lib/languages/python';
import ruby from 'highlight.js/lib/languages/ruby';
import rust from 'highlight.js/lib/languages/rust';
import sql from 'highlight.js/lib/languages/sql';
import typescript from 'highlight.js/lib/languages/typescript';
import xml from 'highlight.js/lib/languages/xml';
import yaml from 'highlight.js/lib/languages/yaml';
import atomOneDark from 'highlight.js/styles/atom-one-dark.css?inline';
import atomOneLight from 'highlight.js/styles/atom-one-light.css?inline';
import github from 'highlight.js/styles/github.css?inline';
import githubDark from 'highlight.js/styles/github-dark.css?inline';
import monokai from 'highlight.js/styles/monokai.css?inline';
import tokyoNight from 'highlight.js/styles/tokyo-night-dark.css?inline';
import stackoverflowLight from 'highlight.js/styles/stackoverflow-light.css?inline';
import androidstudio from 'highlight.js/styles/androidstudio.css?inline';
import tomorrowNight from 'highlight.js/styles/base16/tomorrow-night.css?inline';
import renderMathInElement from 'katex/contrib/auto-render';
import 'katex/dist/katex.min.css';
import mediumZoom from 'medium-zoom';
import { nameToEmoji } from 'gemoji';
import { markdownThemeStyle, normalizeMarkdownTheme } from './markdownThemes';

const codeStyles: Record<string, string> = {
    'atom-one-dark': atomOneDark,
    'atom-one-light': atomOneLight,
    github,
    'github-dark': githubDark,
    monokai,
    'tokyo-night-dark': tokyoNight,
    'stackoverflow-light': stackoverflowLight,
    androidstudio,
    'base16/tomorrow-night': tomorrowNight,
};

Object.entries({
    bash,
    cpp,
    csharp,
    css,
    diff,
    go,
    ini,
    java,
    javascript,
    json,
    kotlin,
    markdown,
    php,
    python,
    ruby,
    rust,
    sql,
    typescript,
    xml,
    yaml,
}).forEach(([name, language]) => hljs.registerLanguage(name, language));

const languageAliases: Record<string, string> = {
    html: 'xml',
    vue: 'xml',
    shell: 'bash',
    sh: 'bash',
    js: 'javascript',
    ts: 'typescript',
    md: 'markdown',
    yml: 'yaml',
};

export interface MarkdownEnhancementOptions {
    markdownTheme?: string;
    codeTheme?: string;
}

export async function enhanceMarkdownContent(
    root: HTMLElement,
    options: MarkdownEnhancementOptions = {},
): Promise<() => void> {
    const markdownTheme = normalizeMarkdownTheme(options.markdownTheme || root.dataset.markdownTheme || 'juejin');
    const codeTheme = normalizeCodeTheme(options.codeTheme || root.dataset.codeTheme || 'atom-one-dark');

    root.classList.add('markdown-body');
    root.dataset.markdownTheme = markdownTheme;
    root.dataset.codeTheme = codeTheme;
    installStyle(`zfy-markdown-theme-${markdownTheme}`, markdownThemeStyle(markdownTheme));
    installStyle('zfy-code-theme', codeStyles[codeTheme]);
    replaceGemoji(root);
    renderMath(root);
    await renderMermaid(root);
    highlightCodeBlocks(root);

    const zoom = mediumZoom(root.querySelectorAll<HTMLImageElement>('img:not([data-no-zoom])'), {
        background: 'rgba(15, 23, 42, .88)',
        margin: 32,
        scrollOffset: 24,
    });

    return () => {
        zoom.detach();
    };
}

function installStyle(id: string, css: string): void {
    if (!css) {
        return;
    }

    let style = document.getElementById(id) as HTMLStyleElement | null;
    if (!style) {
        style = document.createElement('style');
        style.id = id;
        document.head.appendChild(style);
    }

    if (style.textContent !== css) {
        style.textContent = css;
    }
}

function normalizeCodeTheme(value: string): string {
    return codeStyles[value] ? value : 'atom-one-dark';
}

function replaceGemoji(root: HTMLElement): void {
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
        acceptNode(node) {
            const parent = node.parentElement;
            if (!parent || parent.closest('pre, code, script, style, textarea, .katex, .mermaid')) {
                return NodeFilter.FILTER_REJECT;
            }

            return /:[+\-\w]+:/.test(node.textContent || '') ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT;
        },
    });
    const nodes: Text[] = [];

    while (walker.nextNode()) {
        nodes.push(walker.currentNode as Text);
    }

    nodes.forEach((node) => {
        node.textContent = (node.textContent || '').replace(/:([+\-\w]+):/g, (match, name: string) => nameToEmoji[name] || match);
    });
}

function renderMath(root: HTMLElement): void {
    try {
        renderMathInElement(root, {
            delimiters: [
                { left: '$$', right: '$$', display: true },
                { left: '\\[', right: '\\]', display: true },
                { left: '\\(', right: '\\)', display: false },
                { left: '$', right: '$', display: false },
            ],
            ignoredTags: ['script', 'noscript', 'style', 'textarea', 'pre', 'code'],
            throwOnError: false,
            strict: false,
        });
    } catch {
        // 保留原公式文本，避免单个无效公式中断整篇文章增强。
    }
}

async function renderMermaid(root: HTMLElement): Promise<void> {
    const blocks = [...root.querySelectorAll<HTMLElement>('.enlighter-origin[data-enlighter-language="mermaid"], pre > code.language-mermaid')];
    if (blocks.length === 0) {
        return;
    }

    const diagrams = blocks.map((block, index) => {
        const host = document.createElement('div');
        host.className = 'mermaid zfy-mermaid';
        host.id = `zfy-mermaid-${Date.now()}-${index}`;
        const source = block.textContent || '';
        host.textContent = source;
        const container = block.closest('pre.wp-block-zibllblock-enlighter') || block.parentElement;
        container?.replaceWith(host);

        return { host, source };
    });

    try {
        const { default: mermaid } = await import('mermaid');
        mermaid.initialize({
            startOnLoad: false,
            securityLevel: 'strict',
            theme: 'default',
            fontFamily: 'Inter, Microsoft YaHei, sans-serif',
        });
        for (const [index, diagram] of diagrams.entries()) {
            try {
                const id = `zfy-mermaid-svg-${Date.now()}-${index}`;
                const { svg, bindFunctions } = await mermaid.render(id, diagram.source);
                diagram.host.innerHTML = svg;
                bindFunctions?.(diagram.host);
            } catch {
                diagram.host.classList.add('is-error');
            }
        }
    } catch {
        diagrams.forEach(({ host }) => host.classList.add('is-error'));
    }
}

function highlightCodeBlocks(root: HTMLElement): void {
    root.querySelectorAll<HTMLElement>('.wp-block-zibllblock-enlighter').forEach((block) => {
        const source = block.querySelector<HTMLElement>('.enlighter-origin');
        const target = block.querySelector<HTMLElement>('.enlighter');
        if (!source || !target || source.dataset.enhanced === 'true') {
            return;
        }

        const code = source.textContent || '';
        const requested = source.dataset.enlighterLanguage || '';
        const language = languageAliases[requested] || requested;
        let highlighted: string;

        try {
            highlighted = language && hljs.getLanguage(language)
                ? hljs.highlight(code, { language, ignoreIllegals: true }).value
                : hljs.highlightAuto(code).value;
        } catch {
            highlighted = escapeHtml(code);
        }

        const lines = highlighted.split('\n');
        if (lines.at(-1) === '') {
            lines.pop();
        }

        target.classList.add('hljs');
        target.innerHTML = lines.map((line) => `<div><div>${line || '&nbsp;'}</div></div>`).join('');
        source.dataset.enhanced = 'true';
    });
}

function escapeHtml(value: string): string {
    const span = document.createElement('span');
    span.textContent = value;

    return span.innerHTML;
}
