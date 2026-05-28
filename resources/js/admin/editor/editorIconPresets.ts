import { zfyIconSet, type ZfyIconName } from '../icons/zfyIconSet';

export type EditorIconKind = 'svg' | 'font-awesome' | 'token';

export interface EditorIconItem {
    key: string;
    label: string;
    value: string;
    kind: EditorIconKind;
    keywords: string[];
    svg?: string;
    glyph?: string;
}

export interface EditorIconGroup {
    key: string;
    label: string;
    items: EditorIconItem[];
}

interface ZfyIconPreset {
    name: ZfyIconName;
    label: string;
    keywords?: string[];
}

interface FontAwesomePreset {
    name: string;
    label: string;
    glyph: string;
    keywords?: string[];
}

interface TokenIconPreset {
    value: string;
    label: string;
    glyph: string;
    keywords?: string[];
}

function encodeBase64Url(value: string): string {
    const bytes = new TextEncoder().encode(value);
    let binary = '';

    bytes.forEach((byte) => {
        binary += String.fromCharCode(byte);
    });

    return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

export function encodeSvgIcon(svg: string): string {
    return `svg:${encodeBase64Url(svg)}`;
}

export function decodeSvgIcon(value: string): string {
    if (!value.startsWith('svg:')) {
        return '';
    }

    try {
        const payload = value.slice(4);
        const padded = payload.replace(/-/g, '+').replace(/_/g, '/') + '='.repeat((4 - payload.length % 4) % 4);
        const binary = atob(padded);
        const bytes = Uint8Array.from(binary, (char) => char.charCodeAt(0));

        return new TextDecoder().decode(bytes);
    } catch {
        return '';
    }
}

export function isSvgIconValue(value: string): boolean {
    return value.startsWith('svg:');
}

function svgFromZfyIcon(name: ZfyIconName): string {
    const definition = zfyIconSet[name];
    const paths = definition.paths.map((path) => `<path d="${path}"></path>`).join('');

    return `<svg viewBox="${definition.viewBox}" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">${paths}</svg>`;
}

function zfyIcon(preset: ZfyIconPreset): EditorIconItem {
    const svg = svgFromZfyIcon(preset.name);

    return {
        key: `zfy-${preset.name}`,
        label: preset.label,
        value: encodeSvgIcon(svg),
        kind: 'svg',
        keywords: [preset.name, preset.label, ...(preset.keywords || [])],
        svg,
    };
}

function faIcon(preset: FontAwesomePreset): EditorIconItem {
    const value = `fa fa-${preset.name}`;

    return {
        key: `fa-${preset.name}`,
        label: preset.label,
        value,
        kind: 'font-awesome',
        keywords: [preset.name, preset.label, value, ...(preset.keywords || [])],
        glyph: preset.glyph,
    };
}

function tokenIcon(preset: TokenIconPreset): EditorIconItem {
    return {
        key: `token-${preset.value}`,
        label: preset.label,
        value: preset.value,
        kind: 'token',
        keywords: [preset.value, preset.label, ...(preset.keywords || [])],
        glyph: preset.glyph,
    };
}

const alertTokenIcons: TokenIconPreset[] = [
    { value: 'info', label: '信息', glyph: 'i', keywords: ['提示', 'notice'] },
    { value: 'check', label: '成功', glyph: '✓', keywords: ['success', 'ok'] },
    { value: 'warning', label: '警告', glyph: '!', keywords: ['warn'] },
    { value: 'error', label: '错误', glyph: '×', keywords: ['danger'] },
    { value: 'bell', label: '铃铛', glyph: '🔔' },
    { value: 'lamp', label: '灯泡', glyph: '💡', keywords: ['light'] },
    { value: 'fire', label: '火焰', glyph: '🔥', keywords: ['hot'] },
    { value: 'star', label: '星标', glyph: '★' },
    { value: 'none', label: '不显示', glyph: '—', keywords: ['hide', 'empty'] },
];

const zfyCommonIcons: ZfyIconPreset[] = [
    { name: 'preview', label: '查看', keywords: ['view', 'eye'] },
    { name: 'message', label: '评论', keywords: ['comment'] },
    { name: 'time', label: '时间', keywords: ['clock'] },
    { name: 'link', label: '链接' },
    { name: 'download', label: '下载' },
    { name: 'cloud', label: '网盘' },
    { name: 'copy', label: '复制' },
    { name: 'alert', label: '提示' },
    { name: 'lamp', label: '灵感' },
    { name: 'hide', label: '隐藏' },
    { name: 'symbols', label: '星标' },
    { name: 'emoji', label: '表情' },
];

const zfyContentIcons: ZfyIconPreset[] = [
    { name: 'heading', label: '标题' },
    { name: 'quote', label: '引用' },
    { name: 'image', label: '图片' },
    { name: 'table', label: '表格' },
    { name: 'card', label: '卡片' },
    { name: 'cards', label: '卡片组' },
    { name: 'describe', label: '文档' },
    { name: 'grid', label: '宫格' },
    { name: 'timeline', label: '时间线' },
    { name: 'tabs', label: '标签页' },
    { name: 'collapse', label: '折叠' },
    { name: 'component', label: '组件' },
];

const zfyMediaIcons: ZfyIconPreset[] = [
    { name: 'video', label: '视频' },
    { name: 'play', label: '播放' },
    { name: 'music', label: '音乐' },
    { name: 'musicList', label: '歌单' },
    { name: 'audio', label: '音频' },
    { name: 'button', label: '按钮' },
    { name: 'magicButton', label: '多彩按钮' },
    { name: 'noteButton', label: '便条' },
    { name: 'publish', label: '发布' },
    { name: 'save', label: '保存' },
    { name: 'fullscreen', label: '全屏' },
    { name: 'clean', label: '清空' },
];

const faCommonIcons: FontAwesomePreset[] = [
    { name: 'search', label: '搜索', glyph: '⌕' },
    { name: 'info-circle', label: '信息', glyph: 'i' },
    { name: 'check-circle', label: '成功', glyph: '✓' },
    { name: 'exclamation-triangle', label: '警告', glyph: '!' },
    { name: 'times-circle', label: '错误', glyph: '×' },
    { name: 'question-circle', label: '疑问', glyph: '?' },
    { name: 'heart', label: '爱心', glyph: '♥' },
    { name: 'heart-o', label: '空心爱心', glyph: '♡' },
    { name: 'star', label: '星标', glyph: '★' },
    { name: 'star-o', label: '空心星标', glyph: '☆' },
    { name: 'user', label: '用户', glyph: '👤' },
    { name: 'home', label: '首页', glyph: '⌂' },
    { name: 'check', label: '确认', glyph: '✓' },
    { name: 'times', label: '关闭', glyph: '×' },
    { name: 'plus', label: '加号', glyph: '+' },
    { name: 'minus', label: '减号', glyph: '-' },
    { name: 'eye', label: '查看', glyph: '◉' },
    { name: 'comment', label: '评论', glyph: '💬' },
    { name: 'fire', label: '火热', glyph: '🔥' },
    { name: 'gift', label: '礼物', glyph: '🎁' },
    { name: 'shopping-cart', label: '购物车', glyph: '🛒' },
    { name: 'download', label: '下载', glyph: '↓' },
    { name: 'upload', label: '上传', glyph: '↑' },
    { name: 'tag', label: '标签', glyph: '🏷' },
    { name: 'clock-o', label: '时钟', glyph: '◷' },
    { name: 'lock', label: '锁定', glyph: '🔒' },
    { name: 'bell', label: '铃铛', glyph: '🔔' },
    { name: 'handshake-o', label: '握手', glyph: '🤝' },
];

const faContentIcons: FontAwesomePreset[] = [
    { name: 'book', label: '书籍', glyph: '📘' },
    { name: 'bookmark', label: '书签', glyph: '🔖' },
    { name: 'file-text-o', label: '文档', glyph: '▤' },
    { name: 'folder', label: '文件夹', glyph: '▣' },
    { name: 'image-o', label: '图片', glyph: '▧' },
    { name: 'picture-o', label: '图片', glyph: '▧' },
    { name: 'camera', label: '相机', glyph: '◉' },
    { name: 'music', label: '音乐', glyph: '♪' },
    { name: 'play', label: '播放', glyph: '▶' },
    { name: 'pause', label: '暂停', glyph: 'Ⅱ' },
    { name: 'code', label: '代码', glyph: '</>' },
    { name: 'calendar', label: '日历', glyph: '□' },
    { name: 'map-marker', label: '位置', glyph: '⌖' },
    { name: 'link', label: '链接', glyph: '↗' },
    { name: 'paperclip', label: '附件', glyph: '⌘' },
    { name: 'copy', label: '复制', glyph: '⧉' },
];

const faCommerceIcons: FontAwesomePreset[] = [
    { name: 'money', label: '金额', glyph: '¥' },
    { name: 'credit-card', label: '银行卡', glyph: '▰' },
    { name: 'diamond', label: '钻石', glyph: '◆' },
    { name: 'trophy', label: '奖杯', glyph: '🏆' },
    { name: 'truck', label: '运输', glyph: '▱' },
    { name: 'archive', label: '归档', glyph: '▥' },
    { name: 'database', label: '数据库', glyph: '◫' },
    { name: 'cloud', label: '云端', glyph: '☁' },
    { name: 'shield', label: '安全', glyph: '⬟' },
    { name: 'key', label: '钥匙', glyph: '⚿' },
    { name: 'cog', label: '设置', glyph: '⚙' },
    { name: 'wrench', label: '工具', glyph: '⌘' },
];

const faSocialIcons: FontAwesomePreset[] = [
    { name: 'qq', label: 'QQ', glyph: 'QQ' },
    { name: 'weixin', label: '微信', glyph: '微' },
    { name: 'weibo', label: '微博', glyph: 'W' },
    { name: 'github', label: 'GitHub', glyph: 'GH' },
    { name: 'wordpress', label: 'WordPress', glyph: 'W' },
    { name: 'google', label: 'Google', glyph: 'G' },
    { name: 'twitter', label: 'Twitter', glyph: 'X' },
    { name: 'facebook', label: 'Facebook', glyph: 'f' },
    { name: 'instagram', label: 'Instagram', glyph: '◎' },
    { name: 'youtube-play', label: 'YouTube', glyph: '▶' },
    { name: 'telegram', label: 'Telegram', glyph: '✈' },
    { name: 'reddit', label: 'Reddit', glyph: 'R' },
];

export const editorIconGroups: EditorIconGroup[] = [
    { key: 'alert-token', label: '提示图标', items: alertTokenIcons.map(tokenIcon) },
    { key: 'zfy-common', label: '主题内置', items: zfyCommonIcons.map(zfyIcon) },
    { key: 'zfy-content', label: '内容组件', items: zfyContentIcons.map(zfyIcon) },
    { key: 'zfy-media', label: '媒体按钮', items: zfyMediaIcons.map(zfyIcon) },
    { key: 'fa-common', label: 'Font Awesome 常用', items: faCommonIcons.map(faIcon) },
    { key: 'fa-content', label: 'Font Awesome 内容', items: faContentIcons.map(faIcon) },
    { key: 'fa-commerce', label: 'Font Awesome 商城', items: faCommerceIcons.map(faIcon) },
    { key: 'fa-social', label: 'Font Awesome 社交', items: faSocialIcons.map(faIcon) },
];
