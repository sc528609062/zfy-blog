/**
 * zfy-blog TipTap 三模式编辑器入口
 *
 * Sprint 1 仅创建文件骨架；完整实现见 Sprint 2 (M3)。
 * 模式：块编辑（默认）/ 富文本 / Markdown
 *
 * 接入方式（Sprint 2）：
 *   import { mountZfyEditor } from '@/editor';
 *   mountZfyEditor(document.querySelector('#editor'), { initialJson, onSave });
 */
export function mountZfyEditor(el, options = {}) {
    console.warn('[zfy-editor] TipTap editor will be mounted in Sprint 2 (M3).', { el, options });
}

if (typeof window !== 'undefined') {
    window.mountZfyEditor = mountZfyEditor;
}
