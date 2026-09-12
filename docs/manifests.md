# 扩展清单与兼容规则

原生清单使用 JSON，`schema_version` 当前为 `1`。JSON Schema 位于 `schemas/plugin.schema.json`、`schemas/theme.schema.json`；编辑器可通过 `$schema` 引用。CLI 与安装器均执行结构校验、语义校验和路径约束，依赖使用 `composer/semver` 解析，不能靠字符串大小比较版本。

```json
{
  "schema_version": 1,
  "name": "Sample Tools",
  "slug": "sample-tools",
  "version": "1.0.0",
  "compatible": "^1.0",
  "entry": "provider.php",
  "provider": "Sample\\Tools\\Provider",
  "permissions": ["manage contents"],
  "events": ["zfy_content_published"],
  "requires": {"php": "^8.2", "plugins": {}, "themes": {}},
  "update": {"provider": "github", "repository": "owner/sample-tools"}
}
```

| 字段 | 契约 |
| --- | --- |
| `slug` | 小写字母数字及连字符，最长 80，必须等于包目录名 |
| `version` | SemVer `major.minor.patch`，可有预发布或构建后缀 |
| `compatible` | 核心版本约束，如 `^1.0`、`>=1.0 <2.0` |
| `requires.php` | PHP 版本约束 |
| `requires.plugins` | slug 到版本约束的映射，依赖必须已启用 |
| `requires.themes` | 已安装主题版本约束；父主题另声明 `parent` |
| `entry` | 插件 PHP 启动文件；主题为 `views/*.blade.php` |
| `bootstrap` | 主题可选 PHP 启动文件 |
| `provider/autoload.psr-4` | PHP 类名及包内命名空间目录，禁止越过包根 |
| `permissions/events` | 扩展声明用途；不是可信 PHP 的隔离边界，事件不自动注册回调 |
| `settings_schema` | 插件为 `group => {label, fields}`；主题当前为 `global => [field definitions]`，字段声明 key、label、type 和默认值 |
| `admin_pages` | 页面数组，支持 permission、endpoint、fields、columns、module |
| `assets` | 公开构建资源元信息，模板或页面显式引用 |
| `menus/regions/defaults` | 主题菜单、小工具区域、按 scope 分组的默认配置 |
| `update` | GitHub/Gitee 来源和仓库；信任公钥由站点管理员另行固定 |

包目录只能包含自身文件。ZIP 检查路径穿越、绝对路径、Windows 特殊路径、链接、大小写重复路径、文件数及解包大小。升级必须提高版本，停用扩展后执行；正在被启用子主题使用的父主题也不能覆盖。禁用与卸载默认保留设置，清理数据是独立操作。

现有不带 `schema_version` 的内置旧清单保留兼容读取，新包必须使用版本化结构。未来破坏性 API 或文档格式变更提升主版本；增加可选字段可保持主版本，弃用接口先提供适配周期。当前旧事件名适配表见 [hooks.md](hooks.md)。公开的 WordPress 函数、数据库和主题插件格式不属于兼容范围。

签名 Release 清单与安装清单分开：Release 描述制品哈希、逐文件 SHA-256、提交、版本、类型和兼容性；站点先验证发布者签名再解包。具体流程见 [updates.md](updates.md)。
