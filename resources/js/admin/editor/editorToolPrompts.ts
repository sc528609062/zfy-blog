import { todayText } from './editorTools';
import type { EditorPromptSpec, EditorTool } from './types';

type PromptValues = Record<string, string>;

const toneOptions = [
    { label: '信息', value: 'info' },
    { label: '成功', value: 'success' },
    { label: '警告', value: 'warning' },
    { label: '错误', value: 'error' },
];

const alertColorOptions = [
    { label: '蓝色', value: 'blue' },
    { label: '青色', value: 'cyan' },
    { label: '绿色', value: 'green' },
    { label: '黄色', value: 'yellow' },
    { label: '红色', value: 'red' },
    { label: '紫色', value: 'purple' },
    { label: '灰色', value: 'gray' },
];

const quotePromptFields: EditorPromptSpec['fields'] = [
    { name: 'color', label: '引用颜色', type: 'color', defaultValue: '#af870d' },
    { name: 'content', label: '引用内容', type: 'textarea', defaultValue: '引用内容', placeholder: '请输入引用内容' },
];

const cloudOptions = [
    { label: '默认网盘', value: 'default' },
    { label: '360 网盘', value: '360' },
    { label: '百度网盘', value: 'bd' },
    { label: '天翼网盘', value: 'ty' },
    { label: '城通网盘', value: 'ct' },
    { label: '微云网盘', value: 'wy' },
    { label: 'Github 仓库', value: 'github' },
    { label: '蓝奏云网盘', value: 'lz' },
];

const timeFormatOptions = [
    { label: '2026-05-22 13:39:08', value: 'YYYY-MM-DD HH:mm:ss' },
    { label: '2026-05-22 13:39', value: 'YYYY-MM-DD HH:mm' },
    { label: '2026-05-22', value: 'YYYY-MM-DD' },
    { label: '2026年05月22日 13:39:08', value: 'YYYY年MM月DD日 HH:mm:ss' },
    { label: '05月22日 13:39:08', value: 'MM月DD日 HH:mm:ss' },
    { label: '13:39:08', value: 'HH:mm:ss' },
];

export const editorPromptSpecs: Record<string, EditorPromptSpec> = {
    link: {
        id: 'link',
        title: '插入链接',
        fields: [
            { name: 'title', label: '链接标题', type: 'text', defaultValue: '链接文字' },
            { name: 'url', label: '链接地址', type: 'text', defaultValue: 'https://example.com' },
        ],
    },
    image: {
        id: 'image',
        title: '插入图片',
        fields: [
            { name: 'title', label: '图片名称', type: 'text', defaultValue: '图片描述' },
            { name: 'url', label: '图片地址', type: 'text', defaultValue: '/assets/zfy/placeholders/blue.svg' },
        ],
    },
    table: {
        id: 'table',
        title: '插入表格',
        fields: [
            { name: 'columns', label: '列数', type: 'number', defaultValue: '2', min: 1, max: 6 },
            { name: 'rows', label: '行数', type: 'number', defaultValue: '2', min: 1, max: 8 },
        ],
    },
    'code-block': {
        id: 'code-block',
        title: '插入代码块',
        fields: [
            { name: 'language', label: '语言', type: 'text', defaultValue: '' },
            { name: 'content', label: '代码内容', type: 'textarea', defaultValue: '代码内容' },
        ],
    },
    quote: {
        id: 'quote',
        title: '彩色引用',
        fields: quotePromptFields,
    },
    'zfy-alert': {
        id: 'zfy-alert',
        title: '插入提示',
        fields: [
            { name: 'color', label: '提示颜色', type: 'select', defaultValue: 'blue', options: alertColorOptions },
            { name: 'icon', label: '提示图标', type: 'icon', defaultValue: 'info' },
            { name: 'content', label: '提示内容', type: 'textarea', defaultValue: '测试提醒框' },
        ],
    },
    'zfy-callout': {
        id: 'zfy-callout',
        title: '插入标注',
        fields: [
            { name: 'color', label: '边框颜色', type: 'color', defaultValue: '#f0ad4e' },
            { name: 'content', label: '标注内容', type: 'textarea', defaultValue: '标注内容' },
        ],
    },
    'zfy-quote': {
        id: 'zfy-quote',
        title: '彩色引用',
        fields: quotePromptFields,
    },
    'zfy-mtitle': {
        id: 'zfy-mtitle',
        title: '居中标题',
        fields: [
            { name: 'title', label: '标题内容', type: 'text', defaultValue: '居中标题' },
        ],
    },
    'zfy-card-default': {
        id: 'zfy-card-default',
        title: '默认卡片',
        fields: [
            { name: 'title', label: '卡片标题', type: 'text', defaultValue: '卡片标题' },
            { name: 'width', label: '卡片宽度', type: 'text', defaultValue: '100%' },
            { name: 'content', label: '卡片内容', type: 'textarea', defaultValue: '卡片内容' },
        ],
    },
    'zfy-message': {
        id: 'zfy-message',
        title: '消息提示',
        fields: [
            { name: 'type', label: '消息类型', type: 'select', defaultValue: 'success', options: toneOptions },
            { name: 'content', label: '消息内容', type: 'textarea', defaultValue: '消息内容' },
        ],
    },
    'zfy-progress': {
        id: 'zfy-progress',
        title: '进度条',
        fields: [
            { name: 'value', label: '百分比', type: 'number', defaultValue: '60', min: 0, max: 100 },
            { name: 'color', label: '自定义色', type: 'color', defaultValue: '#ff6c6c' },
        ],
    },
    'zfy-bilibili': {
        id: 'zfy-bilibili',
        title: 'BiliBili 视频',
        fields: [
            { name: 'bvid', label: '视频 BVID', type: 'text', defaultValue: 'BV1xx411c7mD' },
            { name: 'page', label: '视频选集', type: 'number', defaultValue: '1', min: 1, max: 999 },
            { name: 'title', label: '显示标题', type: 'text', defaultValue: '视频' },
        ],
    },
    'zfy-dplayer': {
        id: 'zfy-dplayer',
        title: 'M3U8/MP4 视频',
        fields: [
            { name: 'title', label: '显示标题', type: 'text', defaultValue: '视频' },
            { name: 'url', label: '视频地址', type: 'text', defaultValue: '/video/demo.mp4' },
        ],
    },
    'zfy-music-list': {
        id: 'zfy-music-list',
        title: '网易云列表',
        fields: [
            { name: 'id', label: '歌单 ID', type: 'text', defaultValue: '歌单ID' },
            { name: 'color', label: '主题色', type: 'color', defaultValue: '#1989fa' },
        ],
    },
    'zfy-music': {
        id: 'zfy-music',
        title: '网易云单首',
        fields: [
            { name: 'id', label: '歌曲 ID', type: 'text', defaultValue: '歌曲ID' },
            { name: 'color', label: '主题色', type: 'color', defaultValue: '#1989fa' },
        ],
    },
    'zfy-mp3': {
        id: 'zfy-mp3',
        title: '外部音乐',
        fields: [
            { name: 'title', label: '音频名称', type: 'text', defaultValue: '音频名称' },
            { name: 'url', label: '音频地址', type: 'text', defaultValue: '/audio/demo.mp3' },
            { name: 'cover', label: '音频封面', type: 'text', defaultValue: '/assets/zfy/placeholders/blue.svg' },
            { name: 'color', label: '主题色', type: 'color', defaultValue: '#f0ad4e' },
        ],
    },
    'zfy-abtn': {
        id: 'zfy-abtn',
        title: '多彩按钮',
        fields: [
            { name: 'title', label: '按钮内容', type: 'text', defaultValue: '按钮内容' },
            { name: 'url', label: '跳转链接', type: 'text', defaultValue: 'https://example.com' },
            { name: 'icon', label: '按钮图标', type: 'icon', defaultValue: 'fa fa-handshake-o' },
            { name: 'color', label: '按钮颜色', type: 'color', defaultValue: '#ff6800' },
            { name: 'radius', label: '按钮圆角', type: 'text', defaultValue: '8px' },
        ],
    },
    'zfy-button': {
        id: 'zfy-button',
        title: '访问按钮',
        fields: [
            { name: 'title', label: '按钮内容', type: 'text', defaultValue: '访问链接' },
            { name: 'url', label: '跳转链接', type: 'text', defaultValue: 'https://example.com' },
            { name: 'color', label: '按钮颜色', type: 'color', defaultValue: '#1989fa' },
            { name: 'radius', label: '按钮圆角', type: 'text', defaultValue: '8px' },
        ],
    },
    'zfy-anote': {
        id: 'zfy-anote',
        title: '便条按钮',
        fields: [
            { name: 'title', label: '按钮内容', type: 'text', defaultValue: '按钮内容' },
            { name: 'url', label: '跳转链接', type: 'text', defaultValue: 'https://example.com' },
            { name: 'icon', label: '按钮图标', type: 'icon', defaultValue: 'fa fa-handshake-o' },
            { name: 'type', label: '按钮类型', type: 'select', defaultValue: 'secondary', options: [
                { label: 'secondary', value: 'secondary' },
                { label: 'success', value: 'success' },
                { label: 'warning', value: 'warning' },
                { label: 'error', value: 'error' },
            ] },
        ],
    },
    'zfy-dotted': {
        id: 'zfy-dotted',
        title: '彩色虚线',
        fields: [
            { name: 'startColor', label: '开始颜色', type: 'color', defaultValue: '#ff6c6c' },
            { name: 'endColor', label: '结束颜色', type: 'color', defaultValue: '#1989fa' },
        ],
    },
    'zfy-cloud': {
        id: 'zfy-cloud',
        title: '网盘下载',
        fields: [
            { name: 'type', label: '网盘类型', type: 'select', defaultValue: 'default', options: cloudOptions },
            { name: 'title', label: '显示标题', type: 'text', defaultValue: '下载资源' },
            { name: 'url', label: '下载地址', type: 'text', defaultValue: 'https://example.com' },
            { name: 'password', label: '提取密码', type: 'text', defaultValue: '' },
        ],
    },
    time: {
        id: 'time',
        title: '插入当前时间',
        fields: [
            { name: 'format', label: '显示格式', type: 'select', defaultValue: 'YYYY-MM-DD HH:mm:ss', options: timeFormatOptions },
        ],
    },
    'zfy-grid': {
        id: 'zfy-grid',
        title: '插入宫格',
        fields: [
            { name: 'column', label: '宫格列数', type: 'number', defaultValue: '3', min: 1, max: 4 },
            { name: 'gap', label: '宫格间隔', type: 'number', defaultValue: '15', min: 0, max: 48 },
        ],
    },
    'zfy-copy': {
        id: 'zfy-copy',
        title: '复制文本',
        fields: [
            { name: 'showText', label: '显示文案', type: 'text', defaultValue: '显示文案' },
            { name: 'copyText', label: '复制内容', type: 'textarea', defaultValue: '复制内容' },
        ],
    },
};

export function getEditorPromptSpec(tool: EditorTool): EditorPromptSpec | null {
    return editorPromptSpecs[tool.id] || null;
}

export function valuesFromSpec(spec: EditorPromptSpec): PromptValues {
    return Object.fromEntries(spec.fields.map((field) => [field.name, field.defaultValue || '']));
}

export function toolFromPrompt(tool: EditorTool, values: PromptValues): EditorTool {
    const snippet = snippetFor(tool.id, values).replace('{date}', todayText());
    const action = tool.action === 'blockInsert' || tool.action === 'blockWrap' || snippet.includes('\n')
        ? 'blockInsert'
        : 'insert';

    return {
        ...tool,
        action,
        snippet,
        prefix: undefined,
        suffix: undefined,
    };
}

function snippetFor(id: string, values: PromptValues): string {
    const value = (key: string, fallback = '') => cleanAttribute(values[key] || fallback);
    const text = (key: string, fallback = '') => (values[key] || fallback).trim();

    switch (id) {
        case 'link':
            return `[${text('title', '链接文字')}](${value('url', 'https://example.com')})`;
        case 'image':
            return `![${text('title', '图片描述')}](${value('url', '/assets/zfy/placeholders/blue.svg')})`;
        case 'table':
            return buildTable(Number(value('columns', '2')), Number(value('rows', '2')));
        case 'code-block':
            return `\`\`\`${value('language')}\n${text('content', '代码内容')}\n\`\`\``;
        case 'quote':
            return quoteSnippet(value('color', '#af870d'), text('content', '引用内容'));
        case 'zfy-alert':
            return `{zfy-alert color="${value('color', 'blue')}" icon="${value('icon') || 'none'}"}` + `\n${text('content', '测试提醒框')}\n{/zfy-alert}`;
        case 'zfy-callout':
            return `{zfy-callout color="${value('color', '#f0ad4e')}"}` + `\n${text('content', '标注内容')}\n{/zfy-callout}`;
        case 'zfy-quote':
            return quoteSnippet(value('color', '#af870d'), text('content', '引用内容'));
        case 'zfy-mtitle':
            return `{zfy-mtitle title="${value('title', '居中标题')}" /}`;
        case 'zfy-card-default':
            return `{zfy-card-default title="${value('title', '卡片标题')}" width="${value('width', '100%')}"}` + `\n${text('content', '卡片内容')}\n{/zfy-card-default}`;
        case 'zfy-message':
            return `{zfy-message type="${value('type', 'success')}"}` + `\n${text('content', '消息内容')}\n{/zfy-message}`;
        case 'zfy-progress':
            return `{zfy-progress value="${value('value', '60')}" color="${value('color', '#ff6c6c')}" /}`;
        case 'zfy-bilibili':
            return `{zfy-bilibili title="${value('title', '视频')}" bvid="${value('bvid', 'BV1xx411c7mD')}" page="${value('page', '1')}" /}`;
        case 'zfy-dplayer':
            return `{zfy-dplayer title="${value('title', '视频')}" url="${value('url', '/video/demo.mp4')}" /}`;
        case 'zfy-music-list':
            return `{zfy-music-list id="${value('id', '歌单ID')}" color="${value('color', '#1989fa')}" /}`;
        case 'zfy-music':
            return `{zfy-music id="${value('id', '歌曲ID')}" color="${value('color', '#1989fa')}" /}`;
        case 'zfy-mp3':
            return `{zfy-mp3 title="${value('title', '音频名称')}" url="${value('url', '/audio/demo.mp3')}" cover="${value('cover')}" color="${value('color', '#f0ad4e')}" /}`;
        case 'zfy-abtn':
            return `{zfy-abtn icon="${value('icon', 'fa fa-handshake-o')}" color="${value('color', '#ff6800')}" url="${value('url', 'https://example.com')}" radius="${value('radius', '8px')}" title="${value('title', '按钮内容')}" /}`;
        case 'zfy-button':
            return `{zfy-button color="${value('color', '#1989fa')}" url="${value('url', 'https://example.com')}" radius="${value('radius', '8px')}" title="${value('title', '访问链接')}" /}`;
        case 'zfy-anote':
            return `{zfy-anote icon="${value('icon', 'fa fa-handshake-o')}" url="${value('url', 'https://example.com')}" type="${value('type', 'secondary')}" title="${value('title', '按钮内容')}" /}`;
        case 'zfy-dotted':
            return `{zfy-dotted startColor="${value('startColor', '#ff6c6c')}" endColor="${value('endColor', '#1989fa')}" /}`;
        case 'zfy-cloud':
            return `{zfy-cloud title="${value('title', '下载资源')}" type="${value('type', 'default')}" url="${value('url', 'https://example.com')}" password="${value('password')}" /}`;
        case 'time':
            return `{zfy-time format="${value('format', 'YYYY-MM-DD HH:mm:ss')}" /}`;
        case 'zfy-grid':
            return `{zfy-grid column="${value('column', '3')}" gap="${value('gap', '15')}"}` + '\n{zfy-grid-item}\n宫格内容一\n{/zfy-grid-item}\n{zfy-grid-item}\n宫格内容二\n{/zfy-grid-item}\n{zfy-grid-item}\n宫格内容三\n{/zfy-grid-item}\n{/zfy-grid}';
        case 'zfy-copy':
            return `{zfy-copy showText="${value('showText', '显示文案')}" copyText="${value('copyText', '复制内容')}" /}`;
        default:
            return '';
    }
}

function quoteSnippet(color: string, content: string): string {
    return `{zfy-quote color="${color}"}` + `\n${content}\n{/zfy-quote}`;
}

function buildTable(columns: number, rows: number): string {
    const safeColumns = Math.max(1, Math.min(6, Number.isFinite(columns) ? columns : 2));
    const safeRows = Math.max(1, Math.min(8, Number.isFinite(rows) ? rows : 2));
    const header = Array.from({ length: safeColumns }, (_, index) => `标题${index + 1}`);
    const separator = Array.from({ length: safeColumns }, () => '---');
    const body = Array.from({ length: safeRows }, (_, row) => Array.from({ length: safeColumns }, (_, column) => `文本${row + 1}-${column + 1}`));

    return [header, separator, ...body].map((row) => `| ${row.join(' | ')} |`).join('\n');
}

function cleanAttribute(value: string): string {
    return value.replace(/["<>]/g, '').trim();
}
