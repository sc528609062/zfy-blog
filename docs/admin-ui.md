# 后台界面

后台采用 Art Design Pro 的纵向布局和组件样式，参考固定提交 `f3aaf58eec1a0e988f162352c33862327a484f95`。上游地址为 https://github.com/Daymychen/art-design-pro ，MIT 声明及参考文件见 `docs/licenses/art-design-pro.txt`。

## 当前集成

- 230px 侧栏、72px 折叠侧栏、60px 顶栏、42px 菜单项；900px 以下使用抽屉导航。
- 默认主色 `#5D87FF`；浅色、深色、主题色、侧栏折叠和表格密度保存在当前浏览器。
- 顶栏提供菜单搜索、刷新、全屏、外观设置、前台入口和用户菜单；发布按钮按菜单权限显示。
- 页签支持切换、关闭、关闭其他和关闭全部；搜索结果来自 Laravel 下发的当前用户菜单。
- 仪表盘使用真实统计，统一表格、表单、弹窗、编辑器和主题插件管理的外观。

这是与现有 CMS 业务集成的 Art 后台，继续使用同仓 Vue 3、Element Plus、Pinia、Vue Router 和 Laravel 接口。没有引入上游演示账号、模拟接口或无对应业务的入口。前台主题色与后台主色相互独立。

## 维护与验证

布局位于 `resources/js/admin/AdminApp.vue`，导航、搜索、外观组件位于 `resources/js/admin/components/`，样式位于 `resources/css/admin-art.css`。更新参考版本时需要核对这些适配文件，避免直接覆盖 CMS 路由和权限数据。

运行 `npm run typecheck`、`npm run build` 验证前端；在专用浏览器测试站点运行 `python scripts/browser-art-admin.py`，覆盖菜单搜索、折叠状态保存、页签操作、外观设置、明暗模式以及 1440/820/390px 视口。全后台页面检查继续使用 `scripts/browser-admin.py`。

本次验证：TypeScript 与生产构建通过；后台入口和角色测试 3 项、259 个断言通过；52 个入口在桌面/手机共 104 项检查通过；Art 专项 36 项页面检查与搜索、刷新、全屏、折叠、页签、外观保存交互通过。构建保留现有较大 chunk 提示。
