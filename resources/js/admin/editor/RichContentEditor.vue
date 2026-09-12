<script setup lang="ts">
import { computed, watch } from 'vue';
import { EditorContent, useEditor, VueNodeViewRenderer } from '@tiptap/vue-3';
import { Extension, Node } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import { TableKit } from '@tiptap/extension-table';
import Image from '@tiptap/extension-image';
import TaskList from '@tiptap/extension-task-list';
import TaskItem from '@tiptap/extension-task-item';
import { TextStyle, Color } from '@tiptap/extension-text-style';
import TextAlign from '@tiptap/extension-text-align';
import { Back, Right, List, Grid, Picture, Plus, SortUp, SortDown, Delete, Link, DArrowLeft, DArrowRight, EditPen } from '@element-plus/icons-vue';
import { ElMessageBox } from 'element-plus';
import SourceBlock from './SourceBlock.vue';
import RestrictedBlock from './RestrictedBlock.vue';
import ExtensionBlock from './ExtensionBlock.vue';
const props = defineProps<{ modelValue: Record<string, any>; mode: string; blocks?: Record<string, any>[] }>();
const emit = defineEmits<{ 'update:modelValue': [value: Record<string, any>] }>();
const Indent = Extension.create({
    name: 'zfyIndent',
    addGlobalAttributes: () => [{ types: ['paragraph', 'heading'], attributes: { indent: {
        default: 0,
        parseHTML: element => Math.max(0, Math.min(8, Number(element.getAttribute('data-indent') || 0))),
        renderHTML: attrs => attrs.indent > 0 ? { 'data-indent': attrs.indent, style: `margin-left: ${attrs.indent * 2}em` } : {},
    } } }],
});
const Source = Node.create({
    name: 'zfySource', group: 'block', atom: true, draggable: true,
    addAttributes: () => ({ source: { default: '' } }),
    parseHTML: () => [{ tag: 'div[data-zfy-source]' }],
    renderHTML: () => ['div', { 'data-zfy-source': '' }],
    addNodeView: () => VueNodeViewRenderer(SourceBlock),
});
const Restricted = Node.create({
    name: 'zfyRestricted', group: 'block', content: 'block+', defining: true,
    addAttributes: () => ({ rule: { default: 'member' }, label: { default: '此内容需要相应访问权限' } }),
    parseHTML: () => [{ tag: 'section[data-zfy-restricted]' }],
    renderHTML: ({ HTMLAttributes }) => ['section', { ...HTMLAttributes, 'data-zfy-restricted': '' }, 0],
    addNodeView: () => VueNodeViewRenderer(RestrictedBlock),
});
const editor = useEditor({
    extensions: [StarterKit, TableKit, Image, TaskList, TaskItem.configure({ nested: true }), TextStyle, Color, TextAlign.configure({ types: ['heading', 'paragraph'] }), Indent, Source, Restricted, Node.create({
        name: 'zfyExtension', group: 'block', atom: true, draggable: true,
        addOptions: () => ({ definitions: props.blocks || [] }),
        addAttributes: () => ({ key: { default: '' }, values: { default: '{}' } }),
        parseHTML: () => [{ tag: 'div[data-zfy-extension]' }],
        renderHTML: () => ['div', { 'data-zfy-extension': '' }],
        addNodeView: () => VueNodeViewRenderer(ExtensionBlock),
    })],
    content: props.modelValue,
    onUpdate: ({ editor }) => emit('update:modelValue', editor.getJSON()),
});
watch(() => props.modelValue, value => {
    if (editor.value && JSON.stringify(value) !== JSON.stringify(editor.value.getJSON())) editor.value.commands.setContent(value, { emitUpdate: false });
}, { deep: true });
const blocks = computed<Record<string, any>[]>(() => props.modelValue.content || []);
const symbols = ['©', '®', '™', '§', '¶', '±', '×', '÷', '≤', '≥', '≠', '∞', '→', '✓', '★', '😊', '👍', '❤️', '🎉', '📌', '💡', '✅'];
function indent(offset: number) {
    if (!editor.value) return;
    const { state, view } = editor.value;
    const transaction = state.tr;
    state.doc.nodesBetween(state.selection.from, state.selection.to, (node, position) => {
        if (['paragraph', 'heading'].includes(node.type.name)) transaction.setNodeMarkup(position, undefined, { ...node.attrs, indent: Math.max(0, Math.min(8, Number(node.attrs.indent || 0) + offset)) });
    });
    view.dispatch(transaction);
    view.focus();
}
function blockLabel(block: Record<string, any>): string {
    if (block.type === 'zfyExtension') return props.blocks?.find(item => item.key === block.attrs?.key)?.label || '扩展组件';
    return ({ paragraph: '段落', heading: '标题', bulletList: '无序列表', orderedList: '有序列表', taskList: '任务列表', blockquote: '引用', codeBlock: '代码块', horizontalRule: '分隔线', image: '图片', table: '表格', zfySource: '原稿与短代码', zfyRestricted: '权限内容' } as Record<string, string>)[block.type] || block.type;
}
function move(index: number, offset: number) {
    const content = [...blocks.value];
    if (index + offset < 0 || index + offset >= content.length) return;
    [content[index], content[index + offset]] = [content[index + offset], content[index]];
    emit('update:modelValue', { ...props.modelValue, content });
}
function remove(index: number) {
    const content = blocks.value.filter((_: unknown, i: number) => i !== index);
    emit('update:modelValue', { ...props.modelValue, content: content.length ? content : [{ type: 'paragraph' }] });
}
async function addImage() {
    try {
        const { value } = await ElMessageBox.prompt('图片地址', '插入图片', { inputPattern: /^(https?:\/\/|\/)/, inputErrorMessage: '请输入图片地址' });
        editor.value?.chain().focus().setImage({ src: value }).run();
    } catch { /* Dialog cancelled. */ }
}
function setParagraphStyle(value: string) {
    value.startsWith('h') ? editor.value?.chain().focus().toggleHeading({ level: Number(value.slice(1)) as 1 | 2 | 3 | 4 | 5 | 6 }).run() : editor.value?.chain().focus().setParagraph().run();
}
async function setLink() {
    try {
        const { value } = await ElMessageBox.prompt('链接地址', '链接', { inputValue: editor.value?.getAttributes('link').href || '', inputPattern: /^(https?:\/\/|mailto:|\/|$)/, inputErrorMessage: '链接地址无效' });
        if (value) editor.value?.chain().focus().extendMarkRange('link').setLink({ href: value }).run();
        else editor.value?.chain().focus().unsetLink().run();
    } catch { /* Dialog cancelled. */ }
}
function insertBlock(command: string) {
    if (command === 'orderedList') { editor.value?.chain().focus().toggleOrderedList().run(); return; }
    if (command === 'taskList') { editor.value?.chain().focus().toggleTaskList().run(); return; }
    if (command === 'codeBlock') { editor.value?.chain().focus().toggleCodeBlock().run(); return; }
    if (command.startsWith('extension:')) {
        const key = command.slice(10);
        editor.value?.chain().focus().insertContent({ type: 'zfyExtension', attrs: { key, values: JSON.stringify(props.blocks?.find(block => block.key === key)?.defaults || {}) } }).run();
        return;
    }
    if (command.startsWith('restricted:')) {
        editor.value?.chain().focus().insertContent({ type: 'zfyRestricted', attrs: { rule: command.split(':')[1] }, content: [{ type: 'paragraph' }] }).run();
        return;
    }
    editor.value?.chain().focus().insertContent(command === 'source' ? { type: 'zfySource', attrs: { source: '' } } : { type: command, content: command === 'blockquote' ? [{ type: 'paragraph' }] : undefined }).run();
}
</script>
<template>
    <section class="zfy-rich-editor">
        <div v-if="editor" class="zfy-rich-toolbar">
            <el-dropdown v-if="props.blocks?.length" @command="insertBlock"><el-button :icon="Plus">扩展组件</el-button><template #dropdown><el-dropdown-menu><el-dropdown-item v-for="block in props.blocks" :key="block.key" :command="'extension:' + block.key">{{ block.label }}</el-dropdown-item></el-dropdown-menu></template></el-dropdown>
            <el-tooltip content="撤销"><el-button :icon="Back" aria-label="撤销" :disabled="!editor.can().undo()" @click="editor.chain().focus().undo().run()" /></el-tooltip>
            <el-tooltip content="重做"><el-button :icon="Right" aria-label="重做" :disabled="!editor.can().redo()" @click="editor.chain().focus().redo().run()" /></el-tooltip>
            <el-button aria-label="加粗" :type="editor.isActive('bold') ? 'primary' : 'default'" @click="editor.chain().focus().toggleBold().run()"><b>B</b></el-button>
            <el-button aria-label="斜体" @click="editor.chain().focus().toggleItalic().run()"><i>I</i></el-button>
            <el-button aria-label="下划线" @click="editor.chain().focus().toggleUnderline().run()"><u>U</u></el-button>
            <el-button aria-label="删除线" @click="editor.chain().focus().toggleStrike().run()"><s>S</s></el-button>
            <el-tooltip content="行内代码"><el-button aria-label="行内代码" @click="editor.chain().focus().toggleCode().run()"><code>&lt;/&gt;</code></el-button></el-tooltip>
            <el-tooltip content="减少缩进"><el-button :icon="DArrowLeft" aria-label="减少缩进" @click="indent(-1)" /></el-tooltip>
            <el-tooltip content="增加缩进"><el-button :icon="DArrowRight" aria-label="增加缩进" @click="indent(1)" /></el-tooltip>
            <el-dropdown @command="(symbol: string) => editor?.chain().focus().insertContent({ type: 'text', text: symbol }).run()"><el-button :icon="EditPen" aria-label="符号与表情" title="符号与表情" /><template #dropdown><el-dropdown-menu class="zfy-symbol-menu"><el-dropdown-item v-for="symbol in symbols" :key="symbol" :command="symbol">{{ symbol }}</el-dropdown-item></el-dropdown-menu></template></el-dropdown>
            <el-color-picker aria-label="文字颜色" :model-value="editor.getAttributes('textStyle').color || '#303133'" @change="(value: string | null) => value ? editor?.chain().focus().setColor(value).run() : editor?.chain().focus().unsetColor().run()" />
            <el-select aria-label="文字对齐" :model-value="editor.getAttributes('paragraph').textAlign || 'left'" style="width:100px" @change="(value: string) => editor?.chain().focus().setTextAlign(value).run()"><el-option v-for="(label, value) in {left:'左对齐',center:'居中',right:'右对齐',justify:'两端对齐'}" :key="value" :label="label" :value="value" /></el-select>
            <el-tooltip content="链接"><el-button :icon="Link" aria-label="链接" @click="setLink" /></el-tooltip>
            <el-select aria-label="段落样式" :model-value="[1, 2, 3, 4, 5, 6].find(level => editor?.isActive('heading', { level })) ? 'h' + editor.getAttributes('heading').level : 'p'" style="width:110px" @change="setParagraphStyle"><el-option label="段落" value="p" /><el-option v-for="level in 6" :key="level" :label="level + ' 级标题'" :value="'h' + level" /></el-select>
            <el-dropdown @command="insertBlock"><el-button :icon="List" aria-label="列表及代码" title="列表及代码" /><template #dropdown><el-dropdown-menu><el-dropdown-item command="orderedList">有序列表</el-dropdown-item><el-dropdown-item command="taskList">任务列表</el-dropdown-item><el-dropdown-item command="codeBlock">代码块</el-dropdown-item></el-dropdown-menu></template></el-dropdown>
            <el-tooltip content="列表"><el-button :icon="List" aria-label="列表" @click="editor.chain().focus().toggleBulletList().run()" /></el-tooltip>
            <el-tooltip content="表格"><el-button :icon="Grid" aria-label="插入表格" @click="editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()" /></el-tooltip>
            <el-tooltip content="图片"><el-button :icon="Picture" aria-label="插入图片" @click="addImage" /></el-tooltip>
            <el-button v-if="editor.isActive('table')" @click="editor.chain().focus().addRowAfter().run()">插入行</el-button><el-button v-if="editor.isActive('table')" @click="editor.chain().focus().addColumnAfter().run()">插入列</el-button><el-button v-if="editor.isActive('table')" @click="editor.chain().focus().deleteRow().run()">删除行</el-button><el-button v-if="editor.isActive('table')" @click="editor.chain().focus().deleteColumn().run()">删除列</el-button>
            <el-dropdown @command="insertBlock"><el-button :icon="Plus" aria-label="添加块" /><template #dropdown><el-dropdown-menu><el-dropdown-item command="paragraph">段落</el-dropdown-item><el-dropdown-item command="blockquote">引用</el-dropdown-item><el-dropdown-item command="horizontalRule">分隔线</el-dropdown-item><el-dropdown-item command="source">Markdown / 短代码</el-dropdown-item><el-dropdown-item command="restricted:member">登录可见</el-dropdown-item><el-dropdown-item command="restricted:vip">VIP 可见</el-dropdown-item><el-dropdown-item command="restricted:purchased">购买可见</el-dropdown-item><el-dropdown-item command="restricted:comment">评论后可见</el-dropdown-item></el-dropdown-menu></template></el-dropdown>
        </div>
        <div :class="['zfy-rich-body', { 'has-blocks': mode === 'blocks' }]">
            <aside v-if="mode === 'blocks'" class="zfy-block-outline">
                <div v-for="(block, index) in blocks" :key="index"><span>{{ index + 1 }}. {{ blockLabel(block) }}</span><el-button :icon="SortUp" text :disabled="index === 0" aria-label="上移块" @click="move(index, -1)" /><el-button :icon="SortDown" text :disabled="index === blocks.length - 1" aria-label="下移块" @click="move(index, 1)" /><el-button :icon="Delete" text aria-label="删除块" @click="remove(index)" /></div>
            </aside>
            <EditorContent :editor="editor" class="zfy-rich-content" />
        </div>
    </section>
</template>
<style>.zfy-symbol-menu{display:grid;grid-template-columns:repeat(6,40px)}.zfy-symbol-menu .el-dropdown-menu__item{justify-content:center;padding:8px;font-size:18px}</style>
<style scoped>
.zfy-rich-editor{border:1px solid var(--zfy-admin-line);border-radius:6px;background:var(--zfy-admin-surface);overflow:hidden}.zfy-rich-toolbar{display:flex;flex-wrap:wrap;gap:5px;padding:10px;border-bottom:1px solid var(--zfy-admin-line)}.zfy-rich-toolbar .el-button+.el-button{margin:0}.zfy-rich-body{min-height:480px}.zfy-rich-body.has-blocks{display:grid;grid-template-columns:220px minmax(0,1fr)}.zfy-rich-content{min-width:0;padding:20px}.zfy-rich-content :deep(.tiptap){min-height:440px;outline:none;overflow-wrap:anywhere;line-height:1.75}.zfy-rich-content :deep(table){border-collapse:collapse;width:100%;table-layout:fixed}.zfy-rich-content :deep(td),.zfy-rich-content :deep(th){border:1px solid var(--zfy-admin-line);padding:8px}.zfy-rich-content :deep(img){max-width:100%;height:auto}.zfy-rich-content :deep(ul){list-style:disc;padding-left:24px}.zfy-rich-content :deep(ol){list-style:decimal;padding-left:24px}.zfy-rich-content :deep(h2){font-size:24px;font-weight:700}.zfy-rich-content :deep(textarea){width:100%;min-height:180px;background:var(--zfy-admin-surface-soft);padding:12px;color:var(--zfy-admin-text);resize:vertical}.zfy-block-outline{padding:8px;border-right:1px solid var(--zfy-admin-line)}.zfy-block-outline>div{display:flex;align-items:center;gap:0}.zfy-block-outline span{flex:1;overflow:hidden;text-overflow:ellipsis;font-size:12px}.zfy-block-outline .el-button{padding:3px;margin:0}@media(max-width:760px){.zfy-rich-body.has-blocks{grid-template-columns:1fr}.zfy-block-outline{border-right:0;border-bottom:1px solid var(--zfy-admin-line);max-height:150px;overflow:auto}}
</style>
