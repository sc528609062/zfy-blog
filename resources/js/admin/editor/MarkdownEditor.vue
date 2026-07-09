<script setup lang="ts">
import { ElMessageBox } from 'element-plus';
import { shallowRef } from 'vue';
import EditorImageDialog from './EditorImageDialog.vue';
import EditorInsertDialog from './EditorInsertDialog.vue';
import EditorSymbolDialog from './EditorSymbolDialog.vue';
import EditorToolbar from './EditorToolbar.vue';
import { getEditorPromptSpec, toolFromPrompt } from './editorToolPrompts';
import type { SymbolPickerKind } from './symbolPresets';
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
const symbolVisible = shallowRef(false);
const symbolKind = shallowRef<SymbolPickerKind>('characters');
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
            closeOnClickModal: true,
            lockScroll: false,
            type: 'warning',
        });
    } catch {
        return;
    }

    setValue('');
}

async function handleTool(tool: EditorTool): Promise<void> {
    if (tool.id === 'image' || tool.id === 'media') {
        imageVisible.value = true;
        return;
    }

    if (tool.id === 'characters' || tool.id === 'emoji') {
        symbolKind.value = tool.id;
        symbolVisible.value = true;
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
        id: 'media-inline',
        label: '媒体',
        action: 'insert',
        snippet,
    });
    imageVisible.value = false;
}

function handleSymbolSubmit(snippet: string): void {
    if (!snippet) {
        return;
    }

    applyTool({
        id: `${symbolKind.value}-picker`,
        label: symbolKind.value === 'emoji' ? '表情包' : '符号',
        action: 'insert',
        snippet,
    });
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
        <EditorSymbolDialog v-model:visible="symbolVisible" :kind="symbolKind" @submit="handleSymbolSubmit" />
        <EditorImageDialog
            v-model:visible="imageVisible"
            :media="props.media"
            :destroy-url="String(props.routes?.media_destroy || '/admin/media/__MEDIA__')"
            :library-url="String(props.routes?.media_library || '/admin/media/library')"
            :upload-url="String(props.routes?.media_upload || '/admin/media/upload')"
            confirm-text="插入媒体"
            title="媒体库"
            @submit="handleImageSubmit"
        />
    </section>
</template>
