import json
import os
from pathlib import Path
from playwright.sync_api import sync_playwright

base = os.environ.get('ZFY_TEST_URL', 'http://127.0.0.1:8011')
output = Path(__file__).resolve().parent.parent / 'storage/framework/testing/browser-admin'
output.mkdir(parents=True, exist_ok=True)
results, errors = [], []
with sync_playwright() as p:
    browser = p.chromium.launch(headless=True, executable_path=os.environ.get('CHROME_PATH', 'C:/Users/Admin/AppData/Local/Google/Chrome/Application/chrome.exe'))
    context = browser.new_context(viewport={'width': 1440, 'height': 1000})
    page = context.new_page()
    page.on('pageerror', lambda error: errors.append(str(error)))
    page.goto(base + '/login', wait_until='networkidle')
    page.locator('[name=login]').fill('admin@zfy-blog.test')
    page.locator('[name=password]').fill('zfy-blog-123456')
    page.get_by_role('button', name='登录', exact=True).click()
    page.wait_for_load_state('networkidle')
    page.goto(base + '/admin', wait_until='networkidle')
    payload = context.request.get(base + '/admin', headers={'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}).json()['payload']
    (output / 'payload-keys.json').write_text(json.dumps(list(payload.keys())), encoding='utf-8')
    urls = sorted(set(['/admin'] + page.eval_on_selector_all('a[href^="/admin"]', '(nodes) => nodes.map(a => a.getAttribute("href"))')))
    pages = [item for group in payload.get('admin_menu', []) for item in group.get('items', [])]
    if isinstance(pages, dict):
        pages = list(pages.values())
    for entry in pages:
        if isinstance(entry, dict) and entry.get('key'):
            urls.append('/admin' + ('' if entry['key'] == 'dashboard' else '/' + entry['key']))
    for width, label in [(1440, 'desktop'), (390, 'mobile')]:
        page.set_viewport_size({'width': width, 'height': 1000 if width > 500 else 844})
        for index, path in enumerate(sorted(set(urls))):
            response = page.goto(base + path, wait_until='networkidle')
            metrics = page.evaluate('''() => ({overflow: document.documentElement.scrollWidth > innerWidth + 2, brokenImages: [...document.images].filter(img => img.naturalWidth === 0).map(img => img.getAttribute('src')), alerts: [...document.querySelectorAll('.el-message--error')].map(el => el.innerText)})''')
            page.screenshot(path=str(output / (label + '-' + str(index) + '.png')), full_page=True)
            results.append({'viewport': label, 'path': path, 'status': response.status, **metrics})
    report = {'pages': results, 'errors': errors}
    (output / 'results.json').write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding='utf-8')
    print(json.dumps(report, ensure_ascii=True))
    browser.close()
