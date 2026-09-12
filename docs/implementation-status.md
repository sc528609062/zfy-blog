# 功能验收矩阵

核对日期：2026-09-13。范围来自《开发计划书》、本次实施方案、当前菜单及路由。以下“通过”指对应本地检查通过；不等同于生产上线或所有原计划细项完成。测试源码在 `tests/Feature/`，浏览器脚本在 `scripts/`。

## 后台入口

52 个后台入口均完成桌面 1440px、手机 390px 检查，共 104 次 HTTP/DOM 检查：状态 200、无横向溢出、无失效图片、无前端异常或错误消息。此项验证页面加载，不替代下面的业务测试。

| 入口（均在 /admin 下） | 实际能力 | 业务验证 |
| --- | --- | --- |
| 首页、updater | 真实统计、最新内容/订单、备份、迁移、重建、签名更新 | PageCoverage、SiteOperations、UpdateWorker |
| contents、editor、pages、pages-create | 内容/独立页面 CRUD、三模式、预览、自动保存、修订、审核、定时发布 | Publishing、ContentReview、DocumentAndPackage、browser-workflows |
| categories、tags、topics | 分类层级、标签、专题关联与前台聚合 | CmsWorkflow、SiteOperations |
| media、media-upload | 上传、筛选、分页、删除；编辑器另有私有附件和图集管理 | CmsWorkflow、Gallery、PointsAndDownload、browser-authoring |
| comments | 评论回复、审核、敏感词、公开计数 | Moderation、CommunityWorkflow |
| links、links-create、link-categories、link-submissions、link-redirects、link-checks | 友链 CRUD、审核、跳转策略、可用性检查 | CmsWorkflow、SiteOperations |
| themes、menus、widgets、page-builder | 原生主题安装/切换/配置预览、四层菜单、小工具、嵌套响应式布局 | Navigation、ThemeLifecycle、PageBuilderExtension、browser-extensions/authoring |
| plugins、installer、plugin-settings | 包安装、依赖启停、设置、卸载与独立清理、更新源 | DocumentAndPackage、ExtensionRecovery、ExtensionUpdate、browser-extensions |
| products、cards、coupons | 数字/卡密/实物商品、规格、库存、优惠券 | ProductCommerce、CartCheckout、RefundInventory |
| orders、shipments、refunds | 订单、发货/收货、退货物流/验收、全额与部分金额退款 | Shipment、MixedPayment、RefundInventory、browser-commerce |
| commissions、settlement-rules、withdrawals | 作者分成规则、冻结期、结算、提现审核、打款凭证、冲回 | Settlement、MySqlConcurrency |
| shipping-templates | 合并运费、首件/续件、包邮、区域覆盖/拒配 | CartCheckout |
| vip-settings、points-store、points-exchanges | 会员周期/权益、积分商品和兑换发放 | VipEntitlement、PointsAndDownload |
| users、users-create、profiles、roles | 用户资料、封禁、内置角色权限、管理员资料 | AuthWorkflow、RoleManagement、TokenAbilities |
| invite-codes、verification、ban-appeals、private-messages | 邀请注册、作者/认证申请、举报申诉、私信审核 | CommunityWorkflow、PrivateMessage |
| settings-general、settings-writing、settings-reading、settings-discussion、settings-media、settings-permalinks | 注册设置校验/应用、分页、提交规则、媒体来源、固定链接 | SettingsRegistry、SiteOperations、SeoAndSearch |

## 业务与开发接口

| 方案要求 | 已实现及本地验证 | 边界 / 外部状态 |
| --- | --- | --- |
| 旧实现与数据保留 | 作者用户名缺失详情页恢复；充值/查单/分页测试；增量迁移；幂等 CmsDemoSeeder | 原站数据与余额保留，未迁移 WordPress 数据 |
| 三模式内容 | TipTap/ProseMirror、CodeMirror、version=3 文档、服务端 HTML、复杂节点原稿保留、修订恢复 | 复杂文档转 Markdown 使用 fenced JSON，非所有节点转为可读 Markdown |
| 块功能 | 基础格式/缩进/符号/表格、媒体、权限块、扩展组件；标签栏/时间轴/折叠组/卡片列表子项可视化增删排序 | 子项正文使用 Markdown；旧短代码文档保留 |
| 审核与权限 | 作者复审保留公开版本、定时发布、密码/评论/VIP/购买权限、API 不输出原始受限文档 | 正文按访问者重新渲染，不共用带权限的 HTML 缓存 |
| 图集与下载 | 多图上传/拖拽排序/元信息/版权/单图权限/灯箱；私有原图、预览去元数据、原图开关、单次短期凭证与日志 | GD JPEG 预览最长边 1600px；API 图集 URL 需携带认证才能读取受限图片 |
| 用户运营 | 验证、重置、资料、设备会话、作者申请、通知、签到、私信、举报、申诉、封禁 | 邮件/通知队列模拟通过；真实 SMTP 未联调 |
| 商品闭环 | 多规格、购物车、地址、运费、优惠券、库存预留/释放、卡密交付、发货收货、退货退款 | 部分退款按金额，未提供逐 SKU/数量退货界面；累计全退才撤权/恢复可退库存 |
| 余额/积分/VIP/收益 | 多周期/永久 VIP、续费升级、充值、积分奖励/兑换/全积分和积分+余额支付、精确金额、分成/冻结/提现流水 | 积分+外部支付组合尚未实现；内部混合支付退款按原支付比例返还 |
| 支付与退款 | 官方支付宝/微信扫码、验签、查单、关单、金额退款与查询；易支付/虎皮椒协议；重复/晚回调补偿 | 官方 SDK 协议模拟通过；无真实商户资金联调；人工渠道须填写凭证 |
| 搜索与 SEO | 公开标题/摘要 LIKE、MySQL FULLTEXT、sitemap/SEO/固定链接、重建入口 | 未接通外部 Scout/Meilisearch 查询引擎；不索引受限正文 |
| 云存储 | Local、S3、OSS、COS Flysystem 适配与私有流式授权 | 离线协议/签名 URL 测试通过；真实云端上传下载未联调 |
| API v1 | 保留 code/message/data、Token 能力/有效期、限流、所有权与共用服务 | Laravel 验证失败使用标准 errors；接口字段见 api.md |
| 钩子/过滤器 | 参数数量、优先级/顺序、移除/一次性、栈/次数/诊断、旧名映射、严格校验和提交后通知 | 关键事件持久 outbox、消费凭证；外部副作用需消费者自己的幂等策略 |
| 扩展包 | JSON Schema、SemVer、依赖排序、入口/Provider/权限/设置/资源/更新声明、生命周期、迁移、错误记录 | 可信 PHP/Blade，不宣称沙箱；长驻进程升级后需重启 |
| 主题独立开发 | 包内 Blade/资源、父子模板层级、菜单/区域/配置预览；第三方包无需核心配置和 Seeder 修改 | 独立示例 theme/plugin 安装及升级浏览器通过 |
| 第三方注册接口 | 内容类型、短代码、块/页面组件、小工具、后台页、设置、API、支付驱动 | 声明式表单/表格与预构建 module；无独立扩展交易市场 |
| 后台 Art 风格 | 固定 Art Design Pro 提交参考布局、Element Plus、Router/Pinia、页签、明暗、移动导航、表格表单 | 保留 MIT；采用现有业务集成，并非完整移植上游所有页面 |
| 核心/扩展更新 | GitHub/Gitee 源、稳定版检查、管理员确认、签名/哈希、保护数据、拒绝本地改动、离线包 | 核心默认 Gitee；未 push、未创建远程标签、未发布 Release |
| 更新恢复 | 独立 worker、持久阶段、写屏障、文件/DB 备份与恢复、健康检查、安全模式、CLI 恢复 | 自动化含 SQLite 中断与隔离 MySQL DDL 恢复；未做远程正式核心更新浏览器安装 |
| 打包与 CI | 共用脚本、生产依赖/构建资源、签名清单、安装包、GitHub/Gitee 模板 | 本地签名演练通过；Gitee 主机组/制品归档需在远程绑定；PHP8.4/MySQL8.4 流水线未实际运行 |

## 验证记录

- PHP 8.2：125 tests、1030 assertions，6 项专用 MySQL 测试在 SQLite 跳过。
- 隔离 MySQL 5.7（13307/zfy_isolated_test）：33 tests、191 assertions，通过库存竞争、支付/退款/提现幂等、混合支付、分成与文件/数据库恢复。未对站点库运行 fresh 或测试清表。
- TypeScript 类型检查、Pint 格式检查、Vite 生产构建通过；构建有较大 chunk 提示。
- browser-admin：52 页面 × 2 视口，104 项通过。browser-smoke：真实本地站点首页、频道、商品、VIP、详情、登录 × 2 视口通过。
- browser-workflows：块发布、三内置主题预览、余额购买、授权下载、退款撤权通过。
- browser-commerce：购物车→地址→余额支付→发货→收货→退货物流→验收→退款，私信发送/接收通过。发货步骤采用认证浏览器 HTTP 请求，其余主要步骤使用界面操作。
- browser-authoring：缩进发布、增强块子项编辑/排序/发布/前台切换、图片上传/元信息/灯箱/原图下载、嵌套布局保存重载，桌面/手机截图通过。
- browser-extensions：在独立示例源码打包后安装/升级插件与主题，设置保留、声明式表单、组件渲染通过。
- test-release：本地生成临时签名密钥，核心包/安装包验证及示例主题插件打包签名演练。临时私钥在 finally 删除，制品仅在忽略的 storage 测试目录。
- 真实 CmsDemoSeeder 复跑验证现有 users/wallets/points/orders/payments/contents/products/variants/comments/menus 行不变，不覆盖原站余额、管理员或已编辑内容。

浏览器截图、报告、数据库备份和本地打包制品保存在忽略的 `storage/` 中，不进入 Git。

## 尚未完成的验收

本次没有将整个“超越 WordPress”产品目标标为全部完成。仍需补齐或验收：逐 SKU/数量的部分退货工作流、外部渠道与积分组合支付、增强块子项正文的富文本编辑、外部搜索引擎，以及真实商户/SMTP/云存储与远程签名更新联调。第三方登录、微信 JSAPI/通知按方案保留扩展接口。

上述待办应在独立测试环境验收后发布。当前本地访问：`http://127.0.0.1:8010`，后台 `/admin`。本机 PHP 8.2 CLI 位于 `F:/BtSoft/php/82/php.exe`，Windows PowerShell 兼容执行仓库命令。
