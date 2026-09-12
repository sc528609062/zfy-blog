# zfy-blog AI 编码助手上下文

本文件是给 AI 编码助手的项目入口说明。开始任何修改前，先读取本文件，再按任务继续查看相关源码。

## 沟通与命令

- 始终使用简体中文回复。
- 在本仓库执行 Shell 命令时，统一使用 `pwsh -c "..."` 格式。
- 优先用 `rg` / `rg --files` 搜索文件和文本。
- 不要回滚用户已有改动；修改前先看 `git status --short`。
- 涉及数据库、支付、权限、安装流程时，优先补充或运行相关测试。

## 项目定位

`zfy-blog` 是 Laravel 12 + MySQL + Redis 的综合 CMS / 内容商业化平台，包含：

- 内容类型：文章 `post`、图集 `images`、资源 `files`、独立页面 `page`。
- 商业化能力：VIP、付费内容、积分、钱包、订单、支付、下载日志、作者收益、提现。
- 扩展能力：主题、插件、页面构建器、后台菜单、设置注册、钩子和过滤器。
- 前台页面：多主题 Blade 模板渲染。
- 后台页面：Blade 注入 JSON payload，Vue 3 + Element Plus 挂载后台 SPA。
- API：`/api/v1/*`，公开接口 + Sanctum 用户接口。

## 技术栈

- PHP `^8.2`
- Laravel `^12.0`
- Laravel Sanctum、Scout、Livewire
- Spatie Permission、Spatie Activitylog
- Redis 客户端：Predis
- 支付入口：支付宝官方、微信官方、虎皮椒 V3、易支付、余额、积分
- 前端：Vite 7、Vue 3.5、TypeScript、Element Plus、Tailwind CSS 4
- 测试：PHPUnit 11，Laravel `php artisan test`

## 常用命令

```powershell
pwsh -c "composer install"
pwsh -c "npm install"
pwsh -c "php artisan key:generate"
pwsh -c "php artisan migrate --seed"
pwsh -c "npm run build"
pwsh -c "php artisan serve"
pwsh -c "composer test"
pwsh -c "php artisan test"
pwsh -c "vendor/bin/pint"
```

本机没有 MySQL 时，可临时使用 SQLite：

```env
DB_CONNECTION=sqlite
QUEUE_CONNECTION=database
CACHE_STORE=database
REDIS_CLIENT=predis
```

并确保存在 `database/database.sqlite`。

## 关键路径

- `routes/web.php`：安装、前台、登录注册、支付回调、后台路由。
- `routes/api.php`：`/api/v1/*` 平台 API。
- `config/zfy.php`：平台版本、默认主题、主题配置、角色、内容类型、编辑器媒体配置、支付网关。
- `app/Providers/ZfyServiceProvider.php`：默认后台菜单、设置分组、主题能力、插件启动入口。
- `app/Support/Zfy/*`：核心扩展内核，包含钩子、过滤器、后台菜单、设置、主题能力、插件启动。
- `app/Services/ThemeManager.php`：当前主题、主题切换、主题设置默认值。
- `app/Services/PackageManifestService.php`：读取 `themes/*/theme.json` 和 `plugins/*/plugin.json`。
- `app/Services/OrderService.php`：内容订单、VIP 订单、余额支付、积分支付、内容访问权限。
- `app/Services/Payment/*`：支付网关接口和各支付适配器。
- `app/Services/ContentMarkdownRenderer.php`：Markdown、短代码和 HTML 清洗渲染。
- `app/Http/Controllers/Web/SiteController.php`：前台页面渲染与内容购买入口。
- `app/Http/Controllers/Admin/AdminController.php`：后台 shell payload、主题、插件、页面构建器入口。
- `app/Http/Controllers/Admin/AdminContentController.php`：后台编辑器保存、预览、标签同步。
- `app/Http/Controllers/Admin/AdminMediaController.php`：媒体库查询和图片上传。
- `app/Http/Controllers/Api/V1/PlatformController.php`：平台 API 控制器。
- `resources/views/admin/shell.blade.php`：后台 payload 注入点。
- `resources/js/admin/AdminApp.vue`：后台 Vue 根组件。
- `resources/js/admin/components/AdminPage.vue`：后台页面类型分发。
- `resources/js/admin/editor/*`：Markdown 编辑器、工具栏、媒体库、预览。
- `resources/views/themes/*`：三套前台主题 Blade。
- `themes/*/theme.json`：主题清单。
- `plugins/*/plugin.json`：插件清单。
- `database/migrations/*zfy*`：平台业务表结构。
- `database/seeders/CoreInstallSeeder.php`：角色权限、默认管理员、VIP、主题、站点设置、首页布局。
- `ui-mockups/gpt-image-2/complete-system/*`：前后台 UI 参考图。

## 数据与领域模型

核心内容表与模型：

- `contents` / `App\Models\Content`：统一内容模型，支持 `post/images/files/page`，关联作者、分类、标签、附件、评论。
- `categories`、`tags`、`content_tag`：分类标签体系。
- `media`、`attachments`：媒体库和内容附件。
- `comments`：评论，默认用户提交后 `pending`。

商业化表与模型：

- `orders`、`order_items`、`payments`、`payment_logs`
- `vip_levels`、`user_vips`
- `wallets`、`wallet_transactions`
- `points_accounts`、`points_transactions`、`points_store_items`、`points_exchange_orders`
- `products`、`product_variants`、`stock_items`、`card_codes`
- `shipments`、`refunds`、`coupons`
- `download_logs`

扩展与运营表：

- `themes`、`theme_settings`
- `plugins`、`plugin_settings`
- `page_layouts`
- `menus`、`menu_items`、`widgets`
- `links`、`link_categories`、`link_submissions`、`link_checks`
- `notifications`、`notification_reads`
- `system_versions`、`upgrade_logs`、`security_events`

默认角色与权限：

- 角色：`SUPER_ADMIN`、`ADMIN`、`EDITOR`、`USER`
- 权限：`manage system`、`manage contents`、`manage commerce`、`manage themes`、`manage plugins`、`manage users`、`manage links`、`publish contents`、`buy contents`

默认后台账号：

- 地址：`/admin`
- 邮箱：`admin@zfy-blog.test`
- 密码：`zfy-blog-123456`

## 扩展内核约定

可用全局 helper：

- `zfy_on($hook, $callback, $priority = 10)`：注册动作钩子。
- `zfy_emit($hook, ...$args)`：触发动作钩子。
- `zfy_filter($hook, $callback, $priority = 10)`：注册过滤器。
- `zfy_apply($hook, $value, ...$args)`：执行过滤器。
- `zfy_register_admin_page($definition)`：注册后台页面。
- `zfy_register_setting($definition)`：注册设置字段。
- `zfy_theme_support($feature, $options = [])`：声明主题能力。
- `zfy_register_nav_area($key, $definition)`：注册导航区域。
- `zfy_register_widget_area($key, $definition)`：注册小工具区域。

当前已用钩子/过滤器示例：

- `zfy_content_payload`：保存内容前过滤内容属性。
- `zfy_content_saving`、`zfy_content_saved`、`zfy_content_published`：内容保存/发布事件。
- `zfy_editor_toolbar`：过滤后台编辑器工具栏。
- `zfy_plugin_activated`、`zfy_plugin_deactivated`：插件启停事件。

## 常见开发任务

新增后台菜单或页面：

- 优先在 `ZfyServiceProvider::defaultPages()` 添加核心页面定义。
- 插件页面优先放到 `plugins/<slug>/plugin.json` 的 `admin_pages`。
- 页面数据由 `AdminController::page()` 注入，表格数据通常在 `rowsFor()` 中添加。
- Vue 端由 `resources/js/admin/components/AdminPage.vue` 按 `currentPage.kind` 分发。

新增设置项：

- 核心设置放在 `ZfyServiceProvider::registerDefaultSettings()`。
- 插件设置放在 `plugin.json` 的 `settings_schema`。
- Vue 表单渲染入口是 `resources/js/admin/components/SettingsForm.vue`。

新增 API：

- 路由加到 `routes/api.php` 的 `v1` 分组。
- 控制器优先放到 `PlatformController`，复杂业务抽到 `app/Services`。
- 响应保持 `['code' => 0, 'message' => 'ok', 'data' => ...]` 结构。
- 用户私有接口放入 `auth:sanctum` 分组。

新增前台页面：

- 公共页面路由在 `routes/web.php` 中接到 `SiteController::generic()` 或新增 action。
- 前台主题入口由 `ThemeManager::active()['view']` 决定。
- 共享 Blade 片段放在 `resources/views/themes/shared/partials`。
- 主题专属片段放在 `resources/views/themes/<theme>/partials`。

新增主题：

- 新建 `themes/<slug>/theme.json`。
- 原生主题模板放在包内 `views/`，构建资源放在 `assets/`；由清单声明入口、父主题、设置和依赖。
- 使用 `zfy:extension make-theme/validate/pack` 在独立目录开发，后台上传安装；无需修改核心配置或 Seeder。
- 内置三主题保留原模板路径，第三方开发约定见 `docs/extensions.md`、`docs/manifests.md`、`docs/hooks.md`。

新增插件：

- 新建 `plugins/<slug>/plugin.json`，至少包含 `name`、`slug`、`version`、`compatible`、`permissions`、`events`。
- 如需运行代码，提供 Laravel service provider 类，并在 `provider` 中填写完整类名。
- 插件不要覆盖 `app/`、`vendor/` 等核心路径。
- 管理页和设置优先通过 manifest 注册，避免硬编码到核心。

新增内容编辑能力：

- 后端默认工具栏在 `AdminController::defaultEditorToolbar()`。
- 插件或扩展优先用 `zfy_editor_toolbar` 过滤工具栏。
- Markdown 保存逻辑在 `AdminContentController::persistContent()`。
- 预览和渲染逻辑在 `ContentMarkdownRenderer`。
- Vue 编辑器相关代码在 `resources/js/admin/editor/*`。

新增支付能力：

- 新支付网关实现 `app/Services/Payment/PaymentGateway.php` 或继承 `AbstractGateway`。
- 在 `PaymentManager` 注册或解析新 gateway。
- 同步更新 `config/zfy.php` 的 `payment_gateways`。
- 订单履约逻辑优先放到 `OrderService::fulfillPaidOrder()`。

## 前端约定

- Vue 代码使用 Vue 3 Composition API 和 `<script setup lang="ts">`。
- 后台 UI 使用 Element Plus；按钮、表格、卡片和消息提示遵循现有组件风格。
- 后台数据从 `resources/views/admin/shell.blade.php` 的 `admin-payload` 注入，避免前端重复拼复杂业务数据。
- 前端请求后台接口时带上 `X-CSRF-TOKEN` 和 `X-Requested-With: XMLHttpRequest`。
- 管理后台入口资源是 `resources/js/admin.js`；安装页入口是 `resources/js/install.js`；前台入口是 `resources/js/app.js`。
- Vite 入口配置在 `vite.config.js`。

## 后端约定

- 使用 Laravel 常规分层：Controller 负责请求/响应，复杂业务放到 `app/Services`。
- 使用 Eloquent 关系，不要重复手写可复用查询逻辑。
- 写入请求必须做 `validate()`、权限检查和 CSRF/API 认证。
- 后台权限优先使用 Spatie Permission 的 `$user->can(...)`。
- JSON 字段沿用数组 cast，例如 `seo`、`pricing`、`access_rules`、`meta`、`settings_schema`。
- 内容 slug 生成参考 `AdminContentController::uniqueSlug()`。
- 涉及金额、积分、余额和库存时优先使用事务。

## 验证建议

改 PHP 业务逻辑后运行：

```powershell
pwsh -c "php artisan test"
```

改前端或 Blade 资源后运行：

```powershell
pwsh -c "npm run build"
```

改格式或大范围 PHP 代码后运行：

```powershell
pwsh -c "vendor/bin/pint"
```

已有聚合测试命令：

```powershell
pwsh -c "composer test"
```

## 注意事项

- `.env` 可能包含本地敏感配置，不要把值写入文档或提交。
- `vendor/`、`node_modules/`、`storage/`、`bootstrap/cache/` 不要手动改业务代码。
- 插件和主题 manifest 是扩展边界，优先通过 manifest + service provider 扩展，而不是改核心。
- 现有前台 UI 有 mockup 参考，做主题页面时先查看 `ui-mockups/gpt-image-2/complete-system/<style>/`。
- 路由受 `EnsureInstalled` 中间件保护；安装流程相关修改要检查 `/install` 和 `InstallController`。
