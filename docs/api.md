# API v1

前缀 `/api/v1`，成功响应保持 `{"code":0,"message":"ok","data":...}`。验证失败 422、未认证 401、无权限 403、资源不存在 404、限流 429；Laravel 验证响应带字段 errors。分页接口的 `data` 包含分页结构。请求发送 `Accept: application/json`，写 JSON 带 `Content-Type: application/json`。

## 认证

`POST /auth/token` 提交 login 或 email、password、device_name，可选 abilities 与 expires_in。默认有效期一天，可设置 300 至 2592000 秒。返回 Bearer access_token，只显示一次。`GET /auth/tokens` 查看设备令牌，`DELETE /auth/tokens/{id}` 撤销自己的令牌。封禁账号不能签发新令牌。

| 能力 | 使用范围 |
| --- | --- |
| read | 查询内容及自己的业务数据 |
| orders | 创建订单、支付、取消、退款、购物车、地址、充值、提现、积分兑换 |
| download | 申请下载凭证及 GET 文件流 |
| comment | 评论、点赞收藏、关注 |
| profile | 资料、会话撤销、验证邮件、通知已读、私信发送/已读、签到、申请与密码解锁 |

Token 能力不替代账号权限、归属与内容授权。后台使用同域 Session/CSRF，扩展接口同时声明 Laravel permission 与 ability。已封禁用户持有原 profile Token 时仅可提交 appeal；没有可用 Token 时走网页申诉流程。

## 业务入口

| 路径 | 方法与用途 |
| --- | --- |
| `/home /contents /contents/{slug}` | GET 首页、内容分页、按当前访问者渲染的详情 |
| `/categories /tags /taxonomy /authors /search` | GET 公开检索 |
| `/products /vip` | GET 商品与会员方案 |
| `/me /me/sessions` | GET 自己资料及设备会话 |
| `/me` | PUT 资料，改密码需 current_password |
| `/me/sessions/{id}` | DELETE 自己的其他会话 |
| `/me/verification-email` | POST 验证邮件 |
| `/contents/{slug}/unlock` | POST password，解锁凭证与用户及密码版本绑定一小时 |
| `/contents/{slug}/orders /vip/{slug}/orders` | POST 内容/VIP 订单 |
| `/orders /orders/{order_no}` | GET 自己的订单与快照 |
| `/orders/{order_no}/pay/balance /pay/points` | POST 余额或积分支付；balance 可传 points 进行积分抵扣 |
| `/orders/{order_no}/payment /query` | POST 外部支付和主动查单 |
| `/orders/{order_no}/cancel /refund /receive` | POST 取消、申请退款、确认收货；refund 可传 amount，商品支持部分退款 |
| `/refunds/{id}/return` | POST 自己未验收退款的退货物流；pending/processing/refunded 均可，仅已发货实物订单适用 |
| `/cart /cart/quote` | GET 购物车与报价，地址参与区域运费计算 |
| `/cart/products/{id}` | POST 规格与数量加入购物车 |
| `/cart/items/{id}` | PATCH 数量 / DELETE 移除 |
| `/cart/checkout` | POST 下单，request_key 用于幂等 |
| `/addresses /addresses/{id}` | POST 新建 / PATCH 修改 / DELETE 删除自己的地址 |
| `/wallet /points` | GET 余额积分及流水 |
| `/wallet/recharge /wallet/withdrawals` | POST 充值订单 / 提现申请 |
| `/points-store/{id}/exchange` | POST request_id 兑换，事务扣积分和库存 |
| `/contents/{slug}/downloads` | POST 可选 attachment_id，返回限时签名 download_url |
| `/downloads/{attachment}` | GET 签名 URL，继续携带 Bearer；一次性消费，不能重放 |
| `/downloads /notifications` | GET 自己的下载记录 / 通知 |
| `/notifications/{id}/read /checkin` | POST 已读 / 签到 |
| `/messages` | GET 收发私信 / POST recipient_id、body、request_id 幂等发送 |
| `/messages/{id}/read` | POST 接收者标为已读 |
| `/contents/{slug}/comments /reaction` | POST 评论 / 互动 |
| `/authors/{id}/follow /requests` | POST 关注 / 作者申请、举报、申诉 |
| `/extensions/{key}` | 注册的扩展 API，方法及权限由注册定义决定 |

具体输入字段与校验以 `routes/api.php` 及共用控制器为准。金额为十进制字符串，客户端不自行计算最终价格；下单使用服务端商品、优惠、运费和权益快照。重试保留接口要求的原 request_key 或 request_id。下载 URL 同时校验用户、签名、有效期、一次性凭证、当前权益、每日及累计限制，API 返回原文缓存/受限块源数据不属于公开契约。

详情 `data.gallery` 仅返回可见图片的元信息与受控预览 URL，私有原图路径不出现在响应中。需要原图时向 downloads 接口提交图片 attachment_id，原图开关和单图权限会在申请与消费时再次检查。

常规 API 统一限流；令牌签发、解锁与支付另有更严格限制。真实支付、邮件和对象存储需要环境配置，模拟测试成功不表示真实资金或消息已送达。
