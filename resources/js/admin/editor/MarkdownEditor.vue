<script setup lang="ts">
import { ElMessageBox } from 'element-plus';
import { shallowRef } from 'vue';
import EditorInsertDialog from './EditorInsertDialog.vue';
import EditorToolbar from './EditorToolbar.vue';
import { getEditorPromptSpec, toolFromPrompt } from './editorToolPrompts';
import { useMarkdownEditor } from './useMarkdownEditor';
import type { EditorPromptSpec, EditorTool } from './types';

defineProps<{
    tools: EditorTool[];
    busy?: boolean;
    previewVisible?: boolean;
    fullscreen?: boolean;
}>();

const emit = defineEmits<{
    save: [status: 'draft'];
    publish: [status: 'published'];
    preview: [];
    fullscreen: [];
}>();

const model = defineModel<string>({ required: true });
const { hostRef, applyTool, setValue } = useMarkdownEditor(model);
const promptVisible = shallowRef(false);
const promptSpec = shallowRef<EditorPromptSpec | null>(null);
const promptTool = shallowRef<EditorTool | null>(null);

function downloadMarkdown(): void {
    const blob = new Blob([model.value || ''], { type: 'text/markdown;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const anchor = document.createElement('a');

    anchor.href = url;
    anchor.download = `zfy-post-${Date.now()}.md`;
    anchor.click();
    URL.revokeObjectURL(url);
}

async function cleanEditor(): Promise<void> {
    try {
        await ElMessageBox.confirm('确定清空当前编辑内容？', '清空编辑器', {
            confirmButtonText: '清空',
            cancelButtonText: '取消',
            type: 'warning',
        });
    } catch {
        return;
    }

    setValue('');
}

async function handleTool(tool: EditorTool): Promise<void> {
    if (tool.action === 'save') {
        emit('save', 'draft');
        return;
    }

    if (tool.action === 'publish') {
        emit('publish', 'published');
        return;
    }

    if (tool.action === 'preview') {
        emit('preview');
        return;
    }

    if (tool.action === 'fullscreen') {
        emit('fullscreen');
        return;
    }

    if (tool.action === 'download') {
        downloadMarkdown();
        return;
    }

    if (tool.action === 'clean') {
        await cleanEditor();
        return;
    }

    const spec = getEditorPromptSpec(tool);
    if (spec) {
        promptSpec.value = spec;
        promptTool.value = tool;
        promptVisible.value = true;
        return;
    }

    applyTool(tool);
}

function handlePromptSubmit(values: Record<string, string>): void {
    if (!promptTool.value) {
        return;
    }

    applyTool(toolFromPrompt(promptTool.value, values));
    promptTool.value = null;
    promptSpec.value = null;
}
</script>

<template>
    <section :class="['zfy-markdown-editor', { 'is-fullscreen': fullscreen }]">
        <EditorToolbar
            :busy="busy"
            :fullscreen="fullscreen"
            :preview-visible="previewVisible"
            :tools="tools"
            @tool="handleTool"
        />
        <div :class="['zfy-markdown-editor-body', { 'has-preview': previewVisible }]">
            <div ref="hostRef" class="zfy-markdown-editor-host" />
            <slot name="preview" />
        </div>
        <EditorInsertDialog v-model:visible="promptVisible" :spec="promptSpec" @submit="handlePromptSubmit" />
    </section>
</template>
