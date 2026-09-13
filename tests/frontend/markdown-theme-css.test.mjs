import assert from 'node:assert/strict';
import test from 'node:test';
import postcss from 'postcss';
import themes from 'juejin-markdown-themes';
import { scopeThemeCss } from '../../scripts/markdown-theme-css.mjs';

test('all shipped theme selectors and animation names are scoped', () => {
    for (const [name, theme] of Object.entries(themes)) {
        if (!theme.style || theme.style === '404:Not Found') continue;
        const css = postcss.parse(scopeThemeCss(theme.style, name));
        css.walkRules((rule) => {
            if (rule.parent.type === 'atrule' && rule.parent.name.endsWith('keyframes')) return;
            for (const selector of rule.selectors) {
                assert.ok(selector.includes(`data-markdown-theme="${name}"`), selector);
                assert.ok(selector.includes('[data-markdown-component]'), selector);
            }
        });
        css.walkAtRules((rule) => {
            if (rule.name.endsWith('keyframes')) assert.ok(rule.params.startsWith(`zfy-markdown-${name}-`));
        });
    }
});

test('grouped and media selectors are scoped; component exclusion precedes pseudo-elements', () => {
    const css = scopeThemeCss('@media (max-width: 600px) { .markdown-body h2::before, a:is(:hover,:focus) { color:red } }', 'test');
    assert.match(css, /data-markdown-theme="test"/);
    assert.match(css, /\*\)\)::before/);
    assert.match(css, /\[data-markdown-theme="test"\]\s+a:is/);
});

test('animation references follow renamed keyframes and global imports are removed', () => {
    const css = scopeThemeCss('@import "global.css"; @keyframes spin { to { transform:rotate(1turn) } } .markdown-body h1 { animation:spin 2s linear; animation-name:spin }', 'cute');
    assert.ok(!css.includes('@import'));
    assert.match(css, /animation:zfy-markdown-cute-spin 2s linear/);
    assert.match(css, /animation-name:zfy-markdown-cute-spin/);
});

test('code themes use their own data attribute and do not exclude code components', () => {
    const css = scopeThemeCss('.hljs, pre code.hljs { color: red } .hljs-keyword { color:blue }', 'github', 'code');
    for (const selector of postcss.parse(css).nodes.flatMap((rule) => rule.selectors)) {
        assert.ok(selector.startsWith('.markdown-body[data-code-theme="github"] '));
        assert.ok(!selector.includes('data-markdown-component'));
    }
});
