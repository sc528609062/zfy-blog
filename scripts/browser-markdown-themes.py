import json
import os
from pathlib import Path
from playwright.sync_api import sync_playwright

base = os.environ.get('ZFY_TEST_URL', 'http://127.0.0.1:8011')
assert base.endswith(':8011'), 'Dedicated browser fixture required'
root = Path(__file__).resolve().parent.parent
output = root / 'storage/framework/testing/browser-markdown-themes'
output.mkdir(parents=True, exist_ok=True)
markdown = '''# Markdown Theme

## Heading sample

Body paragraph with **bold**, *emphasis*, `inline code`, and [a link](https://example.com).

> A blockquote with a paragraph.

| Name | Value |
| --- | --- |
| Table | Stable |

```javascript
const message = "theme isolation";
console.log(message);
```

{zfy-button title="Download button" url="https://example.com" /}

{zfy-cloud title="Download resource" url="https://pan.baidu.com/s/demo" password="1234" /}

{zfy-tabs}
{zfy-tab title="First"}First body{/zfy-tab}
{zfy-tab title="Second"}Second body{/zfy-tab}
{/zfy-tabs}

{zfy-collapse}{zfy-collapse-item title="Expandable"}Hidden body{/zfy-collapse-item}{/zfy-collapse}
'''
style_snapshot = '''(selectors) => Object.fromEntries(selectors.map(selector => {
    const node = document.querySelector(selector);
    if (!node) throw new Error('Missing node ' + selector);
    const s = getComputedStyle(node);
    return [selector, Object.fromEntries(['color','backgroundColor','fontSize','fontFamily','lineHeight',
        'padding','borderWidth','borderRadius','display','textDecoration','fontWeight'].map(key => [key,s[key]]))];
}))'''

def choose(page, label, option):
    control = page.get_by_role('combobox', name=label, exact=True)
    page.locator('.el-select').filter(has=control).click()
    page.locator('[id="' + control.get_attribute('aria-controls') + '"]').get_by_role('option', name=option, exact=True).click()
    page.wait_for_timeout(220)

with sync_playwright() as p:
    browser = p.chromium.launch(headless=True, executable_path=os.environ.get('CHROME_PATH', 'C:/Users/Admin/AppData/Local/Google/Chrome/Application/chrome.exe'))
    context = browser.new_context(viewport={'width': 1440, 'height': 1000})
    page = context.new_page()
    errors = []
    page.on('pageerror', lambda error: errors.append(str(error)))
    page.goto(base + '/login', wait_until='networkidle')
    page.locator('[name=login]').fill('admin@zfy-blog.test')
    page.locator('[name=password]').fill('zfy-blog-123456')
    page.get_by_role('button', name='登录', exact=True).click()
    page.wait_for_load_state('networkidle')
    page.goto(base + '/admin/editor', wait_until='networkidle')
    page.get_by_text('Markdown', exact=True).click()
    page.locator('.zfy-editor-controlbar input[type=file]').set_input_files({'name': 'themes.md', 'mimeType': 'text/markdown', 'buffer': markdown.encode()})
    page.locator('.zfy-editor-preview .hljs-keyword').first.wait_for()
    page.wait_for_timeout(500)
    selectors = ['.zfy-admin-topbar', '.zfy-editor-controlbar .el-select__wrapper', '.zfy-editor-toolbar',
        '.zfy-shortcode-button', '.zfy-cloud-download', '.zfy-cloud-password code', '.enlighter-toolbar', '.enlighter-btn', '.enlighter.hljs']
    # Keep the code palette fixed while cycling every body theme.
    choose(page, '代码主题', 'Atom One Dark')
    baseline = page.evaluate(style_snapshot, selectors)
    body_styles = set()
    choose(page, '正文主题', '掘金')
    page.locator('.el-select').filter(has=page.get_by_role('combobox', name='正文主题', exact=True)).click()
    theme_names = page.get_by_role('option').all_text_contents()
    page.keyboard.press('Escape')
    tabs = page.locator('.zfy-tabs-head-item')
    assert tabs.count() == 2
    tabs.last.click()
    page.locator('.zfy-shortcode-collapse .zfy-shortcode-title').click()
    collapse_open = page.locator('.zfy-collapse-item.is-open').count()
    page.locator('.zfy-editor-preview').evaluate('(el) => { window.previewHeading = el.querySelector("h2"); }')
    for name in theme_names:
        choose(page, '正文主题', name.strip())
        current = page.evaluate(style_snapshot, selectors)
        assert current == baseline, {'theme': name, 'changed': {key: [baseline[key], current[key]] for key in baseline if baseline[key] != current[key]}}
        body_styles.add(json.dumps(page.evaluate(style_snapshot, ['.zfy-editor-preview h2']), sort_keys=True))
        assert page.locator('.zfy-tabs-head-item.is-active').inner_text() == 'Second'
        assert page.locator('.zfy-collapse-item.is-open').count() == collapse_open
        assert page.evaluate('window.previewHeading === document.querySelector(".zfy-editor-preview h2")')
        assert page.locator('style[id^=zfy-markdown-theme-]').count() == 1
        assert page.locator('style[id^=zfy-code-theme-]').count() == 1
    assert len(body_styles) > 20, len(body_styles)
    code_baseline = page.evaluate(style_snapshot, selectors[:-1])
    page.locator('.el-select').filter(has=page.get_by_role('combobox', name='代码主题', exact=True)).click()
    code_names = page.get_by_role('option').all_text_contents()
    page.keyboard.press('Escape')
    code_styles = set()
    for name in code_names:
        choose(page, '代码主题', name.strip())
        assert page.evaluate(style_snapshot, selectors[:-1]) == code_baseline, name
        code_styles.add(json.dumps(page.evaluate(style_snapshot, ['.enlighter.hljs', '.hljs-keyword']), sort_keys=True))
        assert page.locator('style[id^=zfy-code-theme-]').count() == 1
    assert len(code_styles) == 9
    choose(page, '正文主题', 'GitHub')
    choose(page, '代码主题', 'GitHub Dark')
    assert page.locator('.zfy-cloud-content').evaluate('(el) => el.getBoundingClientRect().width') > 80
    for width, label in [(1440, 'desktop'), (390, 'mobile')]:
        page.set_viewport_size({'width': width, 'height': 1000 if width > 500 else 844})
        page.wait_for_timeout(350)
        if width < 500:
            assert page.locator('.zfy-editor-outline').count() == 0
            page.get_by_role('button', name='文章目录', exact=True).click()
            page.get_by_role('button', name='关闭文章目录', exact=True).click()
            assert page.locator('.zfy-editor-outline').count() == 0
        assert page.get_by_role('combobox', name='代码主题', exact=True).evaluate('(el) => el.getBoundingClientRect().right <= innerWidth')
        page.locator('.zfy-editor-preview-pane').evaluate('(el) => { el.scrollTop = 0; }')
        page.screenshot(path=str(output / (label + '.png')), full_page=True)
        assert not page.evaluate('document.documentElement.scrollWidth > innerWidth + 2')
    # SPA teardown must release themes without touching other admin pages.
    page.set_viewport_size({'width': 1440, 'height': 1000})
    page.get_by_role('button', name='搜索菜单', exact=True).click()
    page.get_by_role('textbox', name='搜索菜单', exact=True).fill('页面构建器')
    page.locator('.zfy-art-search-results > button').first.click()
    page.get_by_role('button', name='保存布局', exact=True).first.wait_for()
    assert page.locator('style[id^=zfy-markdown-theme-], style[id^=zfy-code-theme-]').count() == 0
    manifest = json.loads((root / 'public/build/manifest.json').read_text(encoding='utf-8'))
    module_url = base + '/build/' + manifest['resources/js/shared/markdownEnhancements.ts']['file']
    awaitable_test = '''async (url) => {
        const { enhanceMarkdownContent } = await import(url);
        const html = '<h2>Multi instance</h2><pre class="wp-block-zibllblock-enlighter"><div class="enlighter"></div><code class="enlighter-origin" data-enlighter-language="javascript">const x = "text";</code></pre>';
        const first = document.createElement('article');
        const second = document.createElement('article');
        first.innerHTML = second.innerHTML = html;
        document.body.append(first, second);
        const cleanupFirst = await enhanceMarkdownContent(first, { markdownTheme:'github', codeTheme:'github' });
        const before = getComputedStyle(first.querySelector('.hljs')).backgroundColor;
        const cleanupSecond = await enhanceMarkdownContent(second, { markdownTheme:'mk-cute', codeTheme:'atom-one-dark' });
        if (before !== getComputedStyle(first.querySelector('.hljs')).backgroundColor) throw Error('Code palette leaked');
        if (before === getComputedStyle(second.querySelector('.hljs')).backgroundColor) throw Error('Code palette did not change');
        cleanupSecond();
        if (!document.getElementById('zfy-markdown-theme-github')) throw Error('Active instance lost its style');
        cleanupFirst(); cleanupFirst();
        first.remove(); second.remove();
        if (document.querySelector('style[id^="zfy-markdown-theme-"], style[id^="zfy-code-theme-"]')) throw Error('Style leak');
        return true;
    }'''
    assert page.evaluate(awaitable_test, module_url)
    assert not errors, errors
    report = {'body_themes': len(theme_names), 'code_themes': len(code_names), 'component_styles_stable': True, 'interaction_state_preserved': True, 'multiple_instances': True, 'style_cleanup': True, 'errors': errors}
    (output / 'results.json').write_text(json.dumps(report), encoding='utf-8')
    print(json.dumps(report))
    browser.close()
