import json
import os
import zipfile
from pathlib import Path
from playwright.sync_api import sync_playwright

root = Path(__file__).resolve().parent.parent
base = os.environ.get('ZFY_TEST_URL', 'http://127.0.0.1:8011')
if base != 'http://127.0.0.1:8011':
    raise RuntimeError('Extension acceptance requires the isolated browser fixture on port 8011')
output = root / 'storage/framework/testing/browser-extensions'
output.mkdir(parents=True, exist_ok=True)
def package(kind, slug, version):
    source = root / 'examples' / (kind + 's') / slug
    destination = output / (slug + '-' + version + '.zip')
    with zipfile.ZipFile(destination, 'w', zipfile.ZIP_DEFLATED) as archive:
        for path in source.rglob('*'):
            if path.is_file():
                relative = path.relative_to(source).as_posix()
                if relative == kind + '.json':
                    manifest = json.loads(path.read_text(encoding='utf-8'))
                    manifest['version'] = version
                    archive.writestr(slug + '/' + relative, json.dumps(manifest))
                else:
                    archive.write(path, slug + '/' + relative)
    return destination

with sync_playwright() as p:
    browser = p.chromium.launch(headless=True, executable_path=os.environ.get('CHROME_PATH', 'C:/Users/Admin/AppData/Local/Google/Chrome/Application/chrome.exe'))
    context = browser.new_context(viewport={'width': 1440, 'height': 1000})
    page = context.new_page()
    page.goto(base + '/login', wait_until='networkidle')
    page.locator('[name=login]').fill('admin@zfy-blog.test')
    page.locator('[name=password]').fill('zfy-blog-123456')
    page.get_by_role('button', name='登录', exact=True).click()
    page.wait_for_load_state('networkidle')
    payload = context.request.get(base + '/admin', headers={'Accept': 'application/json'}).json()['payload']
    headers = {'Accept': 'application/json', 'X-CSRF-TOKEN': payload['csrf'], 'X-Requested-With': 'XMLHttpRequest', 'Referer': base + '/admin'}
    def upload(kind, slug, version):
        archive = package(kind, slug, version)
        response = context.request.post(base + '/admin/' + kind + 's/install', headers=headers, multipart={'file': {'name': archive.name, 'mimeType': 'application/zip', 'buffer': archive.read_bytes()}})
        assert response.status == 201, response.text()
        return response.json()['data']['id']
    plugin_id = upload('plugin', 'editorial-tools', '1.0.0')
    theme_id = None
    enabled = False
    try:
        response = context.request.post(base + '/admin/plugins/' + str(plugin_id) + '/toggle', headers=headers)
        assert response.ok, response.text()
        enabled = True
        page.goto(base + '/admin/editorial-settings', wait_until='networkidle')
        page.locator('textarea').fill('Independent extension settings')
        page.get_by_role('button', name='保存', exact=True).click()
        page.get_by_text('已保存', exact=True).wait_for()
        page.reload(wait_until='networkidle')
        assert page.locator('textarea').input_value() == 'Independent extension settings'
        page.screenshot(path=str(output / 'declarative-form.png'), full_page=True)
        page.goto(base + '/admin/editor', wait_until='networkidle')
        page.get_by_role('button', name='扩展组件', exact=True).click()
        page.get_by_text('Editorial callout', exact=True).click()
        page.locator('.extension-block textarea').fill('Independent extension block')
        page.get_by_placeholder('请输入文章标题', exact=True).fill('Extension block acceptance')
        page.get_by_role('button', name='发布文章', exact=True).click()
        page.get_by_role('button', name='查看文章', exact=True).wait_for()
        page.get_by_role('button', name='查看文章', exact=True).click()
        page.wait_for_load_state('networkidle')
        assert 'Independent extension block' in page.locator('body').inner_text()
        context.request.post(base + '/admin/plugins/' + str(plugin_id) + '/toggle', headers=headers)
        enabled = False
        upload('plugin', 'editorial-tools', '1.0.1')
        response = context.request.post(base + '/admin/plugins/' + str(plugin_id) + '/toggle', headers=headers)
        assert response.ok, response.text()
        enabled = True
        page.goto(base + '/admin/editorial-settings', wait_until='networkidle')
        assert page.locator('textarea').input_value() == 'Independent extension settings'
        theme_id = upload('theme', 'native-journal', '1.0.0')
        upload('theme', 'native-journal', '1.0.1')
        response = context.request.post(base + '/admin/themes/' + str(theme_id) + '/preview', headers=headers, data={})
        assert response.ok, response.text()
        page.goto(response.json()['url'], wait_until='networkidle')
        page.screenshot(path=str(output / 'native-theme.png'), full_page=True)
        assert page.locator('main article').count() > 0
        print(json.dumps({'independent_package_install': True, 'declarative_form_save': True, 'extension_block_publish': True, 'plugin_upgrade_preserves_settings': True, 'theme_upgrade_preview': True}))
    finally:
        if enabled:
            context.request.post(base + '/admin/plugins/' + str(plugin_id) + '/toggle', headers=headers)
        context.request.delete(base + '/admin/plugins/' + str(plugin_id), headers=headers)
        if theme_id:
            context.request.delete(base + '/admin/themes/' + str(theme_id) + '/installation', headers=headers)
        browser.close()
