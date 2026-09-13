import json
import os
from pathlib import Path
from playwright.sync_api import sync_playwright

base = os.environ.get('ZFY_TEST_URL', 'http://127.0.0.1:8011')
assert base.endswith(':8011'), 'Dedicated browser fixture required'
output = Path(__file__).resolve().parent.parent / 'storage/framework/testing/browser-appearance-defaults'
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
    payload = context.request.get(base + '/admin/page-builder', headers={'Accept': 'application/json'}).json()['payload']
    headers = {'Accept': 'application/json', 'X-CSRF-TOKEN': payload['csrf'], 'X-Requested-With': 'XMLHttpRequest'}
    original_layout = payload['layouts'][0]
    themes = context.request.get(base + '/admin/themes/configuration').json()['data']
    theme = themes[0]
    try:
        # Cancel leaves edits intact; confirm only changes the form until save.
        page.goto(base + '/admin/themes', wait_until='networkidle')
        form = page.locator('.el-tab-pane:visible').last
        brand = form.get_by_label('站点标识', exact=True)
        brand.fill('Unsaved brand')
        form.get_by_role('button', name='恢复默认', exact=True).click()
        page.get_by_role('dialog').get_by_role('button', name='取消', exact=True).click()
        assert brand.input_value() == 'Unsaved brand'
        form.get_by_role('button', name='恢复默认', exact=True).click()
        page.get_by_role('dialog').get_by_role('button', name='恢复默认', exact=True).click()
        assert brand.input_value() == theme['defaults']['logo_text']
        unchanged = context.request.get(base + '/admin/themes/configuration').json()['data'][0]
        assert unchanged['values'] == theme['values']
        form.get_by_role('button', name='保存配置', exact=True).click()
        page.get_by_text('主题配置已保存', exact=True).wait_for()
        page.reload(wait_until='networkidle')
        assert page.locator('.el-tab-pane:visible').last.get_by_label('站点标识', exact=True).input_value() == theme['defaults']['logo_text']
        for width, label in [(1440, 'desktop'), (390, 'mobile')]:
            page.set_viewport_size({'width': width, 'height': 1000 if width > 500 else 844})
            page.wait_for_timeout(250)
            page.locator('.el-tab-pane:visible').last.get_by_role('button', name='恢复默认', exact=True).scroll_into_view_if_needed()
            page.screenshot(path=str(output / ('theme-' + label + '.png')), full_page=True)
            assert not page.evaluate('document.documentElement.scrollWidth > innerWidth + 2')
            assert page.locator('.theme-settings').evaluate('(el) => el.getBoundingClientRect().right <= innerWidth')
        page.set_viewport_size({'width': 1440, 'height': 1000})
        changed = {'title': original_layout['title'], 'status': 'published', 'schema': json.dumps({'enabled': True, 'blocks': [{'type': 'html', 'html': 'RESET ACCEPTANCE CUSTOM'}]})}
        assert context.request.post(base + original_layout['save_url'], headers=headers, data=changed).ok
        page.goto(base + '/', wait_until='networkidle')
        assert page.locator('body').inner_text().find('RESET ACCEPTANCE CUSTOM') >= 0
        page.goto(base + '/admin/page-builder', wait_until='networkidle')
        form = page.locator('.el-tab-pane:visible').last
        form.get_by_role('button', name='恢复默认', exact=True).click()
        page.get_by_role('dialog').get_by_role('button', name='取消', exact=True).click()
        assert form.get_by_role('switch').get_attribute('aria-checked') == 'true'
        form.get_by_role('button', name='恢复默认', exact=True).click()
        page.get_by_role('dialog').get_by_role('button', name='恢复默认', exact=True).click()
        assert form.get_by_role('switch').get_attribute('aria-checked') == 'false'
        current = context.request.get(base + '/admin/page-builder', headers={'Accept': 'application/json'}).json()['payload']['layouts'][0]
        assert json.loads(current['schema'])['enabled'] is True
        form.get_by_role('button', name='保存布局', exact=True).click()
        page.get_by_text('布局已保存', exact=True).wait_for()
        page.reload(wait_until='networkidle')
        assert page.locator('.el-tab-pane:visible').last.get_by_role('switch').get_attribute('aria-checked') == 'false'
        for width, label in [(1440, 'desktop'), (390, 'mobile')]:
            page.set_viewport_size({'width': width, 'height': 1000 if width > 500 else 844})
            page.locator('.el-tab-pane:visible').last.get_by_role('button', name='恢复默认', exact=True).scroll_into_view_if_needed()
            page.screenshot(path=str(output / ('layout-' + label + '.png')), full_page=True)
            assert not page.evaluate('document.documentElement.scrollWidth > innerWidth + 2')
        page.goto(base + '/', wait_until='networkidle')
        assert 'RESET ACCEPTANCE CUSTOM' not in page.locator('body').inner_text()
        assert page.locator('.builder-module').count() == 0
        assert not errors, errors
        report = {'theme_reset_cancel_confirm_save_reload': True, 'layout_reset_cancel_confirm_save_reload': True, 'native_home_restored': True, 'responsive': True, 'errors': errors}
        (output / 'results.json').write_text(json.dumps(report), encoding='utf-8')
        print(json.dumps(report))
    finally:
        restored = {'title': original_layout['title'], 'status': original_layout['status'], 'schema': original_layout['schema']}
        assert context.request.post(base + original_layout['save_url'], headers=headers, data=restored).ok
        assert context.request.put(base + '/admin/themes/' + str(theme['id']) + '/configuration', headers=headers, data=theme['values']).ok
        browser.close()
