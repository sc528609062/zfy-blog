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

主题设置和页面构建器提供“恢复默认”，确认后回填当前表单，点击保存后生效。取消不会改变编辑内容。主题设置恢复当前主题表单中的字段，默认值由主题清单和字段定义提供；其他主题、文章的 Markdown 样式和站点全局设置不受影响。页面构建器恢复初始区块并关闭“启用自定义布局”，保存后使用主题原生页面，布局名称和发布状态保持当前值；重新打开开关并保存可启用构建器布局。

Markdown 正文主题、代码主题各自限定到文章容器，切换时保留短代码组件交互状态。主题 CSS 在 Vite 构建时通过 PostCSS 解析，动画名称加主题前缀；不在浏览器解析第三方 CSS。短代码、代码工具栏、公式和图表不套用正文主题。扩展作者可在组件根节点添加 `data-markdown-component` 来隔离正文排版。代码颜色只由“代码主题”控制，多个预览或文章可以同时使用不同主题；预览卸载后释放对应样式。下载卡按文章容器宽度响应，适用于分屏编辑和手机。

专项回归：`node --test tests/frontend/markdown-theme-css.test.mjs`、`AppearanceDefaultsTest`，以及隔离浏览器站点上的 `python scripts/browser-markdown-themes.py`、`python scripts/browser-appearance-defaults.py`。

2026-09-13 专项验证：32 种正文主题、9 种代码主题、多实例隔离、样式释放和组件交互状态通过；重置取消/确认/保存/刷新、前台原生首页恢复及桌面/手机检查通过。PHP 相关测试 11 项、212 个断言，CSS 测试 4 项，TypeScript 和生产构建通过。窄屏编辑器工具栏支持换行，目录提供关闭按钮并在切入窄屏时收起。

布局位于 `resources/js/admin/AdminApp.vue`，导航、搜索、外观组件位于 `resources/js/admin/components/`，样式位于 `resources/css/admin-art.css`。更新参考版本时需要核对这些适配文件，避免直接覆盖 CMS 路由和权限数据。

运行 `npm run typecheck`、`npm run build` 验证前端；在专用浏览器测试站点运行 `python scripts/browser-art-admin.py`，覆盖菜单搜索、折叠状态保存、页签操作、外观设置、明暗模式以及 1440/820/390px 视口。全后台页面检查继续使用 `scripts/browser-admin.py`。

本次验证：TypeScript 与生产构建通过；后台入口和角色测试 3 项、259 个断言通过；52 个入口在桌面/手机共 104 项检查通过；Art 专项 36 项页面检查与搜索、刷新、全屏、折叠、页签、外观保存交互通过。构建保留现有较大 chunk 提示。
