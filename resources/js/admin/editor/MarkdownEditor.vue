<script setup lang="ts">
import { Clock, DocumentAdd, Search, Setting, Tickets } from '@element-plus/icons-vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { computed, nextTick, onBeforeUnmount, onMounted, shallowRef, useTemplateRef, watch } from 'vue';
import EditorImageDialog from './EditorImageDialog.vue';
import EditorInsertDialog from './EditorInsertDialog.vue';
import EditorOutline from './EditorOutline.vue';
import EditorSymbolDialog from './EditorSymbolDialog.vue';
import EditorToolbar from './EditorToolbar.vue';
import { getEditorPromptSpec, toolFromPrompt } from './editorToolPrompts';
import { codeThemeOptions, markdownThemeOptions } from '../../shared/markdownThemeOptions';
import { useMediaLibrary } from './useMediaLibrary';
import type { SymbolPickerKind } from './symbolPresets';
import { useEditorOutline, type EditorOutlineItem } from './useEditorOutline';
import { useMarkdownEditor } from './useMarkdownEditor';
import type {
    EditorMediaConfig,
    EditorPromptSpec,
    EditorSaveStatus,
    EditorTool,
} from './types';

type EditorMode = 'edit' | 'split' | 'preview';

const props = defineProps<{
    tools: EditorTool[];
    busy?: boolean;
    fullscreen?: boolean;
    routes?: Record<string, string>;
    media?: EditorMediaConfig;
    title?: string;
    canHistory?: boolean;
    canSetDefaults?: boolean;
    saveStatus?: EditorSaveStatus;
    saveStatusLabel?: string;
}>();

const emit = defineEmits<{
    save: [status: 'draft'];
    publish: [status: 'published'];
    preview: [visible: boolean];
    fullscreen: [];
    history: [];
    presentationDefaults: [];
}>();

const model = defineModel<string>({ required: true });
const mode = defineModel<EditorMode>('mode', { default: 'split' });
const markdownTheme = defineModel<string>('markdownTheme', { default: 'juejin' });
const codeTheme = defineModel<string>('codeTheme', { default: 'atom-one-dark' });
const editorRootRef = useTemplateRef<HTMLElement>('editorRoot');
const importInputRef = useTemplateRef<HTMLInputElement>('importInput');
const syncScroll = shallowRef(true);
const outlineVisible = shallowRef(!window.matchMedia('(max-width: 900px)').matches);
const promptVisible = shallowRef(false);
const imageVisible = shallowRef(false);
const symbolVisible = shallowRef(false);
const symbolKind = shallowRef<SymbolPickerKind>('characters');
const promptSpec = shallowRef<EditorPromptSpec | null>(null);
const promptTool = shallowRef<EditorTool | null>(null);
const modeOptions = [
    { label: '编辑', value: 'edit' },
    { label: '分屏', value: 'split' },
    { label: '预览', value: 'preview' },
];
const libraryUrl = computed(() => String(props.routes?.media_library || '/admin/media/library'));
const uploadUrl = computed(() => String(props.routes?.media_upload || '/admin/media/upload'));
const destroyUrl = computed(() => String(props.routes?.media_destroy || '/admin/media/__MEDIA__'));
const { uploadMedia } = useMediaLibrary(libraryUrl, uploadUrl, destroyUrl);
const {
    cursorLine,
    cursorColumn,
    applyTool,
    setValue,
    showSearch,
    scrollToLine,
    scrollToRatio,
    onScroll,
} = useMarkdownEditor(model, {
    onSave: () => emit('save', 'draft'),
    onFullscreen: () => emit('fullscreen'),
    uploadImage,
});
const { outline } = useEditorOutline(model);
const previewVisible = computed(() => mode.value !== 'edit');
const editorVisible = computed(() => mode.value !== 'preview');
const characterCount = computed(() => model.value.length);
const lineCount = computed(() => (model.value ? model.value.split(/\r\n?|\n/).length : 1));
const wordCount = computed(() => {
    const text = model.value.replace(/[`*_#[\]()>{}|~-]/g, ' ');
    const chinese = text.match(/[\u3400-\u9fff]/g)?.length || 0;
    const words = text.match(/[A-Za-z0-9]+(?:['-][A-Za-z0-9]+)*/g)?.length || 0;

    return chinese + words;
});
let syncingScroll = false;
let removeEditorScroll: (() => void) | undefined;
let removePreviewScroll: (() => void) | undefined;

onMounted(() => {
    removeEditorScroll = onScroll(handleEditorScroll);
    void bindPreviewScroll();
});

onBeforeUnmount(() => {
    removeEditorScroll?.();
    removePreviewScroll?.();
});

watch([previewVisible, () => props.fullscreen], () => void bindPreviewScroll());
watch(mode, (value) => emit('preview', value !== 'edit'));

function downloadMarkdown(): void {
    const blob = new Blob([model.value || ''], { type: 'text/markdown;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const anchor = document.createElement('a');

    anchor.href = url;
    anchor.download = `${sanitizeFilename(props.title || 'zfy-post')}.md`;
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
        mode.value = mode.value === 'edit' ? 'split' : 'edit';
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

function openImport(): void {
    importInputRef.value?.click();
}

async function importMarkdown(event: Event): Promise<void> {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    input.value = '';

    if (!file) {
        return;
    }

    try {
        setValue(await file.text());
        ElMessage.success('Markdown 已导入');
    } catch {
        ElMessage.error('Markdown 文件读取失败');
    }
}

async function uploadImage(file: File): Promise<string> {
    try {
        const directory = String(props.media?.defaultDirectory || 'editor/images');
        const item = await uploadMedia(file, directory);
        ElMessage.success(`${file.name} 已上传`);

        return `![${escapeMarkdownText(file.name.replace(/\.[^.]+$/, ''))}](${item.url})`;
    } catch (error) {
        ElMessage.error(error instanceof Error ? error.message : `${file.name} 上传失败`);
        throw error;
    }
}

async function bindPreviewScroll(): Promise<void> {
    removePreviewScroll?.();
    removePreviewScroll = undefined;
    await nextTick();
    const pane = previewPane();

    if (!pane) {
        return;
    }

    const handler = () => {
        if (!syncScroll.value || syncingScroll) {
            return;
        }

        const max = Math.max(0, pane.scrollHeight - pane.clientHeight);
        syncingScroll = true;
        scrollToRatio(max > 0 ? pane.scrollTop / max : 0);
        requestAnimationFrame(() => {
            syncingScroll = false;
        });
    };

    pane.addEventListener('scroll', handler, { passive: true });
    removePreviewScroll = () => pane.removeEventListener('scroll', handler);
}

function handleEditorScroll(ratio: number): void {
    if (!syncScroll.value || syncingScroll) {
        return;
    }

    const pane = previewPane();
    if (!pane) {
        return;
    }

    syncingScroll = true;
    pane.scrollTop = ratio * Math.max(0, pane.scrollHeight - pane.clientHeight);
    requestAnimationFrame(() => {
        syncingScroll = false;
    });
}

function handleOutlineSelect(item: EditorOutlineItem): void {
    scrollToLine(item.line);
    const headings = previewPane()?.querySelectorAll<HTMLElement>('h1, h2, h3, h4, h5, h6');
    headings?.[item.index]?.scrollIntoView({ block: 'start', behavior: 'smooth' });
}

function previewPane(): HTMLElement | null {
    return editorRootRef.value?.querySelector<HTMLElement>('.zfy-editor-preview-pane') || null;
}

function sanitizeFilename(value: string): string {
    return value.replace(/[\\/:*?"<>|]/g, '-').trim() || 'zfy-post';
}

function escapeMarkdownText(value: string): string {
    return value.replace(/[\[\]\\]/g, '\\$&');
}
</script>

<template>
    <section ref="editorRoot" :class="['zfy-markdown-editor', `is-mode-${mode}`, { 'is-fullscreen': fullscreen }]">
        <EditorToolbar
            :busy="busy"
            :fullscreen="fullscreen"
            :preview-visible="previewVisible"
            :tools="tools"
            @tool="handleTool"
        />

        <div class="zfy-editor-controlbar">
            <el-segmented v-model="mode" :options="modeOptions" size="small" />
            <el-divider direction="vertical" />
            <el-tooltip content="查找和替换" placement="bottom">
                <el-button aria-label="查找和替换" circle :icon="Search" size="small" @click="showSearch" />
            </el-tooltip>
            <el-tooltip content="导入 Markdown" placement="bottom">
                <el-button aria-label="导入 Markdown" circle :icon="DocumentAdd" size="small" @click="openImport" />
            </el-tooltip>
            <el-tooltip content="历史版本" placement="bottom">
                <el-button aria-label="历史版本" circle :disabled="!canHistory" :icon="Clock" size="small" @click="emit('history')" />
            </el-tooltip>
            <input ref="importInput" accept=".md,.markdown,text/markdown,text/plain" type="file" @change="importMarkdown">

            <div class="zfy-editor-controlbar__themes">
                <el-select v-model="markdownTheme" filterable size="small" aria-label="正文主题">
                    <el-option v-for="theme in markdownThemeOptions" :key="theme.value" :label="theme.label" :value="theme.value" />
                </el-select>
                <el-select v-model="codeTheme" filterable size="small" aria-label="代码主题">
                    <el-option v-for="theme in codeThemeOptions" :key="theme.value" :label="theme.label" :value="theme.value" />
                </el-select>
                <el-tooltip v-if="canSetDefaults" content="设为全站默认" placement="bottom">
                    <el-button aria-label="设为全站默认" circle :icon="Setting" size="small" @click="emit('presentationDefaults')" />
                </el-tooltip>
            </div>

            <el-tooltip content="同步滚动" placement="bottom">
                <el-switch v-model="syncScroll" inline-prompt active-text="联" inactive-text="离" />
            </el-tooltip>
            <el-tooltip content="文章目录" placement="bottom">
                <el-button aria-label="文章目录" :class="{ 'is-active': outlineVisible }" circle :icon="Tickets" size="small" @click="outlineVisible = !outlineVisible" />
            </el-tooltip>
        </div>

        <div
            :class="[
                'zfy-markdown-editor-body',
                `is-mode-${mode}`,
                { 'has-preview': previewVisible, 'has-outline': outlineVisible },
            ]"
        >
            <div v-show="editorVisible" ref="editorHost" class="zfy-markdown-editor-host" />
            <slot name="preview" />
            <EditorOutline v-if="outlineVisible" :items="outline" @select="handleOutlineSelect" />
        </div>

        <footer class="zfy-editor-statusbar">
            <span :class="['zfy-editor-save-state', `is-${saveStatus || 'saved'}`]">{{ saveStatusLabel || '已保存' }}</span>
            <span>{{ wordCount }} 字</span>
            <span>{{ characterCount }} 字符</span>
            <span>{{ lineCount }} 行</span>
            <span>行 {{ cursorLine }}，列 {{ cursorColumn }}</span>
        </footer>

        <EditorInsertDialog
            v-model:visible="promptVisible"
            :media="props.media"
            :routes="props.routes"
            :spec="promptSpec"
            @submit="handlePromptSubmit"
        />
        <EditorSymbolDialog v-model:visible="symbolVisible" :kind="symbolKind" @submit="handleSymbolSubmit" />
        <EditorImageDialog
            v-model:visible="imageVisible"
            :media="props.media"
            :destroy-url="destroyUrl"
            :library-url="libraryUrl"
            :upload-url="uploadUrl"
            confirm-text="插入媒体"
            title="媒体库"
            @submit="handleImageSubmit"
        />
    </section>
</template>
