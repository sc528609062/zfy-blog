import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { createRequire } from 'node:module';
import themes from 'juejin-markdown-themes';
import { markdownThemeBaseCss, scopeThemeCss } from './markdown-theme-css.mjs';

const require = createRequire(import.meta.url);
const id = 'virtual:zfy-markdown-themes';
const codeThemeNames = ['atom-one-dark', 'atom-one-light', 'github', 'github-dark', 'monokai',
    'tokyo-night-dark', 'stackoverflow-light', 'androidstudio', 'base16/tomorrow-night'];

export default function markdownThemesPlugin() {
    return {
        name: 'zfy-markdown-themes',
        resolveId(source) { if (source === id) return `\0${id}`; },
        load(source) {
            if (source !== `\0${id}`) return;
            const koiPath = new URL('../resources/css/markdown/koi.css', import.meta.url);
            this.addWatchFile(fileURLToPath(koiPath));
            const registry = { ...themes, koi: { style: readFileSync(koiPath, 'utf8') } };
            const markdownStyles = {};
            const highlights = {};
            for (const [name, theme] of Object.entries(registry)) {
                if (!theme.style || theme.style === '404:Not Found') continue;
                markdownStyles[name] = scopeThemeCss(theme.style, name);
                highlights[name] = theme.highlight || 'atom-one-dark';
            }
            const codeStyles = Object.fromEntries(codeThemeNames.map((name) => [name,
                scopeThemeCss(readFileSync(require.resolve(`highlight.js/styles/${name}.css`), 'utf8'), name, 'code'),
            ]));
            return `export const markdownStyles = ${JSON.stringify(markdownStyles)};
                export const codeStyles = ${JSON.stringify(codeStyles)};
                export const highlights = ${JSON.stringify(highlights)};
                export const baseStyle = ${JSON.stringify(markdownThemeBaseCss())};`;
        },
    };
}
