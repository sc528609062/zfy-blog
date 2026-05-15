# zfy-blog

zfy-blog 是一个 Laravel 12 + MySQL + Redis 的综合 CMS / 内容商业化平台，内置文章、图集、资源、独立页面、多作者、VIP、付费内容、积分、钱包、支付、主题、插件和页面构建器。

## 使用教程

完整安装、宝塔、1Panel、Docker Compose、更新维护和常见问题请查看：[使用教程.md](./使用教程.md)。

## 当前实现

- Laravel 12 项目骨架、Sanctum API、Livewire、Spatie 权限、Scout、Redis 客户端。
- 统一 `contents` 内容模型，支持 `post / images / files / page`。
- 商业化表结构：订单、支付、钱包、积分、VIP、下载日志、作者收益、提现。
- 支付适配器入口：支付宝官方、微信官方、虎皮椒 V3、易支付，默认沙箱占位。
- 三套内置主题：蓝白游戏资源社区风、蓝白资源商城交易风、黄粉创意资源站风。
- 后台主题切换、主题配置、页面构建器、插件管理、内容/订单/用户管理界面。
- `/api/v1/*` 公开 API，支持 Sanctum 保护用户接口。

## 本地启动

```powershell
pwsh -c "Copy-Item .env.example .env"
pwsh -c "php artisan key:generate"
pwsh -c "php artisan migrate --seed"
pwsh -c "npm install"
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

## 默认后台

- 地址：`/admin`
- 邮箱：`admin@zfy-blog.test`
- 密码：`zfy-blog-123456`

## 主题切换

访问 `/admin/themes`，可在三套主题之间切换，并为每套主题保存独立配置。主题 UI 参考 `ui-mockups/gpt-image-2/complete-system/*`。
