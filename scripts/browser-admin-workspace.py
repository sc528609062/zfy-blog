import json
import os
import sys
from pathlib import Path
from playwright.sync_api import sync_playwright

base = os.environ.get('ZFY_TEST_URL', 'http://127.0.0.1:8011')
assert base == 'http://127.0.0.1:8011', 'Dedicated SQLite browser fixture required'
output = Path(__file__).resolve().parent.parent / 'storage/framework/testing/browser-admin-workspace'
output.mkdir(parents=True, exist_ok=True)
checks, errors = [], []

with sync_playwright() as p:
    browser = p.chromium.launch(headless=True, executable_path=os.environ.get('CHROME_PATH', 'C:/Users/Admin/AppData/Local/Google/Chrome/Application/chrome.exe'))
    context = browser.new_context(viewport={'width': 1366, 'height': 768})
    page = context.new_page()
    page.on('pageerror', lambda error: errors.append(str(error)))
    page.goto(base + '/login', wait_until='networkidle')
    page.locator('[name=login]').fill('admin@zfy-blog.test')
    page.locator('[name=password]').fill('zfy-blog-123456')
    page.get_by_role('button', name='登录', exact=True).click()
    page.wait_for_load_state('networkidle')
    page.goto(base + '/admin', wait_until='networkidle')
    payload = context.request.get(base + '/admin', headers={'Accept': 'application/json'}).json()['payload']
    urls = sorted(set('/admin' + ('' if entry['key'] == 'dashboard' else '/' + entry['key']) for group in payload['admin_menu'] for entry in group['items']))
    if '--quick' in sys.argv:
        urls = ['/admin', '/admin/contents', '/admin/products', '/admin/orders', '/admin/media', '/admin/settings-general', '/admin/theme-settings', '/admin/page-builder', '/admin/editor']
    for width, height in [(1366, 768), (1440, 1000), (390, 844)]:
        page.set_viewport_size({'width': width, 'height': height})
        for index, path in enumerate(urls):
            response = page.goto(base + path, wait_until='networkidle')
            page.wait_for_timeout(120)
            metrics = page.evaluate('''() => {
                const top = document.querySelector('.zfy-admin-topbar')?.getBoundingClientRect();
                const pagination = [...document.querySelectorAll('.admin-pagination')].find(el => el.offsetHeight)?.getBoundingClientRect();
                const region = [...document.querySelectorAll('.admin-table-region')].find(el => el.offsetHeight)?.getBoundingClientRect();
                return {
                    overflowX: document.documentElement.scrollWidth > innerWidth + 2,
                    overflowY: document.documentElement.scrollHeight > innerHeight + 2,
                    top: top?.top,
                    paginationVisible: !pagination || (pagination.top >= 0 && pagination.bottom <= innerHeight),
                    tableHeight: region?.height ?? null,
                    alerts: [...document.querySelectorAll('.el-message--error')].map(el => el.innerText)
                };
            }''')
            checks.append({'width': width, 'height': height, 'path': path, 'status': response.status, **metrics})
            page.screenshot(path=str(output / f'{width}-{index}.png'))
            if page.locator('.admin-pagination:visible').count():
                assert page.locator('.admin-pagination:visible .el-pagination').is_visible(), checks[-1]
                for action in page.locator('.el-table__body tr').first.locator('.el-table-fixed-column--right button').all():
                    box = action.bounding_box()
                    assert box and 0 <= box['x'] and box['x'] + box['width'] <= width, (checks[-1], box)
            page.mouse.move(width - 30, height - 100)
            page.mouse.wheel(0, 1200)
            assert page.locator('.zfy-admin-topbar').bounding_box()['y'] == 0, checks[-1]
            if page.locator('.admin-record-dialog:visible').count():
                dialog = page.locator('.admin-record-dialog:visible')
                assert dialog.bounding_box()['height'] <= height - 19
                assert dialog.locator('.el-dialog__footer').bounding_box()['y'] < height
    report = {'checks': checks, 'errors': errors}
    (output / 'results.json').write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding='utf-8')
    failures = [item for item in checks if item['status'] != 200 or item['overflowX'] or item['overflowY'] or item['top'] != 0 or not item['paginationVisible'] or item['alerts'] or (item['tableHeight'] is not None and item['tableHeight'] < 140)]
    print(json.dumps({'pages': len(checks), 'failures': failures, 'errors': errors}, ensure_ascii=True))
    browser.close()
    assert not failures and not errors
