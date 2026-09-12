# zfy-blog 部署说明

## 宝塔单机

1. 最低 PHP 8.2、MySQL 5.7；推荐 PHP 8.4、MySQL 8.4、Redis 7。源码构建需要 Composer 2、Node 22，正式发布包已经包含生产依赖及前端资源。
2. PHP 启用 `bcmath/ctype/curl/dom/fileinfo/filter/gd/json/mbstring/openssl/pdo_mysql/phar/session/sodium/tokenizer/xml/xmlwriter`，测试启用 `pdo_sqlite`，Linux 推荐 OPcache、pcntl。
3. 上传代码到站点目录，运行 `php composer.phar install --no-dev --optimize-autoloader`。
4. 复制 `.env.example` 为 `.env`，配置 MySQL、Redis、站点域名、支付密钥。
5. 新站生成 `APP_KEY` 后访问 `/install`，填写空数据库和独立管理员。已有站点不得覆盖 `.env`、不得重新生成密钥，仅运行 `php artisan migrate --force` 增量迁移。
6. 源码部署执行 `npm ci`、`npm run build`，站点运行目录指向 `public/`。
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

支付宝、微信官方走 Yansongda SDK，支持二维码下单、验签、查单、关单和退款查单；商品订单支持按金额部分退款。易支付、虎皮椒支持下单、查单与通知校验，没有统一关单或退款协议时使用人工凭证处理。缺少凭据时支付选项不可用。真实配置项见 `.env.example` 和 `config/payments.php`，密钥只保存在服务器环境。

## 服务与恢复

生产使用 `APP_ENV=production`、`APP_DEBUG=false`、HTTPS `APP_URL`。设备会话管理需要 `SESSION_DRIVER=database`。邮件使用 Laravel `MAIL_*`，`log/array` 驱动不代表实际送达。Local、S3、OSS、COS 使用原生 Flysystem 适配，配置见 `config/filesystems.php`。私有下载经过应用授权后流式传输，不应映射成公开静态目录。

调度任务负责定时发布、订单过期、事件投递、支付退款对账、收益结算、每日版本检查和管理员已确认的更新。未点击安装的版本不会自动更新。更新需要 CLI PHP、`proc_open`、`mysqldump` 与 `mysql`；后两者可通过 `MYSQLDUMP_PATH`、`MYSQL_PATH` 配置。Web 与 CLI 必须使用同一站点配置。

新 PHP 代码生效需要 `php artisan queue:restart`；关闭 OPcache 时间戳校验时还需重载 PHP-FPM。主题插件类不能在同一个长驻 PHP 进程中重新定义，本项目未声明 Octane 热切换支持。

正式新站安装包为 `installation.zip`，更新包为 `package.zip`，两者不能混用。安装与更新均应校验签名，受控恢复步骤见 [updates.md](updates.md)。已有站点迁移前执行 `php artisan zfy:backup`；完整备份还应包含 `.env`、上传文件和第三方扩展。不得在生产运行 `migrate:fresh` 或测试脚本。

本机实际验证 PHP 8.2 + MySQL 5.7；PHP 8.4 的 CI 配置不等于已经在本机验证该环境。外部送达、资金和云存储结果见 [验收矩阵](implementation-status.md)。
