import json
import os
import time
from pathlib import Path
from playwright.sync_api import sync_playwright, expect

base = 'http://127.0.0.1:8011'
output = Path(__file__).resolve().parent.parent / 'storage/framework/testing/browser-admin-workspace'
output.mkdir(parents=True, exist_ok=True)
with sync_playwright() as p:
    browser = p.chromium.launch(headless=True, executable_path=os.environ.get('CHROME_PATH', 'C:/Users/Admin/AppData/Local/Google/Chrome/Application/chrome.exe'))
    context = browser.new_context(viewport={'width': 1366, 'height': 768})
    page = context.new_page()
    errors, contents, tags, requests = [], [], [], []
    page.on('pageerror', lambda error: errors.append(str(error)))
    page.goto(base + '/login', wait_until='networkidle')
    page.locator('[name=login]').fill('admin@zfy-blog.test')
    page.locator('[name=password]').fill('zfy-blog-123456')
    page.get_by_role('button', name='登录', exact=True).click()
    page.wait_for_load_state('networkidle')
    payload = context.request.get(base + '/admin', headers={'Accept': 'application/json'}).json()['payload']
    headers = {'Accept': 'application/json', 'X-CSRF-TOKEN': payload['csrf'], 'X-Requested-With': 'XMLHttpRequest'}
    marker = 'Workspace-' + str(time.time_ns())
    page.on('request', lambda request: requests.append(request.url) if '/admin/contents?' in request.url or '/admin/resources/tags?' in request.url else None)

    def settle():
        page.wait_for_load_state('networkidle')
        page.wait_for_timeout(150)

    def choose_size(size):
        page.locator('.admin-pagination .el-select').click()
        page.get_by_role('option', name=f'{size}条/页', exact=True).click()
        settle()

    def rows():
        return page.locator('.admin-table-region .el-table__body tbody > tr')

    try:
        for index in range(21):
            response = context.request.post(base + '/admin/contents', headers=headers, data={'title': f'{marker}-{index}', 'type': 'images', 'status': 'draft', 'markdown_cache': 'Pagination browser fixture'})
            assert response.ok, response.text()
            contents.append(response.json()['content']['id'])
        for index in range(23):
            response = context.request.post(base + '/admin/resources/tags', headers=headers, data={'name': f'{marker}-{index}', 'slug': f'{marker.lower()}-{index}', 'color': '#13a88a'})
            assert response.ok, response.text()
            tags.append(response.json()['data']['id'])

        page.goto(base + f'/admin/contents?q={marker}&status=draft&type=images&per_page=10&page=3', wait_until='networkidle')
        expect(rows()).to_have_count(1)
        expect(page.locator('.admin-pagination-total')).to_have_text('共 21 条')
        rows().first.get_by_role('button', name='删除', exact=True).click()
        page.locator('.el-message-box').get_by_role('button', name='删除', exact=True).click()
        expect(rows()).to_have_count(10)
        expect(page.locator('.admin-pagination-total')).to_have_text('共 20 条')
        assert 'page=2' in page.url
        rows().first.get_by_role('button', name='发布', exact=True).click()
        page.locator('.el-message-box').get_by_role('button', name='发布', exact=True).click()
        expect(page.locator('.admin-pagination-total')).to_have_text('共 19 条')
        expect(rows()).to_have_count(9)
        settle()
        before = len(requests)
        choose_size(50)
        expect(rows()).to_have_count(19)
        assert len(requests) == before + 1, requests[before:]
        assert 'page=1' in page.url and 'per_page=50' in page.url
        assert page.locator('.zfy-admin-tab').count() == 1
        choose_size(10)
        page.locator('.admin-table-region .el-scrollbar__wrap').evaluate('n => n.scrollTop = 300')
        page.locator('.admin-pagination .btn-next').click()
        expect(rows()).to_have_count(9)
        assert page.locator('.admin-table-region .el-scrollbar__wrap').evaluate('n => n.scrollTop') == 0
        page.locator('.admin-pagination .el-pagination__jump input').fill('1')
        page.locator('.admin-pagination .el-pagination__jump input').press('Enter')
        expect(rows()).to_have_count(10)
        page.get_by_role('textbox', name='搜索标题').fill(marker + '-no-match')
        page.get_by_role('button', name='搜索', exact=True).click()
        expect(page.locator('.admin-pagination-total')).to_have_text('共 0 条')
        page.go_back(wait_until='networkidle')
        expect(page.get_by_role('textbox', name='搜索标题')).to_have_value(marker)
        expect(rows()).to_have_count(10)

        page.goto(base + '/admin/tags', wait_until='networkidle')
        page.get_by_role('textbox', name='搜索记录').fill(marker)
        page.get_by_role('button', name='搜索', exact=True).click()
        expect(page.locator('.admin-pagination-total')).to_have_text('共 23 条')
        choose_size(10)
        expect(rows()).to_have_count(10)
        page.locator('.admin-pagination .btn-next').click()
        settle()
        page.locator('.admin-pagination .btn-next').click()
        expect(rows()).to_have_count(3)
        choose_size(50)
        expect(rows()).to_have_count(23)
        page.get_by_role('button', name='刷新', exact=True).click()
        expect(rows()).to_have_count(23)

        page.set_viewport_size({'width': 390, 'height': 844})
        page.goto(base + f'/admin/contents?q={marker}&per_page=10', wait_until='networkidle')
        first = rows().first
        assert page.locator('.admin-table-region .el-scrollbar__wrap').evaluate('n => n.scrollWidth <= n.clientWidth + 1')
        first.get_by_role('button', name='内容操作', exact=True).click()
        page.locator('.el-dropdown-menu:visible').get_by_role('menuitem', name='设置', exact=True).click()
        dialog = page.locator('.admin-record-dialog:visible')
        expect(dialog).to_be_visible()
        page.wait_for_timeout(400)
        assert dialog.bounding_box()['height'] <= 824
        footer = dialog.locator('.el-dialog__footer').bounding_box()
        assert footer['y'] + footer['height'] < 844
        page.screenshot(path=str(output / 'mobile-quick-settings.png'))
        dialog.get_by_role('button', name='取消', exact=True).click()
        page.locator('.admin-pagination .btn-next').click()
        settle()
        expect(page.locator('.admin-pagination-position')).to_have_text('2 / 2')
        page.screenshot(path=str(output / 'mobile-pagination.png'))
        for width, height in [(1366, 768), (390, 844)]:
            page.set_viewport_size({'width': width, 'height': height})
            for path, action in [('/admin/settings-general', '保存设置'), ('/admin/page-builder', '保存布局'), ('/admin/theme-settings', '保存配置'), ('/admin/editor', '发布文章')]:
                page.goto(base + path, wait_until='networkidle')
                button = page.get_by_role('button', name=action, exact=True)
                box = button.bounding_box()
                assert box and 0 <= box['y'] and box['y'] + box['height'] <= height, (path, box)
            page.goto(base + '/admin/updater', wait_until='networkidle')
            for tab in ['数据库与备份', '扩展版本', '更新记录', '钩子诊断', '版本更新']:
                page.get_by_role('tab', name=tab, exact=True).click()
                settle()
                assert not page.evaluate('document.documentElement.scrollHeight > innerHeight + 2')
                if tab == '更新记录':
                    box = page.locator('.admin-pagination:visible').bounding_box()
                    assert box and box['y'] + box['height'] <= height, box
        assert not errors, errors
        print('PASS: content/resource pagination, page sizes, jump, filters, back navigation, filtered publish, last-page deletion, mobile actions and dialog footer')
    finally:
        for content in contents:
            response = context.request.delete(base + f'/admin/contents/{content}', headers=headers)
            assert response.ok or response.status == 404
        for tag in tags:
            assert context.request.delete(base + f'/admin/resources/tags/{tag}', headers=headers).ok
        browser.close()
