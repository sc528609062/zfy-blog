<?php

namespace App\Services;

use App\Models\AuthorSettlementRule;
use App\Models\CardCode;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Content;
use App\Models\Coupon;
use App\Models\InviteCode;
use App\Models\Link;
use App\Models\LinkCategory;
use App\Models\LinkSubmission;
use App\Models\Menu;
use App\Models\PointsStoreItem;
use App\Models\PrivateMessage;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\ShippingTemplate;
use App\Models\Tag;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserRequest;
use App\Models\VipLevel;
use App\Models\Widget;
use App\Support\Zfy\ExtensionRegistry;
use App\Support\Zfy\ThemeRegistry;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminResourceRegistry
{
    public function definitions(): array
    {
        $name = ['key' => 'name', 'label' => '名称', 'rules' => ['required', 'string', 'max:120']];
        $slug = ['key' => 'slug', 'label' => '别名', 'rules' => ['required', 'string', 'max:160', 'regex:/^[a-zA-Z0-9][a-zA-Z0-9_-]*$/'], 'unique' => true];
        $sort = ['key' => 'sort_order', 'label' => '排序', 'type' => 'number', 'default' => 0, 'rules' => ['required', 'integer', 'min:0', 'max:99999']];
        $description = ['key' => 'description', 'label' => '描述', 'type' => 'textarea', 'rules' => ['nullable', 'string', 'max:2000']];
        $parent = ['key' => 'parent_id', 'label' => '上级分类', 'type' => 'select', 'nullable' => true, 'rules' => ['nullable', 'integer']];

        return [
            'roles' => ['model' => Role::class, 'permission' => 'manage system', 'search' => 'name', 'create' => false, 'delete' => false, 'fields' => [
                ['key' => 'name', 'label' => '角色', 'readonly' => true, 'rules' => ['exclude']],
                ['key' => 'permissions', 'label' => '权限', 'type' => 'multiselect', 'string_values' => true, 'default' => [], 'options' => [], 'rules' => ['present', 'array']],
            ]],
            'private-messages' => ['model' => PrivateMessage::class, 'permission' => 'manage users', 'search' => 'body', 'create' => false, 'delete' => false, 'fields' => [
                ['key' => 'sender_id', 'label' => '发送者 UID', 'type' => 'number', 'readonly' => true, 'rules' => ['exclude']],
                ['key' => 'recipient_id', 'label' => '接收者 UID', 'type' => 'number', 'readonly' => true, 'rules' => ['exclude']],
                ['key' => 'body', 'label' => '私信内容', 'type' => 'textarea', 'readonly' => true, 'rules' => ['exclude']],
                ['key' => 'status', 'label' => '审核状态', 'type' => 'select', 'options' => ['pending' => '待审核', 'sent' => '通过', 'rejected' => '拒绝']],
            ]],
            'shipping-templates' => ['model' => ShippingTemplate::class, 'permission' => 'manage commerce', 'search' => 'name', 'fields' => [$name,
                ['key' => 'base_fee', 'label' => '首件运费', 'type' => 'number', 'default' => 0, 'rules' => ['required', 'numeric', 'between:0,99999.99', 'decimal:0,2']],
                ['key' => 'base_quantity', 'label' => '首件数量', 'type' => 'number', 'default' => 1, 'rules' => ['required', 'integer', 'between:1,10000']],
                ['key' => 'additional_fee', 'label' => '每续件运费', 'type' => 'number', 'default' => 0, 'rules' => ['required', 'numeric', 'between:0,99999.99', 'decimal:0,2']],
                ['key' => 'free_threshold', 'label' => '满额包邮（留空不包邮）', 'type' => 'number', 'nullable' => true, 'rules' => ['nullable', 'numeric', 'between:0,999999.99', 'decimal:0,2']],
                ['key' => 'regions', 'label' => '区域运费规则', 'type' => 'array', 'default' => [], 'rules' => ['sometimes', 'array', 'max:100']],
            ]],
            'settlement-rules' => ['model' => AuthorSettlementRule::class, 'permission' => 'manage commerce', 'search' => 'name', 'fields' => [$name,
                ['key' => 'scope', 'label' => '适用范围', 'type' => 'select', 'default' => 'default', 'options' => ['default' => '全站默认', 'content' => '指定内容', 'author' => '指定作者', 'category' => '指定分类']],
                ['key' => 'target_id', 'label' => '范围对象 ID', 'type' => 'number', 'nullable' => true, 'rules' => ['nullable', 'integer', 'min:1']],
                ['key' => 'share_percent', 'label' => '作者比例（%）', 'type' => 'number', 'default' => 50, 'rules' => ['required', 'numeric', 'between:0,100', 'decimal:0,2']],
                ['key' => 'hold_days', 'label' => '结算冻结天数', 'type' => 'number', 'default' => 7, 'rules' => ['required', 'integer', 'between:0,365']],
            ]],
            'topics' => ['model' => Topic::class, 'permission' => 'manage contents', 'search' => 'name', 'fields' => [$name, $slug, $description,
                ['key' => 'status', 'label' => '状态', 'type' => 'select', 'default' => 'published', 'options' => ['published' => '发布', 'draft' => '草稿']],
                ['key' => 'content_ids', 'label' => '内容', 'type' => 'multiselect', 'default' => [], 'rules' => ['present', 'array']],
            ]],
            'invite-codes' => ['model' => InviteCode::class, 'permission' => 'manage users', 'search' => 'code', 'fields' => [
                ['key' => 'code', 'label' => '邀请码', 'unique' => true, 'rules' => ['required', 'alpha_dash', 'max:80']],
                ['key' => 'usage_limit', 'label' => '可用次数', 'type' => 'number', 'default' => 1, 'rules' => ['required', 'integer', 'min:1', 'max:1000000']],
                ['key' => 'status', 'label' => '状态', 'type' => 'select', 'default' => 'active', 'options' => ['active' => '启用', 'disabled' => '停用']],
                ['key' => 'expires_at', 'label' => '到期时间', 'type' => 'datetime', 'rules' => ['nullable', 'date']],
            ]],
            'verification' => ['model' => UserRequest::class, 'permission' => 'manage users', 'search' => 'body', 'create' => false, 'delete' => false, 'types' => ['verification', 'author'], 'fields' => [
                ['key' => 'status', 'label' => '状态', 'type' => 'select', 'options' => ['pending' => '待处理', 'approved' => '通过', 'rejected' => '拒绝']],
                ['key' => 'reply', 'label' => '处理意见', 'type' => 'textarea', 'rules' => ['nullable', 'string', 'max:2000']],
            ]],
            'ban-appeals' => ['model' => UserRequest::class, 'permission' => 'manage users', 'search' => 'body', 'create' => false, 'delete' => false, 'types' => ['report', 'appeal'], 'fields' => [
                ['key' => 'status', 'label' => '状态', 'type' => 'select', 'options' => ['pending' => '待处理', 'approved' => '已处理', 'rejected' => '不予受理']],
                ['key' => 'reply', 'label' => '处理意见', 'type' => 'textarea', 'rules' => ['nullable', 'string', 'max:2000']],
            ]],
            'cards' => ['model' => CardCode::class, 'permission' => 'manage commerce', 'search' => 'code_hash', 'fields' => [
                ['key' => 'product_id', 'label' => '商品 ID', 'type' => 'number', 'rules' => ['required', 'integer', 'exists:products,id']],
                ['key' => 'code', 'label' => '卡密', 'type' => 'password', 'rules' => ['nullable', 'string', 'max:2000']],
            ]],
            'shipments' => ['model' => Shipment::class, 'permission' => 'manage commerce', 'search' => 'tracking_no', 'create' => false, 'delete' => false, 'fields' => [
                ['key' => 'carrier', 'label' => '快递公司', 'rules' => ['required', 'string', 'max:100']],
                ['key' => 'tracking_no', 'label' => '运单号', 'rules' => ['required', 'string', 'max:150']],
                ['key' => 'status', 'label' => '状态', 'type' => 'select', 'default' => 'pending', 'options' => ['pending' => '待发货', 'shipped' => '已发货', 'received' => '已签收']],
            ]],
            'points-store' => ['model' => PointsStoreItem::class, 'permission' => 'manage commerce', 'search' => 'title', 'fields' => [
                ['key' => 'title', 'label' => '商品名称', 'rules' => ['required', 'string', 'max:180']], $slug,
                ['key' => 'points_price', 'label' => '兑换积分', 'type' => 'number', 'default' => 0, 'rules' => ['required', 'integer', 'min:0', 'max:100000000']],
                ['key' => 'stock', 'label' => '库存', 'type' => 'number', 'default' => 0, 'min' => -1, 'rules' => ['required', 'integer', 'min:-1', 'max:100000000']],
                ['key' => 'status', 'label' => '状态', 'type' => 'select', 'default' => 'active', 'options' => ['active' => '上架', 'inactive' => '下架']],
            ]],
            'products' => ['model' => Product::class, 'permission' => 'manage commerce', 'search' => 'title', 'fields' => [
                ['key' => 'title', 'label' => '商品名称', 'rules' => ['required', 'string', 'max:180']], $slug,
                ['key' => 'content_id', 'label' => '关联内容 ID', 'type' => 'number', 'nullable' => true, 'rules' => ['nullable', 'integer', 'exists:contents,id']],
                ['key' => 'type', 'label' => '类型', 'type' => 'select', 'default' => 'digital', 'options' => ['digital' => '数字商品', 'physical' => '实物', 'card' => '卡密']],
                ['key' => 'status', 'label' => '状态', 'type' => 'select', 'default' => 'draft', 'options' => ['draft' => '草稿', 'published' => '上架', 'archived' => '下架']],
                ['key' => 'price', 'label' => '售价', 'type' => 'number', 'default' => 0, 'rules' => ['required', 'numeric', 'min:0', 'max:999999.99', 'decimal:0,2']],
                ['key' => 'stock_strategy', 'label' => '库存策略', 'type' => 'select', 'default' => 'unlimited', 'options' => ['unlimited' => '不限量', 'limited' => '限量']],
                ['key' => 'stock', 'label' => '可售库存', 'type' => 'number', 'default' => 0, 'rules' => ['required', 'integer', 'min:0', 'max:100000000']],
                ['key' => 'shipping_fee', 'label' => '每单运费', 'type' => 'number', 'default' => 0, 'rules' => ['required', 'numeric', 'min:0', 'max:99999.99', 'decimal:0,2']],
                ['key' => 'shipping_template_id', 'label' => '运费模板', 'type' => 'number', 'nullable' => true, 'rules' => ['nullable', 'integer', 'exists:shipping_templates,id']],
                ['key' => 'variants', 'label' => '商品规格', 'type' => 'variants', 'default' => [], 'rules' => ['sometimes', 'array', 'max:100']],
            ]],
            'coupons' => ['model' => Coupon::class, 'permission' => 'manage commerce', 'search' => 'code', 'fields' => [
                ['key' => 'code', 'label' => '优惠码', 'unique' => true, 'rules' => ['required', 'string', 'max:80', 'alpha_dash']],
                ['key' => 'type', 'label' => '类型', 'type' => 'select', 'default' => 'fixed', 'options' => ['fixed' => '减免金额', 'percent' => '减免比例']],
                ['key' => 'amount', 'label' => '优惠值', 'type' => 'number', 'default' => 0, 'rules' => ['required', 'numeric', 'min:0', 'max:999999.99', 'decimal:0,2']],
                ['key' => 'usage_limit', 'label' => '使用次数上限', 'type' => 'number', 'nullable' => true, 'rules' => ['nullable', 'integer', 'min:1']],
                ['key' => 'status', 'label' => '状态', 'type' => 'select', 'default' => 'active', 'options' => ['active' => '启用', 'inactive' => '停用']],
                ['key' => 'starts_at', 'label' => '开始时间', 'type' => 'datetime', 'rules' => ['nullable', 'date']],
                ['key' => 'expires_at', 'label' => '结束时间', 'type' => 'datetime', 'rules' => ['nullable', 'date', 'after:starts_at']],
            ]],
            'link-submissions' => ['model' => LinkSubmission::class, 'permission' => 'manage links', 'search' => 'name', 'create' => false, 'fields' => [$name,
                ['key' => 'url', 'label' => '网址', 'rules' => ['required', 'url:http,https', 'max:500']],
                ['key' => 'status', 'label' => '审核', 'type' => 'select', 'options' => ['pending' => '待审核', 'approved' => '通过', 'rejected' => '拒绝']],
            ]],
            'categories' => ['model' => Category::class, 'permission' => 'manage contents', 'search' => 'name', 'fields' => [
                $name, $slug, $parent + ['source' => 'categories'],
                ['key' => 'type', 'label' => '内容类型', 'type' => 'select', 'default' => 'mixed', 'options' => ['mixed' => '全部', 'post' => '文章', 'images' => '图集', 'files' => '资源', 'page' => '页面']],
                $description, $sort,
            ]],
            'tags' => ['model' => Tag::class, 'permission' => 'manage contents', 'search' => 'name', 'fields' => [$name, $slug,
                ['key' => 'color', 'label' => '颜色', 'type' => 'color', 'rules' => ['nullable', 'regex:/^#[a-fA-F0-9]{6}$/']],
            ]],
            'link-categories' => ['model' => LinkCategory::class, 'permission' => 'manage links', 'search' => 'name', 'fields' => [$name, $slug, $parent + ['source' => 'link-categories'], $description, $sort]],
            'links' => ['model' => Link::class, 'permission' => 'manage links', 'search' => 'name', 'fields' => [$name,
                ['key' => 'url', 'label' => '网址', 'rules' => ['required', 'url:http,https', 'max:500']],
                ['key' => 'link_category_id', 'label' => '分类', 'type' => 'select', 'source' => 'link-categories', 'nullable' => true, 'rules' => ['nullable', 'integer']],
                ['key' => 'status', 'label' => '状态', 'type' => 'select', 'default' => 'active', 'options' => ['active' => '启用', 'inactive' => '停用']],
                ['key' => 'target', 'label' => '打开方式', 'type' => 'select', 'default' => '_blank', 'options' => ['_blank' => '新窗口', '_self' => '当前窗口']],
                ['key' => 'nofollow', 'label' => 'nofollow', 'type' => 'boolean', 'default' => true], $sort,
            ]],
            'comments' => ['model' => Comment::class, 'permission' => 'manage contents', 'search' => 'body', 'create' => false, 'fields' => [
                ['key' => 'body', 'label' => '评论内容', 'type' => 'textarea', 'rules' => ['required', 'string', 'max:2000']],
                ['key' => 'status', 'label' => '审核状态', 'type' => 'select', 'options' => ['pending' => '待审核', 'approved' => '通过', 'rejected' => '拒绝', 'spam' => '垃圾评论']],
            ]],
            'menus' => ['model' => Menu::class, 'permission' => 'manage themes', 'search' => 'name', 'fields' => [$name,
                ['key' => 'location', 'label' => '显示位置', 'type' => 'select', 'default' => 'primary', 'unique' => true, 'options' => ['primary' => '主导航', 'mobile' => '移动端', 'footer' => '页脚']],
                ['key' => 'items', 'label' => '菜单项', 'type' => 'menu', 'default' => [], 'rules' => ['present', 'array', 'max:100']],
            ]],
            'widgets' => ['model' => Widget::class, 'permission' => 'manage themes', 'search' => 'title', 'fields' => [
                ['key' => 'title', 'label' => '标题', 'rules' => ['required', 'string', 'max:120']],
                ['key' => 'region', 'label' => '显示区域', 'type' => 'select', 'default' => 'sidebar', 'options' => ['sidebar' => '侧边栏', 'footer' => '页脚', 'user-center' => '用户中心']],
                ['key' => 'type', 'label' => '类型', 'type' => 'select', 'default' => 'text', 'options' => ['text' => '文本', 'html' => 'HTML', 'recent-posts' => '最新文章']],
                ['key' => 'text', 'label' => '正文', 'type' => 'textarea', 'rules' => ['nullable', 'string', 'max:20000']], $sort,
                ['key' => 'enabled', 'label' => '启用', 'type' => 'boolean', 'default' => true],
            ]],
            'vip-settings' => ['model' => VipLevel::class, 'permission' => 'manage commerce', 'search' => 'name', 'fields' => [$name, $slug,
                ['key' => 'level', 'label' => '等级', 'type' => 'number', 'default' => 1, 'rules' => ['required', 'integer', 'min:1', 'max:100']],
                ['key' => 'price_monthly', 'label' => '月费', 'type' => 'number', 'default' => 0, 'rules' => ['required', 'numeric', 'min:0', 'max:999999.99', 'decimal:0,2']],
                ['key' => 'price_yearly', 'label' => '年费', 'type' => 'number', 'default' => 0, 'rules' => ['required', 'numeric', 'min:0', 'max:999999.99', 'decimal:0,2']],
                ['key' => 'price_lifetime', 'label' => '永久价格（留空关闭）', 'type' => 'number', 'default' => null, 'rules' => ['nullable', 'numeric', 'min:0', 'max:999999.99', 'decimal:0,2']],
                ['key' => 'discount_percent', 'label' => '支付比例 %', 'type' => 'number', 'default' => 100, 'rules' => ['required', 'integer', 'between:0,100']],
                ['key' => 'fixed_discount', 'label' => '固定优惠', 'type' => 'number', 'default' => 0, 'rules' => ['required', 'numeric', 'min:0', 'max:999999.99', 'decimal:0,2']],
                ['key' => 'benefits_text', 'label' => '会员权益', 'type' => 'textarea', 'rules' => ['nullable', 'string', 'max:2000']],
            ]],
            'users' => ['model' => User::class, 'permission' => 'manage users', 'search' => 'name', 'delete' => false, 'fields' => [$name,
                ['key' => 'username', 'label' => '用户名', 'unique' => true, 'rules' => ['required', 'string', 'max:80', 'regex:/^[a-zA-Z0-9_-]+$/']],
                ['key' => 'email', 'label' => '邮箱', 'unique' => true, 'rules' => ['required', 'email', 'max:160']],
                ['key' => 'password', 'label' => '密码', 'type' => 'password', 'rules' => ['nullable', 'string', 'min:12', 'max:128']],
                ['key' => 'role', 'label' => '角色', 'type' => 'select', 'default' => 'USER', 'options' => ['USER' => '用户', 'EDITOR' => '编辑', 'ADMIN' => '管理员', 'SUPER_ADMIN' => '超级管理员']],
                ['key' => 'is_author', 'label' => '作者身份', 'type' => 'boolean', 'default' => false],
                ['key' => 'is_banned', 'label' => '停用账号', 'type' => 'boolean', 'default' => false],
                ['key' => 'ban_reason', 'label' => '停用原因', 'type' => 'textarea', 'rules' => ['nullable', 'string', 'max:1000']],
                ['key' => 'author_status', 'label' => '作者审核', 'type' => 'select', 'default' => 'none', 'options' => ['none' => '未申请', 'pending' => '待审核', 'approved' => '通过', 'rejected' => '拒绝']],
            ]],
        ];
    }

    public function get(string $key): array
    {
        $definition = $this->definitions()[$key] ?? null;
        abort_unless($definition, 404);

        $areas = app(ThemeRegistry::class)->payload();
        $definition['fields'] = array_map(function ($field) use ($key, $areas) {
            if ($key === 'roles' && $field['key'] === 'permissions') {
                $field['options'] = Permission::where('guard_name', 'web')->pluck('name', 'name')->all();
            }
            if ($key === 'menus' && $field['key'] === 'location') {
                $field['options'] += array_column($areas['nav_areas'], 'label', 'key');
            }
            if ($key === 'widgets' && $field['key'] === 'region') {
                $field['options'] += array_column($areas['widget_areas'], 'label', 'key');
            }
            if ($key === 'widgets' && $field['key'] === 'type') {
                foreach (app(ExtensionRegistry::class)->all('widget') as $type => $widget) {
                    $field['options'][$type] = $widget['label'] ?? $type;
                }
            }

            return $field;
        }, $definition['fields']);

        return $definition;
    }

    public function schema(string $key): array
    {
        $definition = $this->get($key);
        $definition['fields'] = array_map(function ($field) {
            if ($field['key'] === 'content_ids') {
                $field['options'] = Content::orderBy('title')->pluck('title', 'id')->all();
            }
            if (isset($field['source'])) {
                $model = $this->get($field['source'])['model'];
                $field['options'] = $model::orderBy('name')->pluck('name', 'id')->all();
            }
            unset($field['rules'], $field['source'], $field['unique']);

            return $field;
        }, $definition['fields']);
        unset($definition['model'], $definition['permission']);

        return $definition;
    }
}
