import postcss from 'postcss';
import selectorParser from 'postcss-selector-parser';
import valueParser from 'postcss-value-parser';

export const componentSelector = ':is(.zfy-shortcode, .zfy-shortcode-button-wrap, .zfy-shortcode-rule, .wp-block-zibllblock-enlighter, .katex, .mermaid, [data-markdown-component])';

export function scopeThemeCss(css, theme, kind = 'markdown') {
    const rootSelector = `.markdown-body[data-${kind}-theme="${theme}"]`;
    const rootNodes = selectorParser().astSync(rootSelector).first.nodes;
    const excluded = `:where(:not(${componentSelector}, ${componentSelector} *))`;
    const ast = postcss.parse(css);
    const animations = new Map();

    ast.walkAtRules((rule) => {
        if (rule.name.endsWith('keyframes')) {
            const name = `zfy-${kind}-${theme.replaceAll('/', '-')}-${rule.params}`;
            animations.set(rule.params, name);
            rule.params = name;
        } else if (!['media', 'supports', 'layer', 'container'].includes(rule.name)) {
            // Theme packages may style content, but cannot load global CSS or fonts.
            rule.remove();
        }
    });
    ast.walkDecls((declaration) => {
        if (!['animation', 'animation-name', '-webkit-animation', '-webkit-animation-name'].includes(declaration.prop)) return;
        const value = valueParser(declaration.value);
        value.walk((node) => {
            if (node.type === 'word' && animations.has(node.value)) node.value = animations.get(node.value);
        });
        declaration.value = value.toString();
    });
    ast.walkRules((rule) => {
        if (rule.parent.type === 'atrule' && rule.parent.name.endsWith('keyframes')) return;
        rule.selector = selectorParser((selectors) => {
            selectors.each((selector) => {
                let hasRoot = false;
                selector.walkClasses((node) => {
                    if (node.value !== 'markdown-body') return;
                    hasRoot = true;
                    node.replaceWith(...rootNodes.map((part) => part.clone()));
                });
                if (!hasRoot) {
                    selector.prepend(selectorParser.combinator({ value: ' ' }));
                    for (const node of [...rootNodes].reverse()) selector.prepend(node.clone());
                }
                if (kind === 'markdown') {
                    // Guard the matched element before any pseudo-element.
                    const guard = selectorParser().astSync(excluded).first.first.clone();
                    const pseudoElement = selector.nodes.find((node) => node.type === 'pseudo'
                        && (node.value.startsWith('::') || [':before', ':after', ':first-line', ':first-letter'].includes(node.value)));
                    if (pseudoElement) selector.insertBefore(pseudoElement, guard);
                    else selector.append(guard);
                }
            });
        }).processSync(rule.selector);
    });
    return ast.toString();
}

export function markdownThemeBaseCss() {
    const scope = '.markdown-body[data-markdown-theme]';
    const headings = `${scope} :is(h1,h2,h3,h4,h5,h6):where(:not(${componentSelector}, ${componentSelector} *))`;
    return `
        ${scope} { min-width: 0; overflow-wrap: anywhere; letter-spacing: 0; container: zfy-markdown / inline-size; }
        :where(${scope}) :where(${componentSelector}) {
            color: #24324a; font-family: Inter, "Microsoft YaHei", "PingFang SC", sans-serif;
            font-size: 15px; font-weight: 400; line-height: 1.85; text-align: start;
            letter-spacing: 0; word-spacing: normal; text-transform: none;
        }
        ${headings} { padding: 0; border: 0; border-radius: 0; background: none; }
        ${headings}::before, ${headings}::after {
            content: none; position: static; inset: auto; width: auto; height: auto;
            margin: 0; padding: 0; border: 0; border-radius: 0; background: none;
            opacity: 1; transform: none; transition: none;
        }
        @container zfy-markdown (max-width: 520px) {
            :is(.zfy-editor-preview, .article-content) .zfy-shortcode-cloud {
                grid-template-columns: 44px minmax(0, 1fr); gap: 12px; padding: 14px;
            }
            :is(.zfy-editor-preview, .article-content) .zfy-cloud-logo { width: 44px; height: 44px; }
            :is(.zfy-editor-preview, .article-content) .zfy-shortcode-cloud a.zfy-cloud-download[href] {
                grid-column: 1 / -1; grid-row: auto; width: 100%; min-width: 0;
                white-space: normal; line-height: 1.4; padding: 10px 12px;
            }
            :is(.zfy-editor-preview, .article-content) .zfy-cloud-properties {
                margin: 0 -14px -14px; padding: 12px 14px 14px;
            }
        }
    `;
}
