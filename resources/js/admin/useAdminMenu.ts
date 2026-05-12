import {
    Brush,
    ChatDotRound,
    Connection,
    Document,
    Files,
    Link,
    Monitor,
    Picture,
    Setting,
    ShoppingCart,
    Tools,
    User,
} from '@element-plus/icons-vue';

export interface AdminMenuItem {
    key: string;
    label: string;
    description: string;
}

export interface AdminMenuGroup {
    label: string;
    icon: unknown;
    items: AdminMenuItem[];
}

export const adminMenus: AdminMenuGroup[] = [
    {
        label: '仪表盘',
        icon: Monitor,
        items: [
            { key: 'dashboard', label: '首页', description: '仪表盘默认首页' },
            { key: 'updater', label: '更新', description: '显示系统核心、插件、主题更新' },
        ],
    },
    {
        label: '文章',
        icon: Document,
        items: [
            { key: 'contents', label: '所有文章', description: '管理网站文章' },
            { key: 'editor', label: '写文章', description: '新增文章' },
            { key: 'categories', label: '分类目录', description: '管理文章分类' },
            { key: 'tags', label: '标签', description: '管理文章标签' },
            { key: 'topics', label: '专题', description: '管理文章专题' },
        ],
    },
    {
        label: '媒体',
        icon: Picture,
        items: [
            { key: 'media', label: '媒体库', description: '管理上传的图片、视频等文件' },
            { key: 'media-upload', label: '添加媒体文件', description: '上传新文件' },
        ],
    },
    {
        label: '链接',
        icon: Link,
        items: [
            { key: 'links', label: '全部链接', description: '管理友情链接' },
            { key: 'links-create', label: '添加链接', description: '新增链接' },
            { key: 'link-categories', label: '链接分类', description: '管理链接的分类' },
        ],
    },
    {
        label: '页面',
        icon: Files,
        items: [
            { key: 'pages', label: '所有页面', description: '管理网站页面（如关于、联系等）' },
            { key: 'pages-create', label: '添加页面', description: '新增页面' },
        ],
    },
    {
        label: '评论',
        icon: ChatDotRound,
        items: [
            { key: 'comments', label: '评论', description: '管理文章评论' },
        ],
    },
    {
        label: '外观',
        icon: Brush,
        items: [
            { key: 'themes', label: '主题', description: '切换和管理主题' },
            { key: 'patterns', label: '样板', description: '管理网站样板（块主题）' },
            { key: 'customize', label: '自定义', description: '访问主题定制器' },
            { key: 'widgets', label: '小工具', description: '管理侧边栏等小工具区域' },
            { key: 'menus', label: '菜单', description: '管理导航菜单' },
            { key: 'theme-editor', label: '主题文件编辑器', description: '直接编辑主题代码' },
        ],
    },
    {
        label: '主题设置',
        icon: Setting,
        items: [
            { key: 'theme-settings-general', label: '全局 & 功能', description: '主题全局配置' },
            { key: 'theme-settings-display', label: '页面 & 显示', description: '页面布局样式' },
            { key: 'page-builder', label: '页面构建器', description: '管理页面构建布局' },
        ],
    },
    {
        label: '插件',
        icon: Connection,
        items: [
            { key: 'plugins', label: '已安装的插件', description: '管理当前激活的插件' },
            { key: 'installer', label: '安装插件', description: '安装新插件' },
            { key: 'plugin-editor', label: '插件文件编辑器', description: '直接编辑插件代码' },
        ],
    },
    {
        label: '插件设置',
        icon: Tools,
        items: [
            { key: 'plugin-settings', label: '功能设置', description: '插件自定义配置入口' },
        ],
    },
    {
        label: '商城中心',
        icon: ShoppingCart,
        items: [
            { key: 'orders', label: '订单明细', description: '查看订单列表' },
            { key: 'shipments', label: '发货与物流', description: '处理实物商品发货' },
            { key: 'refunds', label: '售后管理', description: '处理退款/退货' },
            { key: 'commissions', label: '分成明细', description: '查看分销/分成收益' },
            { key: 'products', label: '商品明细', description: '查看商品列表' },
            { key: 'cards', label: '卡密管理', description: '管理虚拟卡密商品' },
            { key: 'coupons', label: '优惠码管理', description: '创建和管理优惠券/码' },
            { key: 'withdrawals', label: '提现记录', description: '查看用户提现申请' },
            { key: 'vip-settings', label: '会员管理', description: '维护会员权益和用户页面' },
        ],
    },
    {
        label: '用户',
        icon: User,
        items: [
            { key: 'users', label: '所有用户', description: '管理注册用户' },
            { key: 'users-create', label: '添加用户', description: '创建新用户' },
            { key: 'profiles', label: '个人资料', description: '编辑当前用户资料' },
            { key: 'invite-codes', label: '邀请码管理', description: '管理用户注册邀请码' },
            { key: 'verification', label: '身份认证', description: '处理用户认证申请（如实名）' },
            { key: 'ban-appeals', label: '举报 & 禁封申诉', description: '处理举报及封禁申诉' },
        ],
    },
    {
        label: '设置',
        icon: Setting,
        items: [
            { key: 'settings-general', label: '常规', description: '站点标题、时区等' },
            { key: 'settings-writing', label: '撰写', description: '文章发布相关设置' },
            { key: 'settings-reading', label: '阅读', description: '首页显示、RSS等' },
            { key: 'settings-discussion', label: '讨论', description: '评论、头像等设置' },
            { key: 'settings-media', label: '媒体', description: '图片尺寸等' },
            { key: 'settings-permalinks', label: '固定链接', description: 'URL结构设置' },
        ],
    },
];

export function findAdminMenuItem(section: string): AdminMenuItem | undefined {
    return adminMenus.flatMap((group) => group.items).find((item) => item.key === section);
}
