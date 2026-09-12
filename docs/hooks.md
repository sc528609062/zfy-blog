# 钩子与过滤器契约

`zfy_on($name, $callback, $priority = 10, $acceptedArgs = null)` 与 `zfy_filter(...)` 返回监听 ID。优先级数字越小越早，同优先级按注册顺序；`null` 传递全部参数，数字只截取前 N 个。动作返回值忽略；过滤器必须返回新值，包括有意返回 `null` 的情况。

`zfy_once`、`zfy_filter_once` 在执行前移除自己，递归触发也只执行一次。`zfy_off`、`zfy_remove_filter` 接受原 callable 或监听 ID，可限定优先级。触发期间新注册监听从下一次触发生效，移除立即生效。`zfy_current_hook`、`zfy_current_filter` 返回当前各自栈顶；`zfy_did` 返回本进程触发次数。HookBus/FilterBus 的 `diagnostics()` 提供监听、计数、调用栈、最近 100 次失败，后台维护页可查看。

```php
$id = zfy_filter('zfy_editor_toolbar', function (array $toolbar, $user): array {
    return $toolbar;
}, 20, 2);
zfy_remove_filter('zfy_editor_toolbar', $id);

zfy_on('zfy_content_saving', function (array $attributes, $content, $user): void {
    if (isset($attributes['title']) && trim($attributes['title']) === '') {
        throw \Illuminate\Validation\ValidationException::withMessages(['title' => 'Title required']);
    }
}, 10, 3);
```

## 校验和通知

`zfy_validate` 与 `zfy_apply_strict` 抛出异常会阻止业务写入；校验回调不可自行提交事务。`zfy_after_commit` 只在事务成功后通知，回滚不通知；`zfy_emit` 和普通过滤器失败记录日志并继续，普通过滤器失败保留进入该监听前的值。通知不能当作保存前验证。

资金关键通知写入 `extension_events` 与业务事务一起提交，由 `zfy:events-deliver` 重试投递。它们参数统一为 **`array $payload, string $eventKey`**，不是 Eloquent Model。投递是至少一次语义：

```php
zfy_on('zfy_order_paid', function (array $payload, string $eventKey): void {
    zfy_consume_event('sample.order-paid', $eventKey, function () use ($payload): void {
        // 在此事务中写本扩展数据；外部服务另用其幂等键或自己的 outbox。
    });
}, 10, 2);
```

`zfy_consume_event` 将消费凭证与回调数据库写入放同一事务，已消费返回 false。外部邮件或 HTTP 请求不能随数据库回滚，需独立幂等处理。持久事件监听失败会重试整个事件，已成功监听也可能再次收到，因此各消费者必须使用不同 consumer key。

## 真实扩展点

| 名称 | 参数 / 阶段 |
| --- | --- |
| `zfy_content_payload` | attributes、请求 payload、Request、Content/null；严格过滤 |
| `zfy_content_saving` | attributes、Content/null、User/null；严格校验 |
| `zfy_content_saved/created/updated/published` | Content、attributes、User/null；提交后 |
| `zfy_content_deleting/deleted` | Content、User；校验 / 提交后 |
| `zfy_content_review_submitted` | Content、User；提交后 |
| `zfy_content_review_rejected` | Content、revision、User；提交后 |
| `zfy_user_saving/saved` | User、去除 password/token 的 dirty/changes；校验 / 提交后 |
| `zfy_user_registered/verified` | User；注册提交后 / 验证完成 |
| `zfy_comment_saving` | data、Content、User；校验 |
| `zfy_comment_created` | Comment、Content；提交后 |
| `zfy_message_sending` | sender、recipient、body；严格校验 |
| `zfy_message_sent` | PrivateMessage；提交后，可能为 pending，接收者通知仅在审核通过后发送 |
| `zfy_media_uploading` | UploadedFile、User；校验 |
| `zfy_media_uploaded` | Media、User；公开上传完成 |
| `zfy_media_created` | Media；私有附件提交后 |
| `zfy_media_deleting/deleted` | Media、User；校验 / 删除后 |
| `zfy_order_created` | Order；提交后 |
| `zfy_order_cancelling/cancelled` | Order；校验 / 提交后 |
| `zfy_payment_creating` | Order、gateway；校验 |
| `zfy_payment_created` | Payment；提交后 |
| `zfy_order_paid/payment_paid/payment_late/vip_activated/refund_completed` | payload、eventKey；持久事件，以各事件表记录的 payload 为准 |
| `zfy_refund_completing` | refund、Order；严格校验 |
| `zfy_return_submitted/received` | refund、Order；提交后 |
| `zfy_shipment_saving` | shipment、data；校验 |
| `zfy_shipment_shipped/received` | shipment、Order；提交后 |
| `zfy_download_authorizing/created` | Content、Media、User；最终下载校验 / 提交后 |
| `zfy_settings_saving/saved` | validated settings；校验 / 提交后 |
| `zfy_theme_settings_saving/saved` | Theme、data；校验 / 提交后 |
| `zfy_plugin_activated/deactivated/uninstalled/data_deleted` | Plugin；生命周期通知 |
| `zfy_theme_activated/deactivated/uninstalled/data_deleted` | Theme；生命周期通知 |
| `zfy_editor_toolbar` | toolbar、User；过滤 |
| `zfy_markdown_before_render` | Markdown、context；过滤 |
| `zfy_rendered_html` | HTML、Markdown、context；可信扩展过滤，须保护权限与转义 |
| `zfy_page_data` | data、page、theme；严格过滤 |
| `zfy_template` | view、page、theme；模板过滤 |
| `zfy_system_installed` | site、去除密码后的 admin；安装完成 |

旧名 `content.created/updated/published/deleted`、`order.paid`、`vip.activated`、`download.created`、`comment.created`、`user.registered` 映射到对应 `zfy_` 名称，注册和触发使用同一个队列。不要同时注册新旧名称以免重复执行。新扩展使用表中的标准名称。
