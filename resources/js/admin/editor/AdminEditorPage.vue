<script setup lang="ts">
import { ElMessage } from 'element-plus';
import { computed, reactive, shallowRef, watch } from 'vue';
import EditorPreview from './EditorPreview.vue';
import EditorSidebar from './EditorSidebar.vue';
import MarkdownEditor from './MarkdownEditor.vue';
import { normalizeToolbar } from './editorTools';
import type { EditorCategory, EditorForm, EditorOption, SavedContent } from './types';

const props = defineProps<{
    payload: Record<string, any>;
}>();

const editorPayload = computed(() => props.payload.editor || {});
const routes = computed(() => props.payload.routes || {});
const csrf = computed(() => props.payload.csrf || document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '');
const tools = computed(() => normalizeToolbar(editorPayload.value.toolbar, Boolean(editorPayload.value.can_use_raw_html)));
const contentTypes = computed<EditorOption[]>(() => editorPayload.value.content_types || [{ value: 'post', label: '文章' }]);
const categories = computed<EditorCategory[]>(() => editorPayload.value.categories || []);
const initialContent = editorPayload.value.content || null;
const initialStatus: 'draft' | 'published' = initialContent?.status === 'published' || editorPayload.value.default_status === 'published'
    ? 'published'
    : 'draft';

const form = reactive<EditorForm>({
    title: initialContent?.title || '',
    type: initialContent?.type || 'post',
    status: initialStatus,
    category_id: initialContent?.category_id || null,
    tags: initialContent?.tags || '',
    cover_url: initialContent?.cover_url || '',
    excerpt: initialContent?.excerpt || '',
});

const markdown = shallowRef(initialContent?.markdown_cache || '');
const contentId = shallowRef<number | null>(initialContent?.id || null);
const savedContent = shallowRef<SavedContent | null>(initialContent ? {
    id: initialContent.id,
    title: initialContent.title,
    slug: initialContent.slug || '',
    status: initialContent.status,
    type: initialContent.type,
    published_at: initialContent.published_at,
    show_url: initialContent.show_url,
} : null);
const saving = shallowRef(false);
const previewVisible = shallowRef(true);
const previewLoading = shallowRef(false);
const previewHtml = shallowRef(initialContent?.rendered_html || '');
const fullscreen = shallowRef(false);
let previewTimer: number | undefined;

watch(markdown, () => {
    if (!previewVisible.value) {
        return;
    }

    window.clearTimeout(previewTimer);
    previewTimer = window.setTimeout(() => {
        void refreshPreview();
    }, 500);
});

function contentPayload(status: 'draft' | 'published') {
    return {
        title: form.title,
        type: form.type,
        status,
        category_id: form.category_id,
        tags: form.tags,
        cover_url: form.cover_url,
        excerpt: form.excerpt,
        markdown_cache: markdown.value,
        block_json: {
            mode: 'markdown',
            editor: 'zfy-markdown',
            version: 1,
        },
    };
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
    previewVisible.value = true;
    previewLoading.value = true;

    try {
        const json = await requestJson(String(routes.value.content_preview || '/admin/contents/preview'), 'POST', {
            markdown: markdown.value,
        });
        previewHtml.value = json.html || '';
    } catch (error) {
        ElMessage.error(error instanceof Error ? error.message : '预览失败');
    } finally {
        previewLoading.value = false;
    }
}

function togglePreview(): void {
    if (previewVisible.value) {
        previewVisible.value = false;
        return;
    }

    void refreshPreview();
}

function toggleFullscreen(): void {
    fullscreen.value = !fullscreen.value;
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

                <MarkdownEditor
                    v-model="markdown"
                    :busy="saving"
                    :fullscreen="fullscreen"
                    :media="editorPayload.media"
                    :preview-visible="previewVisible"
                    :routes="routes"
                    :tools="tools"
                    @fullscreen="toggleFullscreen"
                    @preview="togglePreview"
                    @publish="saveContent"
                    @save="saveContent"
                >
                    <template #preview>
                        <EditorPreview :html="previewHtml" :loading="previewLoading" :visible="previewVisible" />
                    </template>
                </MarkdownEditor>
            </div>

            <EditorSidebar
                v-model="form"
                :busy="saving"
                :categories="categories"
                :content-types="contentTypes"
                :preview-loading="previewLoading"
                :saved-content="savedContent"
                @preview="refreshPreview"
                @publish="saveContent('published')"
                @save="saveContent('draft')"
            />
        </div>
    </section>
</template>
