# Gitee 项目配置说明

## 已配置文件

- `.gitee/ISSUE_TEMPLATE.zh-CN.md`：中文 Issue 模板。
- `.gitee/PULL_REQUEST_TEMPLATE.zh-CN.md`：中文 Pull Request 模板。
- `.workflow/zfy-blog-ci.yml`：Gitee Go 流水线配置。
- `.gitignore`：Laravel / Node / 本地环境 / 缓存 / 临时文件忽略规则。
- `.gitattributes`：归档导出忽略规则与常见文件 diff 类型。

## Gitee Go 注意事项

`zfy-blog` 要求 PHP 8.3。当前流水线使用 `shell@agent`，需要你在 Gitee Go 中准备一台已安装 PHP 8.3、Composer、Node、npm 的自有主机，并把 `.workflow/zfy-blog-ci.yml` 里的：

```yaml
hostGroupID: zfy-blog-php83
```

改成你 Gitee Go 主机组的真实 ID。

不建议直接使用旧版云端 `build@php` 模板，因为它可能不提供 PHP 8.3，容易和 Laravel 12 的运行要求冲突。

## 首次推送前建议

```powershell
pwsh -c "git status --short"
pwsh -c "php artisan test"
pwsh -c "npm run build"
```

确认没有 `.env`、`vendor/`、`node_modules/`、数据库文件、缓存文件、真实支付密钥进入提交。

## 当前远程仓库

```text
https://gitee.com/c528609062/zfy-blog.git
```
