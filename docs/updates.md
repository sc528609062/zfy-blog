# 签名发布、更新与恢复

核心默认源为 Gitee `c528609062/zfy-blog`，可在后台配置 GitHub/Gitee 仓库。主题、插件独立配置来源及固定公钥。检查新版本只读取稳定 Release，管理员确认安装后调度器执行，不会自动安装未确认版本。

## 信任和发布

发布使用不可变 SemVer 标签。`scripts/ci-release.php` 检查 checkout 与标签提交一致，生产流水线安装 `--no-dev` PHP 依赖并构建前端，再调用 `scripts/build-release.php`。制品包含 `package.zip/release.json/release.sig` 及独立新站 `installation.zip`。更新站点只需 PHP CLI，无需 Git、Composer、Node。`scripts/sign-extension.php` 为独立扩展生成同类制品。

清单包含版本、提交、制品 SHA-256、逐文件 SHA-256 和兼容约束，以独立发布私钥签名。核心站点固定 `ZFY_UPDATE_PUBLIC_KEY`，扩展由系统管理员固定各发布者 PEM 公钥。私钥只放 CI 受保护变量 `ZFY_RELEASE_PRIVATE_KEY`，不能放站点、仓库或包中。GitHub/Gitee 镜像同步同一份包、清单与签名。未配置公钥或源码基线时后台拒绝覆盖，不应绕过验证。

GitHub 模板 `.github/workflows/release.yml` 仅生成 artifact，不创建 Release。Gitee 自有构建主机模板 `.gitee/workflows/release.yml` 需要绑定主机组、标签、环境变量及制品归档步骤，见 [gitee.md](gitee.md)。本次仅本地签名演练，未推送、创建远程标签或发布版本。

源码站点在提交并验证干净工作区后，可执行 `php artisan zfy:update --baseline` 记录当前文件哈希。签名正式安装包自带基线。基线仅描述本地已验证版本，不代表远程发布者签名。

## 核心更新

后台维护页检查版本并确认安装；离线包先暂存：

```powershell
pwsh -c "php artisan zfy:update --offline=完整的离线制品目录"
pwsh -c "php artisan zfy:update 返回的任务ID"
```

流程为签名和兼容检查、下载与解包验证、本地文件差异检查、持久化任务、独立执行器、请求及队列写屏障、文件与数据库备份、维护、替换、迁移、缓存重建、健康检查、恢复服务。任务记录位于 `storage/app/private/updates/<id>`，页面显示进度。`.env`、上传、缓存与运行数据、第三方 themes/plugins 均不由核心包覆盖。核心文件本地修改会停止更新并列出差异。

失败自动尝试恢复数据库和文件，仍保持维护及恢复记录。支付回调在维护期间返回可重试状态；恢复后运行主动查单补偿。不要只回退代码或直接删除锁文件。

```powershell
pwsh -c "php artisan zfy:update 任务ID --recover=resume"
pwsh -c "php artisan zfy:update 任务ID --recover=restore"
pwsh -c "php artisan zfy:update 任务ID --recover=finish"
pwsh -c "php artisan zfy:payments-reconcile"
pwsh -c "php artisan queue:restart"
```

`resume` 继续中断流程，`restore` 恢复备份，`finish` 完成健康检查后恢复服务；按任务状态选择动作。故障应用无法启动时，使用任务目录中复制的独立 worker：`php worker.php job.json restore`，恢复后再执行 `finish`。任务文件含数据库连接信息，目录必须保持私有，不得提供 HTTP 下载。

## 扩展更新

停用插件或切换主题后，在扩展页检查并确认版本。后台返回 202，由 `zfy:extensions-update` 独立安全模式进程执行。已确认任务状态包括 queued、downloading、installing、installed、failed、rolled-back。下载阶段中断超过 30 分钟后可重新校验和下载；安装阶段中断需先恢复，不重复盲写。

```powershell
pwsh -c "php artisan zfy:extensions-update 任务ID"
pwsh -c "php artisan zfy:extension safe-mode"
pwsh -c "php artisan zfy:extension recover"
pwsh -c "php artisan zfy:extension safe-mode --off"
```

扩展安装后保持停用，管理员启用执行迁移和升级生命周期。恢复使用 `storage/app/private/extensions/recovery.json`，同时恢复数据库与包文件，相关插件保持停用。生命周期失败即使自动恢复成功也保留维护状态，执行 recover 后才解除。停用保留数据，卸载保留数据，清理操作单独执行。

## 验证边界

SQLite 覆盖恶意压缩包、签名篡改、文件本地修改、核心更新中断及恢复；隔离 MySQL 5.7 覆盖 DDL 失败后的数据库与文件一体恢复。真实 GitHub/Gitee Release/CDN 下载和生产 PHP-FPM 切换尚未联调。本地打包演练使用开发依赖，仅用于验证安装与签名流程；正式 CI 在打包前去除开发依赖。站点备份应另含环境配置和上传，数据库备份不能替代全站异地备份。
