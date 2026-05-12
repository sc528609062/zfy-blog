# zfy-blog 部署说明

## 宝塔单机

1. 安装 PHP 8.3、MySQL 8、Redis、Composer、Node 22。
2. PHP 启用 `curl/fileinfo/gd/intl/mbstring/openssl/pdo_mysql/sodium/zip`。
3. 上传代码到站点目录，运行 `php composer.phar install --no-dev --optimize-autoloader`。
4. 复制 `.env.example` 为 `.env`，配置 MySQL、Redis、站点域名、支付密钥。
5. 执行 `php artisan key:generate && php artisan migrate --seed --force`。
6. 执行 `npm install && npm run build`，站点运行目录指向 `public/`。
7. 配置队列守护：`php artisan queue:work --tries=3`。
8. 配置计划任务：每分钟执行 `php artisan schedule:run`。

默认管理员：

- 邮箱：`admin@zfy-blog.test`
- 密码：`zfy-blog-123456`

## Docker Compose

当前 `docker-compose.yml` 提供 MySQL、Redis、Meilisearch 服务。应用仍可用本机 PHP 运行：

```powershell
pwsh -c "docker compose up -d"
pwsh -c "php artisan migrate --seed"
pwsh -c "npm run build"
pwsh -c "php artisan serve"
```

## 支付配置

支付宝、微信、虎皮椒 V3、易支付均已提供适配器入口、回调路由和沙箱占位。真实密钥不要提交到仓库，应放入服务器 `.env` 或后台加密配置。
