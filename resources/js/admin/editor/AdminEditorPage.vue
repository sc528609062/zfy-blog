<script setup lang="ts">
import { ElMessage } from 'element-plus';
import { computed, reactive, shallowRef, watch } from 'vue';
import EditorPreview from './EditorPreview.vue';
import EditorRevisionDrawer from './EditorRevisionDrawer.vue';
import EditorSidebar from './EditorSidebar.vue';
import ContentAccessPanel from './ContentAccessPanel.vue';
import GalleryManager from './GalleryManager.vue';
import MarkdownEditor from './MarkdownEditor.vue';
import RichContentEditor from './RichContentEditor.vue';
import { normalizeToolbar } from './editorTools';
import { useEditorAutosave } from './useEditorAutosave';
import type {
    EditorCategory,
    EditorForm,
    EditorOption,
    EditorSnapshot,
    SavedContent,
} from './types';

type EditorMode = 'edit' | 'split' | 'preview';

const props = defineProps<{
    payload: Record<string, any>;
}>();

const editorPayload = computed(() => props.payload.editor || {});
const routes = computed<Record<string, string>>(() => props.payload.routes || {});
const csrf = computed(() => props.payload.csrf || document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '');
const tools = computed(() => normalizeToolbar(editorPayload.value.toolbar, Boolean(editorPayload.value.can_use_raw_html)));
const contentTypes = computed<EditorOption[]>(() => editorPayload.value.content_types || [{ value: 'post', label: '文章' }]);
const categories = computed<EditorCategory[]>(() => editorPayload.value.categories || []);
const initialContent = editorPayload.value.content || null;
const defaults = editorPayload.value.presentation_defaults || {};
const initialStatus: 'draft' | 'published' = initialContent?.status === 'published' || editorPayload.value.default_status === 'published'
    ? 'published'
    : 'draft';

const form = reactive<EditorForm>({
    title: initialContent?.title || '',
    type: initialContent?.type || editorPayload.value.default_type || 'post',
    status: initialStatus,
    category_id: initialContent?.category_id || null,
    tags: initialContent?.tags || '',
    cover_url: initialContent?.cover_url || '',
    excerpt: initialContent?.excerpt || '',
});

const blockJson = shallowRef<Record<string, any>>({ ...(initialContent?.block_json || {}) });
const markdown = shallowRef(initialContent?.markdown_cache || '');
const publishedAt = shallowRef<string | null>(initialContent?.published_at || null);
const authoringMode = shallowRef<'markdown' | 'blocks' | 'richtext'>(initialContent?.block_json?.mode || (initialContent ? 'markdown' : 'blocks'));
const documentJson = shallowRef<Record<string, any>>(initialContent?.block_json?.document || { type: 'doc', content: markdown.value ? [{ type: 'zfySource', attrs: { source: markdown.value } }] : [{ type: 'paragraph' }] });

async function switchAuthoringMode(mode: 'markdown' | 'blocks' | 'richtext') {
    if (mode === 'markdown' && authoringMode.value !== 'markdown') {
        const nodes = documentJson.value.content || [];
        markdown.value = nodes.length === 1 && nodes[0].type === 'zfySource' ? nodes[0].attrs.source : '```zfy-document\n' + JSON.stringify(documentJson.value, null, 2) + '\n```';
    } else if (authoringMode.value === 'markdown') {
        try {
            const json = await requestJson(String(routes.value.content_preview || '/admin/contents/preview'), 'POST', { markdown: markdown.value, block_json: { mode: 'markdown' } });
            documentJson.value = json.block_json.document;
        } catch (error) { ElMessage.error((error as Error).message); return; }
    }
    authoringMode.value = mode;
}
const markdownTheme = shallowRef(String(initialContent?.block_json?.presentation?.markdown_theme || defaults.markdown_theme || 'juejin'));
const codeTheme = shallowRef(String(initialContent?.block_json?.presentation?.code_theme || defaults.code_theme || 'atom-one-dark'));
const contentId = shallowRef<number | null>(initialContent?.id || null);
const baseUpdatedAt = shallowRef<string | null>(initialContent?.updated_at || null);
const savedContent = shallowRef<SavedContent | null>(initialContent ? {
    id: initialContent.id,
    title: initialContent.title,
    slug: initialContent.slug || '',
    status: initialContent.status,
    type: initialContent.type,
    published_at: initialContent.published_at,
    updated_at: initialContent.updated_at,
    show_url: initialContent.show_url,
} : null);
const saving = shallowRef(false);
const editorMode = shallowRef<EditorMode>('split');
const previewVisible = computed(() => editorMode.value !== 'edit');
const previewLoading = shallowRef(false);
const previewHtml = shallowRef(initialContent?.rendered_html || '');
const fullscreen = shallowRef(false);
const revisionsVisible = shallowRef(false);
let previewTimer: number | undefined;

const {
    draftKey,
    status: saveStatus,
    statusLabel: saveStatusLabel,
    recoverableDraft,
    markSaved,
    restoreRecoverableDraft,
    discardRecoverableDraft,
} = useEditorAutosave({
    contentId,
    baseUpdatedAt,
    csrf,
    routes,
    getSnapshot,
    applySnapshot,
});

watch([markdown, documentJson], () => {
    if (!previewVisible.value) {
        return;
    }

    window.clearTimeout(previewTimer);
    previewTimer = window.setTimeout(() => {
        void refreshPreview();
    }, 500);
});

function currentBlockJson(): Record<string, any> {
    return {
        ...blockJson.value,
        mode: authoringMode.value,
        editor: 'zfy-document',
        version: 3,
        document: documentJson.value,
        presentation: {
            markdown_theme: markdownTheme.value,
            code_theme: codeTheme.value,
        },
    };
}

function getSnapshot(): EditorSnapshot {
    return {
        title: form.title,
        type: form.type,
        status: form.status,
        category_id: form.category_id,
        tags: form.tags,
        cover_url: form.cover_url,
        excerpt: form.excerpt,
        markdown_cache: markdown.value,
        block_json: currentBlockJson(),
    };
}

function contentPayload(status: 'draft' | 'published'): Record<string, any> {
    return {
        ...getSnapshot(),
        status,
        draft_key: draftKey.value,
        published_at: publishedAt.value,
    };
}

function applySnapshot(snapshot: EditorSnapshot): void {
    form.title = String(snapshot.title ?? form.title);
    form.type = String(snapshot.type ?? form.type);
    form.status = snapshot.status === 'published' ? 'published' : 'draft';
    form.category_id = snapshot.category_id == null ? null : Number(snapshot.category_id);
    form.tags = String(snapshot.tags ?? '');
    form.cover_url = String(snapshot.cover_url ?? '');
    form.excerpt = String(snapshot.excerpt ?? '');
    markdown.value = String(snapshot.markdown_cache ?? '');
    blockJson.value = { ...(snapshot.block_json || {}) };
    authoringMode.value = snapshot.block_json?.mode || 'markdown';
    documentJson.value = snapshot.block_json?.document || { type: 'doc', content: [{ type: 'zfySource', attrs: { source: markdown.value } }] };
    markdownTheme.value = String(snapshot.block_json?.presentation?.markdown_theme || defaults.markdown_theme || 'juejin');
    codeTheme.value = String(snapshot.block_json?.presentation?.code_theme || defaults.code_theme || 'atom-one-dark');
}

async function saveContent(status: 'draft' | 'published'): Promise<void> {
    if (!form.title.trim()) {
        ElMessage.error('请填写文章标题');
        return;
    }

    saving.value = true;
    form.status = status;

    try {
        const url = contentId.value
            ? String(routes.value.content_update || '/admin/contents/__CONTENT__').replace('__CONTENT__', String(contentId.value))
            : String(routes.value.content_store || '/admin/contents');
        const method = contentId.value ? 'PATCH' : 'POST';
        const json = await requestJson(url, method, contentPayload(status));

        savedContent.value = json.content;
        contentId.value = json.content.id;
        baseUpdatedAt.value = json.content.updated_at || new Date().toISOString();
        markSaved(baseUpdatedAt.value);
        ElMessage.success(status === 'published' ? '文章已发布' : '草稿已保存');

        if (previewVisible.value) {
            await refreshPreview();
        }
    } catch (error) {
        ElMessage.error(error instanceof Error ? error.message : '保存失败');
    } finally {
        saving.value = false;
    }
}

async function refreshPreview(): Promise<void> {
    if (!previewVisible.value) {
        return;
    }

    previewLoading.value = true;

    try {
        const json = await requestJson(String(routes.value.content_preview || '/admin/contents/preview'), 'POST', {
            markdown: markdown.value,
            block_json: currentBlockJson(),
        });
        previewHtml.value = json.html || '';
    } catch (error) {
        ElMessage.error(error instanceof Error ? error.message : '预览失败');
    } finally {
        previewLoading.value = false;
    }
}

function handlePreview(visible: boolean): void {
    if (visible) {
        void refreshPreview();
    }
}

function toggleFullscreen(): void {
    fullscreen.value = !fullscreen.value;
}

async function savePresentationDefaults(): Promise<void> {
    try {
        const json = await requestJson(
            String(routes.value.editor_presentation_defaults || '/admin/editor/presentation-defaults'),
            'POST',
            {
                markdown_theme: markdownTheme.value,
                code_theme: codeTheme.value,
            },
        );
        ElMessage.success(json.message || '已设为全站默认样式');
    } catch (error) {
        ElMessage.error(error instanceof Error ? error.message : '默认样式保存失败');
    }
}

function handleRevisionRestore(snapshot: EditorSnapshot, content: SavedContent): void {
    applySnapshot(snapshot);
    form.status = content.status === 'published' ? 'published' : 'draft';
    savedContent.value = content;
    baseUpdatedAt.value = content.updated_at || new Date().toISOString();
    markSaved(baseUpdatedAt.value);
    void refreshPreview();
}

async function requestJson(url: string, method: string, data: Record<string, any>): Promise<any> {
    const response = await fetch(url, {
        method,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf.value,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(data),
    });
    const json = await response.json().catch(() => ({}));

    if (!response.ok) {
        const firstError = Object.values(json.errors || {})[0];
        const message = Array.isArray(firstError) ? firstError[0] : json.message;
        throw new Error(message || `请求失败：${response.status}`);
    }

    return json;
}
</script>

<template>
    <section :class="['zfy-editor-page', { 'is-fullscreen': fullscreen }]">
        <div class="zfy-editor-layout">
            <div class="zfy-editor-main">
                <el-card class="zfy-editor-title-card" shadow="never">
                    <el-input
                        v-model="form.title"
                        class="zfy-editor-title-input"
                        maxlength="180"
                        placeholder="请输入文章标题"
                        size="large"
                    />
                </el-card>

                <el-alert
                    v-if="recoverableDraft"
                    class="zfy-editor-recovery"
                    :closable="false"
                    show-icon
                    title="发现未恢复的自动保存"
                    type="warning"
                >
                    <template #default>
                        <span>{{ recoverableDraft.title }} · {{ recoverableDraft.summary || '空白正文' }}</span>
                        <el-button size="small" type="warning" @click="restoreRecoverableDraft">恢复</el-button>
                        <el-button size="small" @click="discardRecoverableDraft">丢弃</el-button>
                    </template>
                </el-alert>

                <el-segmented :model-value="authoringMode" :options="[{ label: '块编辑', value: 'blocks' }, { label: '富文本', value: 'richtext' }, { label: 'Markdown', value: 'markdown' }]" @change="switchAuthoringMode" />
                <RichContentEditor v-if="authoringMode !== 'markdown'" v-model="documentJson" :mode="authoringMode" :blocks="editorPayload.blocks" />
                <MarkdownEditor
                    v-else
                    v-model="markdown"
                    v-model:code-theme="codeTheme"
                    v-model:markdown-theme="markdownTheme"
                    v-model:mode="editorMode"
                    :busy="saving"
                    :can-history="Boolean(contentId)"
                    :can-set-defaults="Boolean(editorPayload.can_manage_theme_defaults)"
                    :fullscreen="fullscreen"
                    :media="editorPayload.media"
                    :routes="routes"
                    :save-status="saveStatus"
                    :save-status-label="saveStatusLabel"
                    :title="form.title"
                    :tools="tools"
                    @fullscreen="toggleFullscreen"
                    @history="revisionsVisible = true"
                    @preview="handlePreview"
                    @presentation-defaults="savePresentationDefaults"
                    @publish="saveContent"
                    @save="saveContent"
                >
                    <template #preview>
                        <EditorPreview
                            :code-theme="codeTheme"
                            :html="previewHtml"
                            :loading="previewLoading"
                            :markdown-theme="markdownTheme"
                            :visible="previewVisible"
                        />
                    </template>
                </MarkdownEditor>
                <ContentAccessPanel v-if="contentId" :key="contentId" :content-id="contentId" :content="initialContent" :csrf="csrf" :can-manage-commerce="Boolean(editorPayload.can_manage_commerce)" />
                <GalleryManager v-if="contentId" :key="'gallery-' + contentId" :content-id="contentId" :csrf="csrf" @cover="form.cover_url = $event" />
            </div>

            <EditorSidebar
                v-model="form"
                v-model:published-at="publishedAt"
                :busy="saving"
                :categories="categories"
                :content-types="contentTypes"
                :media="editorPayload.media"
                :preview-loading="previewLoading"
                :routes="routes"
                :saved-content="savedContent"
                @preview="refreshPreview"
                @publish="saveContent('published')"
                @save="saveContent('draft')"
            />
        </div>

        <EditorRevisionDrawer
            v-model:visible="revisionsVisible"
            :content-id="contentId"
            :csrf="csrf"
            :routes="routes"
            @restore="handleRevisionRestore"
        />
    </section>
</template>
