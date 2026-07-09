import type { EditorTool } from './types';

const fallbackToolbar: EditorTool[] = [
    { id: 'bold', label: '加粗', icon: 'EditPen', action: 'wrap', prefix: '**', suffix: '**', placeholder: '加粗文字' },
    { id: 'italic', label: '斜体', icon: 'Edit', action: 'wrap', prefix: '*', suffix: '*', placeholder: '斜体文字' },
    { id: 'quote', label: '彩色引用', icon: 'ChatLineSquare', action: 'blockWrap', prefix: '{zfy-quote color="#af870d"}\n', suffix: '\n{/zfy-quote}', placeholder: '引用内容' },
    { id: 'link', label: '链接', icon: 'Link', action: 'wrap', prefix: '[', suffix: '](https://example.com)', placeholder: '链接文字' },
    { id: 'image', label: '媒体库', icon: 'Picture', action: 'insert', snippet: '![图片描述](/assets/zfy/placeholders/blue.svg)' },
    { id: 'preview', label: '预览', icon: 'View', action: 'preview' },
];

function normalizeTool(tool: EditorTool, canUseRawHtml: boolean): EditorTool | null {
    if (tool.requiresRawHtml && !canUseRawHtml) {
        return null;
    }

    const children = (tool.children || [])
        .map((child) => normalizeTool(child, canUseRawHtml))
        .filter(Boolean) as EditorTool[];

    return {
        ...tool,
        children: children.length > 0 ? children : undefined,
    };
}

export function normalizeToolbar(toolbar: EditorTool[] | undefined, canUseRawHtml: boolean): EditorTool[] {
    const source = Array.isArray(toolbar) && toolbar.length > 0 ? toolbar : fallbackToolbar;

    return source
        .map((tool) => normalizeTool(tool, canUseRawHtml))
        .filter(Boolean) as EditorTool[];
}

export function todayText(): string {
    const now = new Date();
    const pad = (value: number) => String(value).padStart(2, '0');

    return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())} ${pad(now.getHours())}:${pad(now.getMinutes())}`;
}
