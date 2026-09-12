import json
import os
import time
from pathlib import Path
from playwright.sync_api import sync_playwright

base = os.environ.get('ZFY_TEST_URL', 'http://127.0.0.1:8011')
assert base.endswith(':8011'), 'Dedicated browser fixture required'
root = Path(__file__).resolve().parent.parent
output = root / 'storage/framework/testing/browser-authoring'
output.mkdir(parents=True, exist_ok=True)
with sync_playwright() as p:
    browser = p.chromium.launch(headless=True, executable_path=os.environ.get('CHROME_PATH', 'C:/Users/Admin/AppData/Local/Google/Chrome/Application/chrome.exe'))
    context = browser.new_context(viewport={'width': 1440, 'height': 1000}, accept_downloads=True)
    page = context.new_page()
    errors = []
    page.on('pageerror', lambda error: errors.append(str(error)))
    page.goto(base + '/login', wait_until='networkidle')
    page.locator('[name=login]').fill('admin@zfy-blog.test')
    page.locator('[name=password]').fill('zfy-blog-123456')
    page.get_by_role('button', name='登录', exact=True).click()
    page.wait_for_load_state('networkidle')
    page.goto(base + '/admin/editor', wait_until='networkidle')
    title = 'Authoring acceptance ' + str(time.time_ns())
    page.get_by_placeholder('请输入文章标题', exact=True).fill(title)
    page.locator('.tiptap').fill('Indented paragraph')
    page.get_by_role('button', name='增加缩进', exact=True).click()
    assert 'margin-left' in (page.locator('.tiptap p').first.get_attribute('style') or '')
    page.locator('.tiptap').press('Control+End')
    page.locator('.tiptap').press('Enter')
    page.get_by_role('button', name='扩展组件', exact=True).hover()
    page.get_by_role('menuitem', name='标签栏', exact=True).click()
    collection = page.locator('.extension-block').last
    item = collection.locator('.extension-item').first
    item.get_by_label('标题', exact=True).fill('First tab')
    item.get_by_label('内容 (Markdown)', exact=True).fill('First tab body')
    collection.get_by_role('button', name='添加子项', exact=True).click()
    item = collection.locator('.extension-item').last
    item.get_by_label('标题', exact=True).fill('Second tab')
    item.get_by_label('内容 (Markdown)', exact=True).fill('Second tab body')
    item.get_by_role('button', name='上移子项', exact=True).click()
    assert collection.locator('.extension-item').first.get_by_label('标题', exact=True).input_value() == 'Second tab'
    page.screenshot(path=str(output / 'collection-editor-desktop.png'), full_page=True)
    page.set_viewport_size({'width': 390, 'height': 844})
    page.wait_for_function("document.querySelector('.zfy-admin-sidebar').getBoundingClientRect().right <= 0")
    page.get_by_role('button', name='打开后台菜单', exact=True).click()
    page.wait_for_function("document.querySelector('.zfy-admin-sidebar').getBoundingClientRect().left >= -1")
    page.locator('.zfy-sidebar-close').click()
    page.wait_for_function("document.querySelector('.zfy-admin-sidebar').getBoundingClientRect().right <= 0")
    page.screenshot(path=str(output / 'collection-editor-mobile.png'), full_page=True)
    assert not page.evaluate('document.documentElement.scrollWidth > innerWidth + 2')
    page.set_viewport_size({'width': 1440, 'height': 1000})
    page.get_by_role('button', name='发布文章', exact=True).click()
    page.get_by_role('button', name='查看文章', exact=True).wait_for()
    gallery = page.locator('.gallery-manager')
    gallery.locator('input[type=file]').set_input_files(str(root / 'public/theme-assets/a-avatar.png'))
    gallery.locator('.gallery-item').wait_for()
    gallery.get_by_label('图片标题', exact=True).fill('Gallery acceptance image')
    gallery.get_by_label('图片说明', exact=True).fill('Gallery caption')
    gallery.get_by_label('版权', exact=True).fill('Local acceptance fixture')
    gallery.locator('.el-checkbox').filter(has_text='允许下载原图').click()
    gallery.get_by_role('button', name='保存图集', exact=True).click()
    page.wait_for_timeout(400)
    page.get_by_role('button', name='查看文章', exact=True).click()
    page.wait_for_load_state('networkidle')
    assert page.locator('.zfy-tabs-head-item').first.inner_text() == 'Second tab'
    assert page.locator('.zfy-tabs-body-item.is-active').inner_text().strip() == 'Second tab body'
    page.locator('.zfy-tabs-head-item').last.click()
    assert page.locator('.zfy-tabs-body-item.is-active').inner_text().strip() == 'First tab body'
    assert page.locator('[data-gallery-image]').count() == 1
    assert 'Gallery caption' in page.locator('body').inner_text()
    image_status = page.locator('[data-gallery-image] img').evaluate('(image) => image.complete && image.naturalWidth > 0')
    assert image_status
    page.locator('[data-gallery-image]').click()
    assert page.locator('dialog[open]').count() == 1
    page.get_by_role('button', name='关闭', exact=True).click()
    with page.expect_download() as event:
        page.get_by_role('button', name='下载原图', exact=True).click()
    assert event.value.failure() is None
    for width, label in [(1440, 'desktop'), (390, 'mobile')]:
        page.set_viewport_size({'width': width, 'height': 1000 if width > 500 else 844})
        page.screenshot(path=str(output / ('gallery-' + label + '.png')), full_page=True)
        assert not page.evaluate('document.documentElement.scrollWidth > innerWidth + 2')
    page.set_viewport_size({'width': 1440, 'height': 1000})
    page.goto(base + '/admin/page-builder', wait_until='networkidle')
    payload = context.request.get(base + '/admin/page-builder', headers={'Accept': 'application/json'}).json()['payload']
    headers = {'Accept': 'application/json', 'X-CSRF-TOKEN': payload['csrf'], 'X-Requested-With': 'XMLHttpRequest'}
    layout = payload['layouts'][0]
    previous = {'title': layout['title'], 'status': layout['status'], 'schema': json.dumps(layout['schema']) if not isinstance(layout['schema'], str) else layout['schema']}
    try:
        page.get_by_role('button', name='添加容器', exact=True).first.click()
        container = page.locator('.layout-blocks').first.locator(':scope > .builder-block').last
        container.get_by_label('标题', exact=True).first.fill('Nested acceptance layout')
        container.get_by_role('button', name='添加区块', exact=True).click()
        page.get_by_role('button', name='保存布局', exact=True).click()
        page.wait_for_timeout(500)
        assert page.locator('.el-message--error').count() == 0
        page.reload(wait_until='networkidle')
        assert page.locator('.layout-blocks.nested').count() > 0
        for width, label in [(1440, 'desktop'), (390, 'mobile')]:
            page.set_viewport_size({'width': width, 'height': 1000 if width > 500 else 844})
            page.screenshot(path=str(output / ('layout-' + label + '.png')), full_page=True)
            assert not page.evaluate('document.documentElement.scrollWidth > innerWidth + 2')
    finally:
        response = context.request.post(base + layout['save_url'], headers=headers, data=previous)
        assert response.status == 200, response.text()
    assert not errors, errors
    report = {'indent_published': True, 'collection_edit_reorder_publish': True, 'gallery_upload_metadata_lightbox_original': True, 'nested_layout_save_reload': True, 'errors': errors}
    (output / 'results.json').write_text(json.dumps(report), encoding='utf-8')
    print(json.dumps(report))
    browser.close()
