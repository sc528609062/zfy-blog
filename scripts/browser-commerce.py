import json
import os
import time
from pathlib import Path
from playwright.sync_api import sync_playwright

base = os.environ.get('ZFY_TEST_URL', 'http://127.0.0.1:8011')
assert base.endswith(':8011'), 'Dedicated browser fixture required'
output = Path(__file__).resolve().parent.parent / 'storage/framework/testing/browser-commerce'
output.mkdir(parents=True, exist_ok=True)
with sync_playwright() as p:
    browser = p.chromium.launch(headless=True, executable_path=os.environ.get('CHROME_PATH', 'C:/Users/Admin/AppData/Local/Google/Chrome/Application/chrome.exe'))
    context = browser.new_context(viewport={'width': 1440, 'height': 1000})
    page = context.new_page()
    errors = []
    page.on('pageerror', lambda error: errors.append(str(error)))
    def login(email, password):
        context.clear_cookies()
        page.goto(base + '/login', wait_until='networkidle')
        page.locator('[name=login]').fill(email)
        page.locator('[name=password]').fill(password)
        page.get_by_role('button', name='登录', exact=True).click()
        page.wait_for_load_state('networkidle')
    def headers():
        return {'Accept': 'application/json', 'X-CSRF-TOKEN': page.locator('meta[name=csrf-token]').get_attribute('content'), 'X-Requested-With': 'XMLHttpRequest'}
    login('buyer@browser.test', 'browser-test-123456')
    page.goto(base + '/shop', wait_until='networkidle')
    product = page.locator('article').filter(has=page.get_by_role('heading', name='创作笔记本（演示）', exact=True)).last
    product.get_by_role('button', name='加入购物车', exact=True).click()
    page.wait_for_load_state('networkidle')
    assert '/cart' in page.url
    address_form = page.locator('form[action="/user/addresses"]')
    address_form.locator('xpath=ancestor::details').locator('summary').click()
    for name, value in {'recipient': 'Browser Recipient', 'phone': '13800138000', 'region': '测试省测试市', 'address': '测试路 1 号', 'postal_code': '100000'}.items():
        address_form.locator('[name=' + name + ']').fill(value)
    address_form.locator('input[type=checkbox]').check()
    address_form.get_by_role('button', name='添加地址', exact=True).click()
    page.wait_for_load_state('networkidle')
    checkout = page.locator('form[action="/cart/checkout"]')
    checkout.locator('[name=gateway]').select_option('balance')
    checkout.get_by_role('button', name='提交订单', exact=True).click()
    page.wait_for_load_state('networkidle')
    row = page.locator('tr').filter(has_text='创作笔记本（演示）').first
    order_no = row.locator('td').first.inner_text().strip()
    row.get_by_role('button', name='支付', exact=True).click()
    page.wait_for_load_state('networkidle')
    assert '已支付' in page.locator('tr').filter(has_text=order_no).inner_text()
    login('admin@zfy-blog.test', 'zfy-blog-123456')
    page.goto(base + '/admin/shipments', wait_until='networkidle')
    payload = context.request.get(base + '/admin', headers={'Accept': 'application/json'}).json()['payload']
    request_headers = {'Accept': 'application/json', 'X-CSRF-TOKEN': payload['csrf'], 'X-Requested-With': 'XMLHttpRequest'}
    orders = context.request.get(base + '/admin/commerce/orders', headers=request_headers).json()['data']['data']
    order_id = next(item['id'] for item in orders if item['order_no'] == order_no)
    shipments = context.request.get(base + '/admin/resources/shipments', headers=request_headers).json()['data']['data']
    shipment = next(item for item in shipments if item['order_id'] == order_id)
    response = context.request.patch(base + '/admin/resources/shipments/' + str(shipment['id']), headers=request_headers, data={'status': 'shipped', 'carrier': 'Browser Express', 'tracking_no': 'OUT-' + str(time.time_ns())})
    assert response.status == 200, response.text()
    page.reload(wait_until='networkidle')
    page.screenshot(path=str(output / 'shipment.png'), full_page=True)
    login('buyer@browser.test', 'browser-test-123456')
    page.goto(base + '/user/orders', wait_until='networkidle')
    row = page.locator('tr').filter(has_text=order_no)
    row.get_by_role('button', name='确认收货', exact=True).click()
    page.wait_for_load_state('networkidle')
    row = page.locator('tr').filter(has_text=order_no)
    row.get_by_text('申请退款', exact=True).click()
    reason = 'Physical return ' + str(time.time_ns())
    row.locator('[name=reason]').fill(reason)
    row.get_by_role('button', name='提交申请', exact=True).click()
    page.wait_for_load_state('networkidle')
    row = page.locator('tr').filter(has_text=order_no)
    row.get_by_text('填写退货物流', exact=True).click()
    row.locator('[name=carrier]').fill('Browser Return Express')
    row.locator('[name=tracking_no]').fill('RETURN-' + str(time.time_ns()))
    row.get_by_role('button', name='提交物流', exact=True).click()
    page.wait_for_load_state('networkidle')
    login('admin@zfy-blog.test', 'zfy-blog-123456')
    page.goto(base + '/admin/refunds', wait_until='networkidle')
    row = page.locator('.el-table__row').filter(has_text=reason)
    for action, prompt_title, reference in [('退货验收', '确认退货入库', 'Browser return inspected'), ('退款', '退款凭证', 'Browser balance refund')]:
        row.get_by_role('button', name=action, exact=True).click()
        page.get_by_role('dialog', name='确认处理', exact=True).locator('.el-button--primary').click()
        prompt = page.get_by_role('dialog', name=prompt_title, exact=True)
        prompt.locator('input').fill(reference)
        prompt.locator('.el-button--primary').click()
        page.wait_for_timeout(700)
    assert '已退款' in row.inner_text()
    page.screenshot(path=str(output / 'return-refund.png'), full_page=True)
    login('buyer@browser.test', 'browser-test-123456')
    page.goto(base + '/user/messages', wait_until='networkidle')
    page.locator('form[action$="/user/messages"] [name=recipient_id]').fill('1')
    message = 'Browser private message ' + str(time.time_ns())
    page.locator('form[action$="/user/messages"] [name=body]').fill(message)
    page.get_by_role('button', name='发送私信', exact=True).click()
    page.wait_for_load_state('networkidle')
    assert message in page.locator('body').inner_text()
    login('admin@zfy-blog.test', 'zfy-blog-123456')
    page.goto(base + '/user/messages', wait_until='networkidle')
    assert message in page.locator('body').inner_text()
    page.set_viewport_size({'width': 390, 'height': 844})
    page.screenshot(path=str(output / 'messages-mobile.png'), full_page=True)
    assert not page.evaluate('document.documentElement.scrollWidth > innerWidth + 2')
    assert not errors, errors
    report = {'cart_address_payment': True, 'shipment_receive_return_refund': True, 'private_message_delivery': True, 'errors': errors}
    (output / 'results.json').write_text(json.dumps(report), encoding='utf-8')
    print(json.dumps(report))
    browser.close()
