import { defaultKeymap, history, historyKeymap, indentWithTab, redo, undo } from '@codemirror/commands';
import { markdown } from '@codemirror/lang-markdown';
import { defaultHighlightStyle, syntaxHighlighting } from '@codemirror/language';
import { openSearchPanel, searchKeymap } from '@codemirror/search';
import { EditorState } from '@codemirror/state';
import { EditorView, keymap, placeholder as cmPlaceholder } from '@codemirror/view';
import { nextTick, onBeforeUnmount, onMounted, shallowRef, useTemplateRef, watch, type Ref } from 'vue';
import { todayText } from './editorTools';
import type { EditorTool } from './types';

interface MarkdownEditorOptions {
    onSave?: () => void;
    onFullscreen?: () => void;
    uploadImage?: (file: File) => Promise<string>;
}

export function useMarkdownEditor(model: Ref<string>, options: MarkdownEditorOptions = {}) {
    const hostRef = useTemplateRef<HTMLElement>('editorHost');
    const viewRef = shallowRef<EditorView | null>(null);
    const cursorLine = shallowRef(1);
    const cursorColumn = shallowRef(1);
    const scrollListeners = new Set<(ratio: number) => void>();
    let removeScrollListener: (() => void) | undefined;

    onMounted(() => {
        if (!hostRef.value) {
            return;
        }

        viewRef.value = new EditorView({
            parent: hostRef.value,
            state: EditorState.create({
                doc: model.value || '',
                extensions: [
                    history(),
                    markdown(),
                    syntaxHighlighting(defaultHighlightStyle, { fallback: true }),
                    keymap.of([
                        indentWithTab,
                        ...editorKeymap(),
                        ...searchKeymap,
                        ...defaultKeymap,
                        ...historyKeymap,
                    ]),
                    cmPlaceholder('开始写作...'),
                    EditorView.lineWrapping,
                    EditorView.domEventHandlers({
                        paste(event) {
                            return handleFileTransfer(event.clipboardData?.files);
                        },
                        drop(event) {
                            return handleFileTransfer(event.dataTransfer?.files);
                        },
                    }),
                    EditorView.updateListener.of((update) => {
                        if (update.docChanged) {
                            const value = update.state.doc.toString();
                            if (value !== model.value) {
                                model.value = value;
                            }
                        }

                        if (update.selectionSet || update.docChanged) {
                            updateCursor(update.state);
                        }
                    }),
                ],
            }),
        });

        updateCursor(viewRef.value.state);
        const scroller = viewRef.value.scrollDOM;
        const handleScroll = () => {
            const max = Math.max(0, scroller.scrollHeight - scroller.clientHeight);
            const ratio = max > 0 ? scroller.scrollTop / max : 0;
            scrollListeners.forEach((listener) => listener(ratio));
        };
        scroller.addEventListener('scroll', handleScroll, { passive: true });
        removeScrollListener = () => scroller.removeEventListener('scroll', handleScroll);
    });

    onBeforeUnmount(() => {
        removeScrollListener?.();
        scrollListeners.clear();
        viewRef.value?.destroy();
        viewRef.value = null;
    });

    watch(model, (value) => {
        const view = viewRef.value;
        if (!view || value === view.state.doc.toString()) {
            return;
        }

        view.dispatch({
            changes: { from: 0, to: view.state.doc.length, insert: value || '' },
        });
    });

    function editorKeymap() {
        return [
            { key: 'Mod-b', preventDefault: true, run: () => runWrap('**', '**', '加粗文字') },
            { key: 'Mod-i', preventDefault: true, run: () => runWrap('*', '*', '斜体文字') },
            { key: 'Mod-k', preventDefault: true, run: () => runWrap('[', '](https://)', '链接文字') },
            { key: 'Mod-s', preventDefault: true, run: () => runCommand(options.onSave) },
            { key: 'F11', preventDefault: true, run: () => runCommand(options.onFullscreen) },
            { key: 'Shift-Mod-7', preventDefault: true, run: () => runPrefix('', '列表项', true) },
            { key: 'Shift-Mod-8', preventDefault: true, run: () => runPrefix('- ', '列表项', false) },
            { key: 'Shift-Mod-.', preventDefault: true, run: () => runPrefix('> ', '引用内容', false) },
        ];
    }

    function runWrap(prefix: string, suffix: string, fallback: string): boolean {
        wrapSelection(prefix, suffix, fallback);

        return true;
    }

    function runPrefix(prefix: string, fallback: string, ordered: boolean): boolean {
        prefixLines(prefix, fallback, ordered);

        return true;
    }

    function runCommand(command?: () => void): boolean {
        command?.();

        return true;
    }

    function applyTool(tool: EditorTool): void {
        const view = viewRef.value;
        if (!view) {
            return;
        }

        if (tool.action === 'command') {
            if (tool.command === 'undo') {
                undo(view);
            }
            if (tool.command === 'redo') {
                redo(view);
            }
            focus();
            return;
        }

        if (tool.action === 'wrap' || tool.action === 'blockWrap') {
            wrapSelection(tool.prefix || '', tool.suffix || '', tool.placeholder || tool.label, tool.action === 'blockWrap' || Boolean(tool.block));
            return;
        }

        if (tool.action === 'linePrefix') {
            prefixLines(tool.prefix || '', tool.placeholder || tool.label, tool.id === 'ordered-list');
            return;
        }

        if (tool.action === 'blockInsert') {
            insertBlock((tool.snippet || '').replace('{date}', todayText()));
            return;
        }

        if (tool.action === 'insert') {
            const snippet = (tool.snippet || '').replace('{date}', todayText());
            if (tool.block || snippet.includes('\n')) {
                insertBlock(snippet);
                return;
            }

            insertText(snippet);
        }
    }

    function setValue(value: string): void {
        const view = viewRef.value;
        model.value = value;

        if (!view) {
            return;
        }

        view.dispatch({
            changes: { from: 0, to: view.state.doc.length, insert: value },
        });
    }

    function insertText(text: string): void {
        const view = viewRef.value;
        if (!view) {
            return;
        }

        const range = view.state.selection.main;
        view.dispatch({
            changes: { from: range.from, to: range.to, insert: text },
            selection: { anchor: range.from + text.length },
        });
        focus();
    }

    function insertBlock(text: string): void {
        const view = viewRef.value;
        if (!view) {
            return;
        }

        const range = view.state.selection.main;
        const prepared = prepareBlockInsert(text, range.from, range.to);

        view.dispatch({
            changes: { from: range.from, to: range.to, insert: prepared.text },
            selection: { anchor: range.from + prepared.offset + prepared.bodyLength },
        });
        focus();
    }

    function wrapSelection(prefix: string, suffix: string, fallback: string, block = false): void {
        const view = viewRef.value;
        if (!view) {
            return;
        }

        const range = view.state.selection.main;
        const selected = view.state.sliceDoc(range.from, range.to) || fallback;
        const text = `${prefix}${selected}${suffix}`;
        const prepared = block
            ? prepareBlockInsert(text, range.from, range.to)
            : { text, offset: 0, bodyLength: text.length };

        view.dispatch({
            changes: { from: range.from, to: range.to, insert: prepared.text },
            selection: {
                anchor: range.from + prepared.offset + prefix.length,
                head: range.from + prepared.offset + prefix.length + selected.length,
            },
        });
        focus();
    }

    function prefixLines(prefix: string, fallback: string, ordered = false): void {
        const view = viewRef.value;
        if (!view) {
            return;
        }

        const range = view.state.selection.main;
        if (range.empty) {
            const line = view.state.doc.lineAt(range.from);
            const lineText = view.state.sliceDoc(line.from, line.to);
            const text = lineText ? `${ordered ? '1. ' : prefix}${lineText}` : `${ordered ? '1. ' : prefix}${fallback}`;

            view.dispatch({
                changes: { from: line.from, to: line.to, insert: text },
                selection: { anchor: line.from + text.length },
            });
            focus();
            return;
        }

        const fromLine = view.state.doc.lineAt(range.from);
        const toLine = view.state.doc.lineAt(range.to);
        const selected = view.state.sliceDoc(fromLine.from, toLine.to);
        const text = selected
            .split('\n')
            .map((line, index) => (line.trim() === '' ? '' : `${ordered ? `${index + 1}. ` : prefix}${line}`))
            .join('\n');

        view.dispatch({
            changes: { from: fromLine.from, to: toLine.to, insert: text },
            selection: { anchor: fromLine.from, head: fromLine.from + text.length },
        });
        focus();
    }

    function prepareBlockInsert(text: string, from: number, to: number): { text: string; offset: number; bodyLength: number } {
        const view = viewRef.value;
        const body = text.replace(/\r\n?/g, '\n').replace(/^\n+|\n+$/g, '');

        if (!view || !body) {
            return { text: body, offset: 0, bodyLength: body.length };
        }

        const before = view.state.sliceDoc(0, from);
        const after = view.state.sliceDoc(to, view.state.doc.length);
        const prefix = before.length === 0 ? '' : before.endsWith('\n\n') ? '' : before.endsWith('\n') ? '\n' : '\n\n';
        const suffix = after.length === 0 ? '' : after.startsWith('\n\n') ? '' : after.startsWith('\n') ? '\n' : '\n\n';

        return {
            text: `${prefix}${body}${suffix}`,
            offset: prefix.length,
            bodyLength: body.length,
        };
    }

    function focus(): void {
        void nextTick(() => viewRef.value?.focus());
    }

    function showSearch(): void {
        const view = viewRef.value;
        if (view) {
            openSearchPanel(view);
            view.focus();
        }
    }

    function scrollToLine(lineNumber: number): void {
        const view = viewRef.value;
        if (!view) {
            return;
        }

        const line = view.state.doc.line(Math.min(Math.max(1, lineNumber), view.state.doc.lines));
        view.dispatch({
            selection: { anchor: line.from },
            effects: EditorView.scrollIntoView(line.from, { y: 'start', yMargin: 48 }),
        });
        focus();
    }

    function scrollToRatio(ratio: number): void {
        const scroller = viewRef.value?.scrollDOM;
        if (!scroller) {
            return;
        }

        const max = Math.max(0, scroller.scrollHeight - scroller.clientHeight);
        scroller.scrollTop = Math.min(1, Math.max(0, ratio)) * max;
    }

    function onScroll(listener: (ratio: number) => void): () => void {
        scrollListeners.add(listener);

        return () => scrollListeners.delete(listener);
    }

    function updateCursor(state: EditorState): void {
        const position = state.selection.main.head;
        const line = state.doc.lineAt(position);
        cursorLine.value = line.number;
        cursorColumn.value = position - line.from + 1;
    }

    function handleFileTransfer(files?: FileList | null): boolean {
        if (!files || !options.uploadImage) {
            return false;
        }

        const images = [...files].filter((file) => file.type.startsWith('image/'));
        if (images.length === 0) {
            return false;
        }

        void uploadImages(images);

        return true;
    }

    async function uploadImages(files: File[]): Promise<void> {
        for (const file of files) {
            try {
                const snippet = await options.uploadImage?.(file);
                if (snippet) {
                    insertBlock(snippet);
                }
            } catch {
                // 上传组件负责显示具体失败原因，继续处理剩余图片。
            }
        }
    }

    return {
        hostRef,
        viewRef,
        cursorLine,
        cursorColumn,
        applyTool,
        setValue,
        insertText,
        focus,
        showSearch,
        scrollToLine,
        scrollToRatio,
        onScroll,
    };
}
