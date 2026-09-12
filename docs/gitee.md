# Gitee 项目配置说明

## 已配置文件

- `.gitee/ISSUE_TEMPLATE.zh-CN.md`：中文 Issue 模板。
- `.gitee/PULL_REQUEST_TEMPLATE.zh-CN.md`：中文 Pull Request 模板。
- `.workflow/zfy-blog-ci.yml`：Gitee Go 流水线配置。
- `.gitignore`：Laravel / Node / 本地环境 / 缓存 / 临时文件忽略规则。
- `.gitattributes`：归档导出忽略规则与常见文件 diff 类型。

## Gitee Go 注意事项

`zfy-blog` 最低 PHP 8.2，推荐 PHP 8.4。当前流水线使用 `shell@agent`，需要在 Gitee Go 中准备已安装 PHP 8.2+、Composer 2、Node 22 的独立构建主机，并把 `.workflow/zfy-blog-ci.yml` 和发布模板中的：

```yaml
hostGroupID: zfy-blog-php82
```

改成你 Gitee Go 主机组的真实 ID。

流水线仅可运行在隔离 checkout，不得使用站点运行目录。发布模板为 `.gitee/workflows/release.yml`，手动绑定不可变版本标签，配置 `RELEASE_TAG` 和受保护的 `ZFY_RELEASE_PRIVATE_KEY`；实际主机组及制品上传由仓库管理员在 Gitee Go 中绑定。该模板尚未在远程运行，不代表已发布版本。两个平台共用 `scripts/ci-release.php`，镜像同步同一制品及签名，不重新打包。

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
