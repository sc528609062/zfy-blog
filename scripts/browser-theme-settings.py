import json
import os
import sys
from pathlib import Path
from playwright.sync_api import sync_playwright, expect

base = os.environ.get('ZFY_TEST_URL', 'http://127.0.0.1:8011')
assert base == 'http://127.0.0.1:8011', 'Dedicated browser fixture required'
output = Path(__file__).resolve().parent.parent / 'storage/framework/testing/theme-settings'
output.mkdir(parents=True, exist_ok=True)

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
    payload = context.request.get(base + '/admin/theme-settings', headers={'Accept': 'application/json'}).json()['payload']
    headers = {'Accept': 'application/json', 'X-CSRF-TOKEN': payload['csrf'], 'X-Requested-With': 'XMLHttpRequest'}
    themes = [item for item in context.request.get(base + '/admin/themes/configuration').json()['data'] if item['slug'] in ['style-a-blue-gaming', 'style-b-marketplace', 'style-c-creative']]
    active = next(item for item in themes if item['is_active'])
    layouts = context.request.get(base + '/admin/page-builder', headers={'Accept': 'application/json'}).json()['payload']['layouts']
    checks = []

    def save(theme, values):
        response = context.request.put(base + '/admin/themes/' + str(theme['id']) + '/configuration', headers=headers, data=values)
        assert response.ok, response.text()

    def activate(theme):
        response = context.request.post(base + '/admin/themes/activate', headers=headers, data={'slug': theme['slug']})
        assert response.ok, response.text()

    def responsive(label):
        assert not page.evaluate('document.documentElement.scrollWidth > innerWidth + 2'), label
        page.locator('.site-hero-image, .a-item-cover img, .site-logo').evaluate_all('(images) => images.forEach(i => i.loading = "eager")')
        page.wait_for_function('Array.from(document.querySelectorAll(".site-hero-image, .a-item-cover img, .site-logo")).every(i => i.complete)')
        broken = page.locator('.site-hero-image, .a-item-cover img, .site-logo').evaluate_all('(images) => images.filter(i => !i.complete || !i.naturalWidth).map(i => i.src)')
        assert not broken, (label, broken)
        page.screenshot(path=str(output / (label + '.png')), full_page=True)
        checks.append(label)

    try:
        for layout in layouts:
            schema = json.loads(layout['schema'])
            schema['enabled'] = False
            assert context.request.post(base + layout['save_url'], headers=headers, data={'title': layout['title'], 'status': layout['status'], 'schema': json.dumps(schema)}).ok
        for theme in ([] if '--admin-only' in sys.argv else themes):
            save(theme, theme['defaults'])
            activate(theme)
            for width, device in [(1440, 'desktop'), (820, 'tablet'), (390, 'mobile')]:
                page.set_viewport_size({'width': width, 'height': 1000 if width > 500 else 844})
                for path, name in [('/', 'home'), ('/posts', 'posts'), ('/content/browser-resource', 'detail'), ('/vip', 'vip'), ('/shop', 'shop'), ('/user', 'user')]:
                    response = page.goto(base + path, wait_until='networkidle')
                    assert response.ok, (theme['slug'], path, response.status)
                    responsive(theme['slug'] + '-' + device + '-' + name)
                if width == 390:
                    page.locator('.zfy-mobile-menu > summary').click()
                    expect(page.locator('.zfy-mobile-menu nav')).to_be_visible()
            save(theme, {**theme['defaults'], 'sidebar_position': 'left', 'sidebar_width': 320, 'card_radius': 0, 'cover_ratio': '1/1', 'hero_title': 'Theme preview title', 'logo_url': '/theme-assets/a-avatar.png'})
            page.set_viewport_size({'width': 1440, 'height': 1000})
            page.goto(base + '/', wait_until='networkidle')
            main = page.locator('.a-two-col > .a-card-panel').bounding_box()
            side = page.locator('.a-two-col > .a-side-stack').bounding_box()
            assert side['x'] < main['x'] and abs(side['width'] - 320) < 2
            expect(page.locator('.a-hero h1')).to_have_text('Theme preview title')
            responsive(theme['slug'] + '-custom-left')

        activate(active)
        save(active, active['defaults'])
        page.goto(base + '/admin/theme-settings', wait_until='networkidle')
        form = page.locator('.theme-settings')
        brand = form.get_by_label('站点标识', exact=True)
        brand.fill('Unsaved preview brand')
        form.locator('.theme-settings-nav').get_by_role('button', name='首页内容').click()
        expect(form.get_by_label('横幅标题', exact=True)).to_be_visible()
        form.get_by_role('switch', name='显示首页横幅', exact=True).locator('..').click()
        expect(form.get_by_label('横幅标题', exact=True)).to_be_hidden()
        form.get_by_role('button', name='重置此分组').click()
        page.get_by_role('dialog').get_by_role('button', name='恢复默认', exact=True).click()
        expect(form.get_by_label('横幅标题', exact=True)).to_be_visible()
        form.get_by_label('搜索主题设置', exact=True).fill('站点标识')
        expect(brand).to_have_value('Unsaved preview brand')
        with page.expect_popup() as popup_info:
            form.get_by_role('button', name='预览', exact=True).click()
        preview = popup_info.value
        preview.wait_for_load_state('networkidle')
        expect(preview.locator('.a-brand strong')).to_have_text('Unsaved preview brand')
        preview.close()
        live = context.request.get(base + '/').text()
        assert 'Unsaved preview brand' not in live

        page.locator('.zfy-admin-sidebar').get_by_text('主题', exact=True).click()
        page.get_by_role('dialog').get_by_role('button', name='继续编辑', exact=True).click()
        expect(brand).to_have_value('Unsaved preview brand')
        assert page.url.endswith('/admin/theme-settings')
        form.get_by_role('button', name='保存配置', exact=True).click()
        expect(form.locator('.configuration-actions > span')).to_have_text('已保存')
        page.reload(wait_until='networkidle')
        expect(form.get_by_label('站点标识', exact=True)).to_have_value('Unsaved preview brand')
        for width, device in [(1440, 'desktop'), (390, 'mobile')]:
            page.set_viewport_size({'width': width, 'height': 1000 if width > 500 else 844})
            for group in ['品牌导航', '配色布局', '首页内容', '列表卡片', '文章阅读', '页脚信息']:
                form.locator('.theme-settings-nav').get_by_role('button', name=group, exact=True).click()
                assert not page.evaluate('document.documentElement.scrollWidth > innerWidth + 2'), (device, group)
            page.screenshot(path=str(output / ('admin-' + device + '.png')), full_page=True)
        page.set_viewport_size({'width': 1440, 'height': 1000})
        page.get_by_role('button', name='深色模式', exact=True).click()
        page.wait_for_timeout(350)
        page.screenshot(path=str(output / 'admin-dark.png'), full_page=True)
        page.get_by_role('button', name='浅色模式', exact=True).click()
        form.locator('.theme-settings-nav').get_by_role('button', name='品牌导航', exact=True).click()
        form.locator('.zfy-cover-field').first.get_by_role('button', name='选择图片', exact=True).click()
        expect(page.locator('.el-dialog:visible')).to_be_visible()
        page.locator('.el-dialog:visible').get_by_role('button', name='取消', exact=True).click()
        brand.fill('Unsaved navigation check')
        page.get_by_role('button', name='刷新当前页', exact=True).click()
        page.get_by_role('dialog', name='未保存的更改').get_by_role('button', name='继续编辑', exact=True).click()
        expect(brand).to_have_value('Unsaved navigation check')
        page.locator('.zfy-admin-sidebar').get_by_text('主题', exact=True).click()
        page.get_by_role('dialog', name='未保存的更改').get_by_role('button', name='离开', exact=True).click()
        page.wait_for_url('**/admin/themes')
        page.get_by_role('button', name='主题设置', exact=True).click()
        page.wait_for_url('**/admin/theme-settings')
        expect(form.get_by_label('站点标识', exact=True)).to_have_value('Unsaved preview brand')
        form.get_by_label('站点标识', exact=True).fill('Unsaved history check')
        page.evaluate('history.back()')
        page.get_by_role('dialog', name='未保存的更改').get_by_role('button', name='继续编辑', exact=True).click()
        expect(form.get_by_label('站点标识', exact=True)).to_have_value('Unsaved history check')
        assert page.url.endswith('/admin/theme-settings')
        assert not errors, errors
        report = {'responsive_pages': len(checks), 'preview_isolated': True, 'dependent_fields': True, 'group_reset_preserves_other_groups': True, 'unsaved_navigation_protected': True, 'save_reload': True, 'dark_mode': True, 'media_library': True, 'console_errors': errors}
        (output / 'results.json').write_text(json.dumps(report), encoding='utf-8')
        print(json.dumps(report))
    finally:
        for layout in layouts:
            assert context.request.post(base + layout['save_url'], headers=headers, data={'title': layout['title'], 'status': layout['status'], 'schema': layout['schema']}).ok
        for theme in themes:
            save(theme, theme['values'])
        activate(active)
        browser.close()
