# 原生扩展开发

主题与插件是管理员安装的可信 PHP、Blade 和 JavaScript 代码，具有应用进程权限，不提供沙箱。仅参考 WordPress 的扩展习惯，不运行 WordPress/Zibll 扩展，不迁移其数据。

## 独立开发和安装

在站点外的工作目录开发，安装包只包含自身目录。最低 PHP 8.2，清单规则见 [manifests.md](manifests.md)。

```powershell
pwsh -c "php artisan zfy:extension make-plugin sample-tools --directory=../sample-tools"
pwsh -c "php artisan zfy:extension validate sample-tools --directory=../sample-tools"
pwsh -c "php artisan zfy:extension pack sample-tools --directory=../sample-tools --output=../sample-tools.zip"
pwsh -c "php artisan zfy:extension make-theme sample-theme --directory=../sample-theme"
pwsh -c "php artisan zfy:extension validate sample-theme --type=theme --directory=../sample-theme"
```

后台插件或主题页上传 ZIP。插件安装后保持停用；启用时检查兼容性、依赖、启动预检及迁移。主题安装后切换启用，无需改 `config/zfy.php` 或 Seeder。提高 `version` 后打包、停用并上传实现升级；启用执行升级生命周期。已修改的扩展文件会阻止覆盖，应将本地修改合入独立源码后重新发包。

可运行示例：`examples/plugins/editorial-tools` 包含独立 Provider、声明式后台表单、API、短代码和组件；`examples/themes/native-journal` 包含自身模板、CSS、Provider 与设置。`scripts/browser-extensions.py` 在隔离站点安装并升级这些示例，`scripts/test-release.php` 校验、打包及签名。示例不会自动安装到生产站点。

## 生命周期和加载

插件 `entry` 指向 PHP 启动文件，`provider` 是 Laravel ServiceProvider 类名，也可声明 `autoload.psr-4`。主题 `entry` 是 Blade 模板，PHP 入口另用 `bootstrap`。实现 `App\Support\Zfy\PluginLifecycle` 或 `ThemeLifecycle` 可提供 `activate(): void`、`deactivate(): void`、`upgrade(string $fromVersion): void`、`uninstall(bool $deleteData): void`。

Provider 的 `register()` 注册服务，`boot()` 注册扩展接口；应可重复启动，不在启动阶段扣款或发送邮件。迁移放 `database/migrations`，文件名加扩展前缀避免冲突。迁移和升级函数必须可恢复，不在数据库事务中执行外部资金操作。

插件按依赖拓扑顺序加载；主题父级先于子级加载。切换主题按父到子激活、子到父停用，共用父级不重复停用。启用失败记录错误；迁移维护区发生失败时恢复数据库和文件并保留维护状态。禁用保留数据；卸载归档代码并调用 `uninstall(false)`，独立清理操作才调用 `uninstall(true)`。

```powershell
pwsh -c "php artisan zfy:extension safe-mode"
pwsh -c "php artisan zfy:extension disable sample-tools"
pwsh -c "php artisan zfy:extension recover"
pwsh -c "php artisan zfy:extension safe-mode --off"
pwsh -c "php artisan queue:restart"
```

安全模式跳过扩展 PHP，第三方主题回退内置主题；CLI 停用不执行故障插件代码。恢复步骤见 [updates.md](updates.md)。长驻进程需重启，不能在同一 PHP 进程重新定义已加载类；不承诺 Octane 热切换。

## 注册接口

| 接口 | 定义与要求 |
| --- | --- |
| `zfy_register_content_type($key, $definition)` | 注册内容类型，键使用扩展前缀，提供 `label` |
| `zfy_register_shortcode($key, $renderer)` | 渲染器接收属性、正文及上下文，返回 HTML；输出仍应转义用户数据 |
| `zfy_register_block($key, $definition)` | `label/fields/defaults/rules/render`；服务端渲染器接收字段数组和上下文 |
| `zfy_register_widget($key, $definition)` | 小工具定义与渲染器；配合主题小工具区域 |
| `zfy_register_admin_page($definition)` | 必须声明权限，后台导航显示不替代接口权限验证 |
| `zfy_register_setting($definition)` | 核心 SettingsRegistry 字段；插件优先 manifest `settings_schema` |
| `zfy_register_api($key, $definition)` | `methods/handler/public/permission/ability`，公开 API 仅允许 GET |
| `zfy_register_payment($key, $driver)` | 驱动实现 `PaymentGateway`，按能力实现退款、关单及验签接口 |
| `zfy_theme_support / zfy_register_nav_area / zfy_register_widget_area` | 主题能力、菜单区域、小工具区域声明 |
| `zfy_asset($type, $slug, $path)` | 包内公开资源 URL，仅交付可公开访问的构建资源 |
| `zfy_template($slug, $candidates)` | 按候选顺序查找，子主题模板优先于父模板 |

组件字段支持 text、textarea、number、boolean、select、color、repeater。repeater 使用 `fields` 定义子项字段，`defaults` 定义新子项，`max` 限制数量；保存值为对象数组，支持增删与排序。服务端 `rules` 必须验证数组上限、允许键及 `items.*.field`，不能仅依赖表单。内置标签栏、时间轴、折叠组和卡片列表使用此接口，旧短代码正文继续支持。权限块使用平台 `ContentVisibility`，不要读取并直接输出原始 `block_json` 或 `markdown_cache`。缓存不得混用不同访问者权限。三模式转换保留无法无损转换的 source/extension 节点，`block_json` 文档使用 `version=3`、`editor=zfy-document`，包含 `mode`、ProseMirror `document` 和保留的 `original_markdown`。

```php
zfy_register_block('sample-notice', [
    'label' => 'Notice',
    'fields' => [['key' => 'text', 'label' => 'Text', 'type' => 'textarea']],
    'defaults' => ['text' => ''],
    'rules' => ['text' => ['required', 'string', 'max:1000']],
    'render' => fn (array $values, array $context) => '<aside>'.e($values['text']).'</aside>',
]);
```

## 后台页面

声明式页面 `kind=extension`，通过 `endpoint` 加载 `code/message/data`；`fields` 存在时渲染表单并使用 `save_method` 保存，否则以 `columns` 渲染表格。复杂表格分页和自定义交互可随包交付预构建 ES module。`module` 必须是同域 `/extensions/assets/plugin/<slug>/...`，导出 `mount(element, {csrf, request, definition})`，可返回清理函数；第三方构建产物不要求站点安装 Node。

私有 API handler 应使用共享业务服务，并声明 Laravel permission 和 Token ability。用户提交的任何 ID 都必须核对所有权。外部副作用通过队列处理；持久事件的幂等消费示例见 [hooks.md](hooks.md)。

支持部分退款的驱动应额外实现 `AmountRefundQueryGateway::queryRefundAmount(Payment, string $refundNo, string $amount)`，核对渠道确认金额与本笔退款金额。旧 `RefundableGateway` 继续支持全额退款。请求结果不明时保留处理中状态；仅渠道明确确认退款不存在才用原退款号重试。

## 主题模板

`theme.json` 声明 `entry: views/layout.blade.php`。视图命名空间为 `theme-<slug>::`，候选可按 `content-<type>`、`single`、`layout` 传给 `zfy_template`。子主题声明 `parent` 和 `requires.themes`；同路径覆盖父模板，资源通过具体包 slug 寻址，不自动复制父资源。

模板获得站点页面 payload；可复用 `themes.shared.partials.native-content`、`seo` 与用户中心片段。主题设置来自 `$theme['settings']`，模板必须实际使用自己声明的字段。配置预览使用绑定管理员、有效期 600 秒的缓存标识，期间可重复访问，不改变当前公开主题。共享商业流程和权限判断由平台服务负责。

### 分组主题表单

`theme.json` 的 `settings_schema.global` 使用字段对象数组，后台自动渲染到独立主题设置页。支持 `text/textarea/color/boolean/number/select/image/url`；`group` 可为 `brand/layout/home/cards/article/footer/extension`，省略或未知分组归入“主题扩展”。`depends_on` 指向同主题布尔字段，控制表单显示，不删除已保存值。

```json
{
  "settings_schema": {
    "global": [
      { "key": "notice_enabled", "label": "显示公告", "type": "boolean", "group": "home", "default": false },
      { "key": "notice_image", "label": "公告图片", "type": "image", "group": "home", "default": "", "depends_on": "notice_enabled" },
      { "key": "notice_url", "label": "公告链接", "type": "url", "group": "home", "default": "", "depends_on": "notice_enabled" }
    ]
  }
}
```

`default` 提供重置默认值，清单 `defaults.global` 中的同名值优先；`false`、`0` 和空字符串保留原义。数字字段可声明 `min/max/step`，并同时通过 `rules` 声明服务端范围，例如 `["required", "integer", "between:1,20"]`。选择字段通过 `options` 的键验证输入。图片与 URL 仅接受单斜线开头的站内路径或 HTTP/HTTPS 地址，不允许脚本、协议相对地址和控制字符；Blade 输出仍必须转义。空的文本、图片和 URL 在保存时归一为字符串。

值位于 `$theme['settings']['global']`。扩展必须在模板中落实开关与字段，不能只注册表单；内置主题外观 CSS 不注入第三方主题。保存继续调用 `zfy_theme_settings_saving` 校验和提交后的 `zfy_theme_settings_saved` 事件。
