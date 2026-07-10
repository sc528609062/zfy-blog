<script setup lang="ts">
import { ElMessage, ElMessageBox } from 'element-plus';
import { shallowRef, watch } from 'vue';
import type { EditorRevision, EditorSnapshot, SavedContent } from './types';

const props = defineProps<{
    visible: boolean;
    contentId: number | null;
    routes: Record<string, string>;
    csrf: string;
}>();

const emit = defineEmits<{
    'update:visible': [visible: boolean];
    restore: [snapshot: EditorSnapshot, content: SavedContent];
}>();

const revisions = shallowRef<EditorRevision[]>([]);
const selected = shallowRef<EditorRevision | null>(null);
const loading = shallowRef(false);
const restoring = shallowRef(false);

watch(
    () => props.visible,
    (visible) => {
        if (visible && props.contentId) {
            void loadRevisions();
        }
    },
);

async function loadRevisions(): Promise<void> {
    if (!props.contentId) {
        return;
    }

    loading.value = true;
    selected.value = null;

    try {
        const url = routeUrl('content_revisions');
        const json = await requestJson(url, 'GET');
        revisions.value = Array.isArray(json.revisions) ? json.revisions : [];
    } catch (error) {
        ElMessage.error(error instanceof Error ? error.message : '历史版本加载失败');
    } finally {
        loading.value = false;
    }
}

async function inspectRevision(revision: EditorRevision): Promise<void> {
    try {
        const json = await requestJson(routeUrl('content_revision', revision.id), 'GET');
        selected.value = json.revision || revision;
    } catch (error) {
        ElMessage.error(error instanceof Error ? error.message : '版本详情加载失败');
    }
}

async function restoreRevision(): Promise<void> {
    if (!selected.value?.snapshot) {
        return;
    }

    try {
        await ElMessageBox.confirm('恢复后将替换当前编辑内容，当前版本会先自动存入历史记录。', '恢复历史版本', {
            confirmButtonText: '恢复',
            cancelButtonText: '取消',
            closeOnClickModal: true,
            lockScroll: false,
            type: 'warning',
        });
    } catch {
        return;
    }

    restoring.value = true;

    try {
        const json = await requestJson(routeUrl('content_revision_restore', selected.value.id), 'POST');
        emit('restore', selected.value.snapshot, json.content);
        emit('update:visible', false);
        ElMessage.success('历史版本已恢复');
    } catch (error) {
        ElMessage.error(error instanceof Error ? error.message : '恢复失败');
    } finally {
        restoring.value = false;
    }
}

function routeUrl(key: string, revisionId?: number): string {
    return String(props.routes[key] || '')
        .replace('__CONTENT__', String(props.contentId || ''))
        .replace('__REVISION__', String(revisionId || ''));
}

async function requestJson(url: string, method: string): Promise<any> {
    const response = await fetch(url, {
        method,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': props.csrf,
            'X-Requested-With': 'XMLHttpRequest',
        },
    });
    const json = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new Error(json.message || `请求失败：${response.status}`);
    }

    return json;
}

function formattedDate(value?: string | null): string {
    if (!value) {
        return '未知时间';
    }

    return new Date(value).toLocaleString('zh-CN', { hour12: false });
}
</script>

<template>
    <el-drawer
        :model-value="visible"
        class="zfy-editor-revision-drawer"
        direction="rtl"
        size="min(760px, 92vw)"
        title="历史版本"
        :lock-scroll="false"
        @update:model-value="emit('update:visible', $event)"
    >
        <div v-loading="loading" class="zfy-editor-revisions">
            <div class="zfy-editor-revision-list">
                <button
                    v-for="revision in revisions"
                    :key="revision.id"
                    :class="{ 'is-active': selected?.id === revision.id }"
                    type="button"
                    @click="inspectRevision(revision)"
                >
                    <strong>{{ revision.title || '未命名版本' }}</strong>
                    <span>{{ formattedDate(revision.created_at) }}</span>
                    <small>{{ revision.summary || '无正文摘要' }}</small>
                </button>
                <el-empty v-if="!loading && revisions.length === 0" description="暂无历史版本" :image-size="72" />
            </div>

            <div class="zfy-editor-revision-preview">
                <template v-if="selected?.snapshot">
                    <header>
                        <div>
                            <strong>{{ selected.snapshot.title || selected.title }}</strong>
                            <span>{{ formattedDate(selected.created_at) }}</span>
                        </div>
                        <el-button :loading="restoring" type="primary" @click="restoreRevision">恢复此版本</el-button>
                    </header>
                    <pre>{{ selected.snapshot.markdown_cache || '空白正文' }}</pre>
                </template>
                <el-empty v-else description="选择一个版本查看内容" :image-size="88" />
            </div>
        </div>
    </el-drawer>
</template>
