# zfy-blog Copilot Instructions

请遵循根目录 `AGENTS.md` 中的项目上下文和编码约定。它是本仓库 AI 助手的唯一详细说明来源。

关键规则：

- 回复和注释优先使用简体中文。
- Shell 命令示例统一写成 `pwsh -c "..."`。
- 项目是 Laravel 12 + Vue 3 + Element Plus 的 CMS / 内容商业化平台。
- 后端复杂业务放入 `app/Services`，前端后台入口在 `resources/js/admin/*`。
- 扩展主题和插件时优先使用 `themes/*/theme.json`、`plugins/*/plugin.json` 和 `app\Support\Zfy` 内核能力。
