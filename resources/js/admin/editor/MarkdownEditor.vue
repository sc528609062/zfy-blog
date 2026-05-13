<script setup lang="ts">
import { ElMessageBox } from 'element-plus';
import { shallowRef } from 'vue';
import EditorImageDialog from './EditorImageDialog.vue';
import EditorInsertDialog from './EditorInsertDialog.vue';
import EditorToolbar from './EditorToolbar.vue';
import { getEditorPromptSpec, toolFromPrompt } from './editorToolPrompts';
import { useMarkdownEditor } from './useMarkdownEditor';
import type { EditorMediaConfig, EditorPromptSpec, EditorTool } from './types';

const props = defineProps<{
    tools: EditorTool[];
    busy?: boolean;
    previewVisible?: boolean;
    fullscreen?: boolean;
    routes?: Record<string, string>;
    media?: EditorMediaConfig;
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
const imageVisible = shallowRef(false);
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
    if (tool.id === 'image') {
        imageVisible.value = true;
        return;
    }

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

function handleImageSubmit(snippet: string): void {
    if (!snippet.trim()) {
        return;
    }

    applyTool({
        id: 'image-inline',
        label: '图片',
        action: 'insert',
        snippet,
    });
    imageVisible.value = false;
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
        <EditorImageDialog
            v-model:visible="imageVisible"
            :media="props.media"
            :library-url="String(props.routes?.media_library || '/admin/media/library')"
            :upload-url="String(props.routes?.media_upload || '/admin/media/upload')"
            @submit="handleImageSubmit"
        />
    </section>
</template>
