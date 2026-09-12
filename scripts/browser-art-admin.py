import json
import os
from pathlib import Path
from playwright.sync_api import sync_playwright

base = os.environ.get('ZFY_TEST_URL', 'http://127.0.0.1:8011')
assert base.endswith(':8011'), 'Dedicated browser fixture required'
output = Path(__file__).resolve().parent.parent / 'storage/framework/testing/browser-art-admin'
output.mkdir(parents=True, exist_ok=True)
with sync_playwright() as p:
    browser = p.chromium.launch(headless=True, executable_path=os.environ.get('CHROME_PATH', 'C:/Users/Admin/AppData/Local/Google/Chrome/Application/chrome.exe'))
    context = browser.new_context(viewport={'width': 1440, 'height': 1000})
    page = context.new_page()
    errors, checks = [], []
    page.on('pageerror', lambda error: errors.append(str(error)))
    page.goto(base + '/login', wait_until='networkidle')
    page.locator('[name=login]').fill('admin@zfy-blog.test')
    page.locator('[name=password]').fill('zfy-blog-123456')
    page.get_by_role('button', name='登录', exact=True).click()
    page.wait_for_load_state('networkidle')
    page.goto(base + '/admin', wait_until='networkidle')
    assert page.locator('.el-sub-menu__icon-arrow').first.bounding_box()['width'] <= 14
    page.get_by_role('button', name='收起侧栏', exact=True).click()
    page.wait_for_function("document.querySelector('.zfy-admin-sidebar').getBoundingClientRect().width <= 73")
    page.screenshot(path=str(output / 'collapsed.png'), full_page=True)
    page.reload(wait_until='networkidle')
    assert page.locator('.zfy-admin-shell.is-collapsed').count() == 1
    page.get_by_role('button', name='展开侧栏', exact=True).click()
    page.get_by_role('button', name='全屏', exact=True).click()
    page.wait_for_function('Boolean(document.fullscreenElement)')
    page.get_by_role('button', name='退出全屏', exact=True).click()
    page.wait_for_function('!document.fullscreenElement')
    page.get_by_role('button', name='搜索菜单', exact=True).click()
    page.get_by_role('textbox', name='搜索菜单', exact=True).fill('商品管理')
    if page.locator('.zfy-art-search-results > button').count() == 0:
        page.get_by_role('textbox', name='搜索菜单', exact=True).fill('商品')
    page.locator('.zfy-art-search-results > button').first.click()
    page.wait_for_url('**/admin/products')
    page.wait_for_load_state('networkidle')
    assert page.locator('.zfy-art-page-heading h1').inner_text() == '商品'
    assert page.locator('.zfy-admin-tab').count() == 2
    with page.expect_response(lambda response: '/admin/resources/products?' in response.url and response.status == 200):
        page.get_by_role('button', name='刷新当前页', exact=True).click()
    page.get_by_role('button', name='页签操作', exact=True).click()
    page.get_by_role('menuitem', name='关闭其他页签', exact=True).click()
    assert page.locator('.zfy-admin-tab').count() == 1
    page.get_by_role('button', name='外观设置', exact=True).click()
    page.get_by_role('button', name='主题色 #13A88A', exact=True).click()
    page.locator('.zfy-art-setting').filter(has_text='紧凑表格').locator('.el-switch').click()
    assert page.locator('.zfy-admin-shell.is-compact').count() == 1
    page.locator('.el-drawer__close-btn').click()
    page.reload(wait_until='networkidle')
    assert page.locator('.zfy-admin-shell.is-compact').count() == 1
    assert page.evaluate("getComputedStyle(document.documentElement).getPropertyValue('--zfy-admin-primary').trim()") == '#13A88A'
    page.get_by_role('button', name='外观设置', exact=True).click()
    page.get_by_role('button', name='主题色 #5D87FF', exact=True).click()
    page.locator('.zfy-art-setting').filter(has_text='紧凑表格').locator('.el-switch').click()
    page.locator('.el-drawer__close-btn').click()
    for dark in [False, True]:
        if dark:
            page.get_by_role('button', name='深色模式', exact=True).click()
        page.set_viewport_size({'width': 1440, 'height': 1000})
        page.goto(base + '/admin/products', wait_until='networkidle')
        page.get_by_role('button', name='新建', exact=True).click()
        page.locator('.el-dialog').wait_for()
        page.wait_for_timeout(400)
        page.screenshot(path=str(output / (('dark-' if dark else 'light-') + 'form.png')), full_page=True)
        page.get_by_role('button', name='取消', exact=True).click()
        for width, label in [(1440, 'desktop'), (820, 'tablet'), (390, 'mobile')]:
            page.set_viewport_size({'width': width, 'height': 1000 if width > 900 else 844})
            for index, path in enumerate(['/admin', '/admin/contents', '/admin/products', '/admin/settings-general', '/admin/themes', '/admin/editor']):
                response = page.goto(base + path, wait_until='networkidle')
                if width <= 900:
                    page.wait_for_function("document.querySelector('.zfy-admin-sidebar').getBoundingClientRect().right <= 0")
                metrics = page.evaluate("""() => ({overflow: document.documentElement.scrollWidth > innerWidth + 2, brokenImages: [...document.images].filter(img => !img.complete || img.naturalWidth === 0).map(img => img.getAttribute('src')), alerts: [...document.querySelectorAll('.el-message--error')].map(el => el.innerText)})""")
                checks.append({'viewport': label, 'dark': dark, 'path': path, 'status': response.status, **metrics})
                assert response.status == 200 and not metrics['overflow'] and not metrics['brokenImages'] and not metrics['alerts'], checks[-1]
                page.screenshot(path=str(output / (('dark-' if dark else 'light-') + label + '-' + str(index) + '.png')), full_page=True)
            if width <= 900:
                assert page.locator('.zfy-admin-user-trigger .el-avatar').is_visible()
                page.get_by_role('button', name='打开后台菜单', exact=True).click()
                page.wait_for_function("document.querySelector('.zfy-admin-sidebar').getBoundingClientRect().left >= -1")
                page.screenshot(path=str(output / (('dark-' if dark else 'light-') + label + '-menu.png')))
                page.locator('.zfy-sidebar-close').click()
    page.keyboard.press('Control+k')
    page.get_by_role('textbox', name='搜索菜单', exact=True).fill('settings-general')
    page.get_by_role('textbox', name='搜索菜单', exact=True).press('Enter')
    page.wait_for_url('**/admin/settings-general')
    assert not errors, errors
    report = {'checks': checks, 'errors': errors, 'collapse_search_tabs_preferences_mobile_menu': True}
    (output / 'results.json').write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding='utf-8')
    print(json.dumps({'pages': len(checks), 'errors': errors, 'interactions': 'passed'}))
    browser.close()
