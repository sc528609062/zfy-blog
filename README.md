# zfy-blog

zfy-blog 是一个 Laravel 12 + MySQL + Redis 的综合 CMS / 内容商业化平台，内置文章、图集、资源、独立页面、多作者、VIP、付费内容、积分、钱包、支付、主题、插件和页面构建器。

## 使用教程

安装、宝塔、1Panel 和 Docker Compose 请查看：[使用教程.md](./使用教程.md)。当前行为与验收边界以以下文档为准：

- [站长使用指南](docs/operator-guide.md)、[部署要求](docs/deployment.md)
- [主题插件开发手册](docs/extensions.md)、[Manifest 规范](docs/manifests.md)、[钩子目录](docs/hooks.md)
- [发布与更新恢复](docs/updates.md)、[API 说明](docs/api.md)
- [功能验收矩阵与限制](docs/implementation-status.md)

## 当前实现

- Laravel 12 项目骨架、Sanctum API、Livewire、Spatie 权限、Scout、Redis 客户端。
- 统一 `contents` 内容模型，支持 `post / images / files / page`。
- 商业化表结构：订单、支付、钱包、积分、VIP、下载日志、作者收益、提现。
- 支付适配器：支付宝官方、微信官方、虎皮椒 V3、易支付。缺少真实商户配置时渠道不可用，协议模拟不代表资金联调通过。
- 三套内置主题：蓝白游戏资源社区风、蓝白资源商城交易风、黄粉创意资源站风。
- 后台主题切换、主题配置、页面构建器、插件管理、内容/订单/用户管理界面。
- `/api/v1/*` 公开 API，支持 Sanctum 保护用户接口。
- 原生主题插件支持版本化清单、SemVer 依赖、Provider、生命周期、迁移、签名更新、安全模式和独立示例包；不兼容运行 WordPress / Zibll 扩展。
- 后台使用 Vue Router、Pinia、Element Plus，参考 Art Design Pro 固定提交 `f3aaf58eec1a0e988f162352c33862327a484f95`，保留 [MIT 声明](docs/licenses/art-design-pro.txt)。

最低 PHP 8.2、MySQL 5.7；推荐 PHP 8.4、MySQL 8.4、Redis 7。源码构建使用 Composer 2、Node 22；正式安装包包含生产依赖与前端资源。

## 本地启动

```powershell
pwsh -c "composer install"
pwsh -c "Copy-Item .env.example .env"
pwsh -c "php artisan key:generate"
pwsh -c "php artisan migrate --seed"
pwsh -c "npm ci"
pwsh -c "npm run build"
pwsh -c "php artisan serve"
```

如果本机没有 MySQL，可临时把 `.env` 改为：

```env
DB_CONNECTION=sqlite
QUEUE_CONNECTION=database
CACHE_STORE=database
REDIS_CLIENT=predis
```

并确保存在 `database/database.sqlite`。

以上 Seeder 方式适合本地开发。生产新站访问 `/install` 创建自己的管理员，向导要求空数据库。已有站点仅运行增量迁移，禁止执行 `migrate:fresh`。演示数据独立执行 `php artisan db:seed --class=CmsDemoSeeder`，保留已有编辑，不充值、不重置管理员、不伪造已支付订单。

## 默认后台

- 地址：`/admin`
- 邮箱：`admin@zfy-blog.test`
- 密码：`zfy-blog-123456`

## 主题切换

访问 `/admin/themes`，可在三套主题之间切换，并为每套主题保存独立配置。主题 UI 参考 `ui-mockups/gpt-image-2/complete-system/*`。

## 验证

```powershell
pwsh -c "php artisan test"
pwsh -c "vendor/bin/pint --test app bootstrap config database routes tests scripts"
pwsh -c "npm run typecheck"
pwsh -c "npm run build"
```

隔离 MySQL 测试只允许 `127.0.0.1:13307/zfy_isolated_test`，执行 `php scripts/test-mysql.php`。浏览器脚本使用独立 SQLite fixture，不得指向站点数据库。
