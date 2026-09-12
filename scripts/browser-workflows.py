import json
import os
from pathlib import Path
from playwright.sync_api import sync_playwright

base = os.environ.get('ZFY_TEST_URL', 'http://127.0.0.1:8011')
output = Path(__file__).resolve().parent.parent / 'storage/framework/testing/browser-workflows'
output.mkdir(parents=True, exist_ok=True)
results = []
with sync_playwright() as p:
    browser = p.chromium.launch(headless=True, executable_path=os.environ.get('CHROME_PATH', 'C:/Users/Admin/AppData/Local/Google/Chrome/Application/chrome.exe'))
    context = browser.new_context(viewport={'width': 1440, 'height': 1000}, accept_downloads=True)
    page = context.new_page()
    def login(email, password):
        context.clear_cookies()
        page.goto(base + '/login', wait_until='networkidle')
        page.locator('[name=login]').fill(email)
        page.locator('[name=password]').fill(password)
        page.get_by_role('button', name='登录', exact=True).click()
        page.wait_for_load_state('networkidle')
    login('admin@zfy-blog.test', 'zfy-blog-123456')
    page.goto(base + '/admin/editor', wait_until='networkidle')
    title = 'Browser publish ' + str(__import__('time').time_ns())
    page.get_by_placeholder('请输入文章标题', exact=True).fill(title)
    page.locator('.tiptap').fill('Browser published block content')
    page.get_by_role('button', name='发布文章', exact=True).click()
    page.get_by_role('button', name='查看文章', exact=True).wait_for()
    page.get_by_role('button', name='查看文章', exact=True).click()
    page.wait_for_load_state('networkidle')
    assert 'Browser published block content' in page.locator('body').inner_text()
    results.append({'block_editor_publish': True})
    page.goto(base + '/admin/editor', wait_until='networkidle')
    page.screenshot(path=str(output / 'editor-initial.png'), full_page=True)
    page.locator('input').evaluate_all('(nodes) => nodes.map(n => ({placeholder:n.placeholder,type:n.type}))')
    inputs = page.locator('input').evaluate_all('(nodes) => nodes.map(n => ({placeholder:n.placeholder,type:n.type}))')
    (output / 'editor-inputs.json').write_text(json.dumps(inputs), encoding='utf-8')
    payload = context.request.get(base + '/admin', headers={'Accept': 'application/json'}).json()['payload']
    headers = {'Accept': 'application/json', 'X-CSRF-TOKEN': payload['csrf'], 'X-Requested-With': 'XMLHttpRequest'}
    themes = context.request.get(base + '/admin/themes/configuration', headers=headers).json()['data']
    for theme in themes:
        if not theme['installed']:
            continue
        preview = context.request.post(base + '/admin/themes/' + str(theme['id']) + '/preview', headers=headers, data=theme['values'])
        assert preview.status == 200, preview.text()
        for width, label in [(1440, 'desktop'), (390, 'mobile')]:
            page.set_viewport_size({'width': width, 'height': 1000 if width > 500 else 844})
            response = page.goto(preview.json()['url'], wait_until='networkidle')
            assert response.status == 200
            page.screenshot(path=str(output / (theme['slug'] + '-' + label + '.png')), full_page=True)
            overflow = page.evaluate('document.documentElement.scrollWidth > innerWidth + 2')
            if overflow:
                print(json.dumps({'theme': theme['slug'], 'width': width, 'elements': page.evaluate('''[...document.querySelectorAll('body *')].filter(el=>el.getBoundingClientRect().right>innerWidth+2).slice(0,20).map(el=>({tag:el.tagName,cls:el.className,text:el.innerText?.slice(0,60),width:el.getBoundingClientRect().width}))''')}, ensure_ascii=True))
            assert not overflow
        results.append({'theme_preview': theme['slug'], 'passed': True})
    login('buyer@browser.test', 'browser-test-123456')
    page.set_viewport_size({'width': 1440, 'height': 1000})
    page.goto(base + '/content/browser-resource', wait_until='networkidle')
    if 'Purchased test document' not in page.locator('body').inner_text():
        page.locator('.a-paywall select[name=gateway]').select_option('balance')
        page.get_by_role('button', name='立即购买', exact=True).click()
        page.wait_for_load_state('networkidle')
    page.goto(base + '/content/browser-resource', wait_until='networkidle')
    assert 'Purchased test document' in page.locator('body').inner_text()
    with page.expect_download() as event:
        page.get_by_role('button', name='下载 browser-fixture.txt', exact=True).click()
    download = event.value
    download.save_as(output / 'download.txt')
    assert 'Private browser fixture download' in (output / 'download.txt').read_text()
    results.append({'purchase_and_private_download': True})
    page.goto(base + '/user/orders', wait_until='networkidle')
    refund_form = page.locator('form[action$="/refund"]').first
    if refund_form.count():
        refund_form.locator('xpath=ancestor::details').locator('summary').click()
        refund_form.locator('input[name=reason]').fill('Browser refund verification')
        refund_form.locator('button').click()
        page.wait_for_load_state('networkidle')
    login('admin@zfy-blog.test', 'zfy-blog-123456')
    page.goto(base + '/admin/refunds', wait_until='networkidle')
    row = page.locator('.el-table__row').filter(has_text='Browser refund verification').first
    row.get_by_role('button', name='退款', exact=True).click()
    page.get_by_role('dialog', name='确认处理', exact=True).locator('.el-button--primary').click()
    prompt = page.get_by_role('dialog', name='退款凭证', exact=True)
    prompt.locator('input').fill('browser-test internal refund')
    prompt.locator('.el-button--primary').click()
    page.wait_for_timeout(800)
    page.screenshot(path=str(output / 'refund.png'), full_page=True)
    login('buyer@browser.test', 'browser-test-123456')
    page.goto(base + '/content/browser-resource', wait_until='networkidle')
    assert 'Purchased test document' not in page.locator('body').inner_text()
    results.append({'refund_revokes_access': True})
    (output / 'results.json').write_text(json.dumps(results, indent=2), encoding='utf-8')
    print(json.dumps(results))
    browser.close()
