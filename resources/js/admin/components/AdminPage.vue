<script setup lang="ts">
import { computed, reactive, shallowRef, watch } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import AdminEditorPage from '../editor/AdminEditorPage.vue';
import CoverImageField from '../editor/CoverImageField.vue';
import AdminDataTable from './AdminDataTable.vue';
import SettingsForm from './SettingsForm.vue';
import BitsGradientText from './bits/BitsGradientText.vue';
import BitsMetricCard from './bits/BitsMetricCard.vue';
import BitsSpotlightCard from './bits/BitsSpotlightCard.vue';
import type { AdminPageDefinition } from '../useAdminMenu';

const props = defineProps<{
    section: string;
    title: string;
    description: string;
    payload: Record<string, any>;
    currentPage?: AdminPageDefinition;
}>();

const emit = defineEmits<{
    navigate: [target: string];
}>();

type AdminRow = Record<string, any>;

const stats = computed(() => props.payload.stats || {});
const themes = computed(() => props.payload.themes || []);
const plugins = computed(() => props.payload.plugins || []);
const layouts = computed(() => props.payload.layouts || []);
const pageKind = computed(() => props.currentPage?.kind || 'placeholder');
const settingsSchema = computed(() => props.payload.settings_schema || []);
const editorPayload = computed(() => props.payload.editor || {});
const contentTypes = computed(() => editorPayload.value.content_types || []);
const categories = computed(() => editorPayload.value.categories || []);
const localContents = shallowRef<AdminRow[]>([]);
const localOrders = shallowRef<AdminRow[]>([]);
const localTableRows = shallowRef<AdminRow[]>([]);
const quickSettingsVisible = shallowRef(false);
const quickSettingsSaving = shallowRef(false);
const quickSettingsContentId = shallowRef<number | string | null>(null);
const quickSettingsForm = reactive({
    title: '',
    type: 'post',
    status: 'draft',
    category_id: null as number | null,
    topic: '',
    tags: '',
    cover_url: '',
    excerpt: '',
});

const contents = computed(() => localContents.value);
const orders = computed(() => localOrders.value);
const tableRows = computed(() => localTableRows.value);

watch(
    [() => props.payload, () => props.section],
    ([payload, section]) => {
        const payloadContents = cloneRows(payload.contents);
        const payloadOrders = cloneRows(payload.orders);

        localContents.value = payloadContents;
        localOrders.value = payloadOrders;
        localTableRows.value = cloneRows(payload.data_rows || (section === 'orders' ? payload.orders : payload.contents));
    },
    { immediate: true },
);

function cloneRows(rows: unknown): AdminRow[] {
    return Array.isArray(rows) ? rows.map((row) => ({ ...(row as AdminRow) })) : [];
}

function sameRowId(row: AdminRow, id: number | string): boolean {
    return String(row.id || '') === String(id);
}

function replaceRow(rows: typeof localContents, id: number | string, patch: AdminRow) {
    let changed = false;

    const nextRows = rows.value.map((row) => {
        if (!sameRowId(row, id)) {
            return row;
        }

        changed = true;

        return {
            ...row,
            ...patch,
        };
    });

    if (changed) {
        rows.value = nextRows;
    }
}

function updateContentRow(id: number | string, patch: AdminRow) {
    replaceRow(localContents, id, patch);
    replaceRow(localTableRows, id, patch);
}

function removeRow(rows: typeof localContents, id: number | string) {
    rows.value = rows.value.filter((row) => !sameRowId(row, id));
}

function removeContentRow(id: number | string) {
    removeRow(localContents, id);
    removeRow(localTableRows, id);
}

function statusPatch(status: string): AdminRow {
    const labels: Record<string, string> = {
        published: '已发布',
        draft: '草稿',
        pending: '待审核',
        archived: '已归档',
    };
    const tagTypes: Record<string, string> = {
        published: 'success',
        draft: 'warning',
        pending: 'warning',
        archived: 'info',
    };

    return {
        status,
        status_label: labels[status] || status || '未知',
        status_tag_type: tagTypes[status] || 'info',
    };
}

function contentTypeLabel(type: string): string {
    const matchedType = contentTypes.value.find((item: Record<string, any>) => String(item.value) === type);
    const labels: Record<string, string> = {
        post: '文章',
        images: '图集',
        files: '资源',
        page: '页面',
    };

    return matchedType?.label || labels[type] || type || '内容';
}

function categoryName(categoryId: number | null): string {
    if (!categoryId) {
        return '未分类';
    }

    const matchedCategory = categories.value.find((category: Record<string, any>) => Number(category.id) === Number(categoryId));

    return matchedCategory?.name || '未分类';
}

function formatAdminDate(value: unknown): string | null {
    if (!value) {
        return null;
    }

    const date = new Date(String(value));

    if (Number.isNaN(date.getTime())) {
        return String(value).replace('T', ' ').slice(0, 16);
    }

    const pad = (part: number) => String(part).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

function truncateText(value: string, maxLength = 96): string {
    return value.length > maxLength ? `${value.slice(0, maxLength)}...` : value;
}

function tagsFromInput(value: string): Array<{ name: string }> {
    return value
        .split(/[,，\n]/)
        .map((name) => name.trim())
        .filter(Boolean)
        .map((name) => ({ name }));
}

function submitPost(url: string, data: Record<string, string | number | boolean | null> = {}) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = url;

    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value = props.payload.csrf || '';
    form.appendChild(csrf);

    Object.entries(data).forEach(([name, value]) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value == null ? '' : String(value);
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
}

function copyPlaceholder() {
    ElMessage.success('页面入口已保留，可继续接入真实业务');
}

function goAdmin(section: string) {
    emit('navigate', section);
}

function contentRoute(routeKey: string, row: Record<string, any>): string {
    return String(props.payload.routes?.[routeKey] || '').replace('__CONTENT__', String(row.id || ''));
}

function editRow(row: Record<string, any>) {
    if (row.edit_url) {
        emit('navigate', String(row.edit_url));
        return;
    }

    if (props.section === 'contents' && row.id) {
        emit('navigate', `/admin/editor?content=${row.id}`);
        return;
    }

    ElMessage.info('这个条目的编辑页面还没有接入');
}

function settingsRow(row: Record<string, any>) {
    if (!row.id) {
        ElMessage.info('这个条目的设置页面还没有接入');
        return;
    }

    quickSettingsContentId.value = row.id;
    quickSettingsForm.title = String(row.title || '');
    quickSettingsForm.type = String(row.type || 'post');
    quickSettingsForm.status = String(row.status || 'draft');
    quickSettingsForm.category_id = row.category_id ? Number(row.category_id) : null;
    quickSettingsForm.topic = row.topic_name && row.topic_name !== '未归入专题' ? String(row.topic_name) : '';
    quickSettingsForm.tags = String(row.tags_text || (Array.isArray(row.tags) ? row.tags.map((tag: any) => tag.name || tag).join(', ') : ''));
    quickSettingsForm.cover_url = String(row.cover_url_raw || '');
    quickSettingsForm.excerpt = String(row.excerpt_raw || '');
    quickSettingsVisible.value = true;
}

async function requestJson(url: string, method: string, body?: Record<string, unknown>) {
    const response = await fetch(url, {
        method,
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': props.payload.csrf || '',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: body ? JSON.stringify(body) : undefined,
    });

    const json = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new Error(json.message || `请求失败：${response.status}`);
    }

    return json;
}

async function toggleContentStatus(row: Record<string, any>) {
    if (!row.id) {
        return;
    }

    const nextStatus = row.status === 'published' ? 'draft' : 'published';
    const actionLabel = nextStatus === 'published' ? '发布' : '设为草稿';

    try {
        await ElMessageBox.confirm(`确定要将「${row.title || '这篇文章'}」${actionLabel}吗？`, '确认操作', {
            type: nextStatus === 'published' ? 'success' : 'warning',
            confirmButtonText: actionLabel,
            cancelButtonText: '取消',
            closeOnClickModal: true,
            lockScroll: false,
        });

        const json = await requestJson(contentRoute('content_status', row), 'PATCH', { status: nextStatus });
        const updatedContent = json.content || {};
        const updatedStatus = String(updatedContent.status || nextStatus);

        updateContentRow(row.id, {
            ...statusPatch(updatedStatus),
            published_at: updatedStatus === 'published' ? formatAdminDate(updatedContent.published_at || new Date().toISOString()) : null,
        });
        ElMessage.success(nextStatus === 'published' ? '文章已发布' : '文章已设为草稿');
    } catch (error) {
        if (error === 'cancel' || error === 'close') {
            return;
        }

        ElMessage.error(error instanceof Error ? error.message : '操作失败');
    }
}

async function deleteContent(row: Record<string, any>) {
    if (!row.id) {
        return;
    }

    try {
        await ElMessageBox.confirm(`确定删除「${row.title || '这篇文章'}」吗？删除后可由后台数据恢复流程处理。`, '删除文章', {
            type: 'warning',
            confirmButtonText: '删除',
            cancelButtonText: '取消',
            closeOnClickModal: true,
            confirmButtonClass: 'el-button--danger',
            lockScroll: false,
        });

        await requestJson(contentRoute('content_destroy', row), 'DELETE');
        removeContentRow(row.id);
        ElMessage.success('文章已删除');
    } catch (error) {
        if (error === 'cancel' || error === 'close') {
            return;
        }

        ElMessage.error(error instanceof Error ? error.message : '删除失败');
    }
}

async function saveQuickSettings() {
    if (!quickSettingsContentId.value) {
        return;
    }

    const contentId = quickSettingsContentId.value;

    if (!quickSettingsForm.title.trim()) {
        ElMessage.error('请填写文章标题');
        return;
    }

    quickSettingsSaving.value = true;

    try {
        const url = String(props.payload.routes?.content_settings || '/admin/contents/__CONTENT__/settings')
            .replace('__CONTENT__', String(contentId));

        const json = await requestJson(url, 'PATCH', {
            title: quickSettingsForm.title,
            type: quickSettingsForm.type,
            status: quickSettingsForm.status,
            category_id: quickSettingsForm.category_id,
            topic: quickSettingsForm.topic,
            tags: quickSettingsForm.tags,
            cover_url: quickSettingsForm.cover_url,
            excerpt: quickSettingsForm.excerpt,
        });
        const updatedContent = json.content || {};
        const updatedStatus = String(updatedContent.status || quickSettingsForm.status);
        const rawCover = quickSettingsForm.cover_url.trim();
        const rawExcerpt = quickSettingsForm.excerpt.trim();
        const tags = tagsFromInput(quickSettingsForm.tags);

        updateContentRow(contentId, {
            title: String(updatedContent.title || quickSettingsForm.title),
            type: String(updatedContent.type || quickSettingsForm.type),
            type_label: contentTypeLabel(String(updatedContent.type || quickSettingsForm.type)),
            ...statusPatch(updatedStatus),
            category_id: quickSettingsForm.category_id,
            category_name: categoryName(quickSettingsForm.category_id),
            topic_name: quickSettingsForm.topic.trim() || '未归入专题',
            tags,
            tags_text: tags.map((tag) => tag.name).join(', '),
            cover_url: rawCover || '/assets/zfy/placeholders/cover-blue.svg',
            cover_url_raw: rawCover,
            excerpt: rawExcerpt ? truncateText(rawExcerpt) : '暂无摘要',
            excerpt_raw: rawExcerpt,
            published_at: updatedStatus === 'published' ? formatAdminDate(updatedContent.published_at || new Date().toISOString()) : null,
        });

        ElMessage.success('快捷设置已保存');
        quickSettingsVisible.value = false;
    } catch (error) {
        ElMessage.error(error instanceof Error ? error.message : '保存失败');
    } finally {
        quickSettingsSaving.value = false;
    }
}
</script>

<template>
    <section class="zfy-admin-page">
        <template v-if="pageKind === 'dashboard'">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <BitsMetricCard label="文章总数" :value="stats.contents || 0" trend="内容资产" tone="blue" />
                <BitsMetricCard label="订单总数" :value="stats.orders || 0" trend="商城交易" tone="green" />
                <BitsMetricCard label="商品总数" :value="stats.products || 0" trend="核心商城" tone="amber" />
                <BitsMetricCard label="链接总数" :value="stats.links || 0" trend="增强友链" tone="rose" />
            </div>

            <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
                <BitsSpotlightCard class="xl:col-span-2">
                    <div class="zfy-card-title">
                        <div>
                            <h2>运营概览</h2>
                            <p>清爽后台视图，独立于前台主题。</p>
                        </div>
                        <el-tag type="success">运行中</el-tag>
                    </div>
                    <div class="zfy-flow-line" />
                </BitsSpotlightCard>

                <BitsSpotlightCard tone="green">
                    <div class="zfy-card-title">
                        <div>
                            <h2>快捷操作</h2>
                            <p>常用后台入口</p>
                        </div>
                    </div>
                    <div class="zfy-quick-grid">
                        <el-button @click="goAdmin('editor')">写文章</el-button>
                        <el-button @click="goAdmin('media')">媒体库</el-button>
                        <el-button @click="goAdmin('themes')">主题</el-button>
                        <el-button @click="goAdmin('products')">商品</el-button>
                    </div>
                </BitsSpotlightCard>
            </div>

            <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                <el-card shadow="never">
                    <template #header>最新文章</template>
                    <AdminDataTable
                        :rows="contents"
                        variant="content"
                        @edit="editRow"
                        @settings="settingsRow"
                        @toggle-status="toggleContentStatus"
                        @delete="deleteContent"
                    />
                </el-card>
                <el-card shadow="never">
                    <template #header>最新订单</template>
                    <AdminDataTable :rows="orders" @edit="editRow" />
                </el-card>
            </div>
        </template>

        <template v-else-if="pageKind === 'table'">
            <el-card shadow="never">
                <template #header>
                    <div class="zfy-card-title">
                        <div>
                            <h2>{{ title }}</h2>
                            <p>{{ description }}</p>
                        </div>
                        <el-space wrap>
                            <el-button>批量操作</el-button>
                            <el-button>导出</el-button>
                            <el-button type="primary">新建</el-button>
                        </el-space>
                    </div>
                </template>
                <AdminDataTable
                    :rows="tableRows"
                    :variant="section === 'contents' ? 'content' : 'default'"
                    @edit="editRow"
                    @settings="settingsRow"
                    @toggle-status="toggleContentStatus"
                    @delete="deleteContent"
                />
            </el-card>
        </template>

        <template v-else-if="pageKind === 'editor'">
            <AdminEditorPage :payload="payload" />
        </template>

        <template v-else-if="pageKind === 'themes'">
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2 xl:grid-cols-3">
                <el-card v-for="theme in themes" :key="theme.slug" shadow="never">
                    <div class="zfy-theme-preview">
                        <span>{{ theme.name }}</span>
                    </div>
                    <div class="zfy-card-title mt-4">
                        <div>
                            <h2>{{ theme.name }}</h2>
                            <p>{{ theme.slug }} · v{{ theme.version }}</p>
                        </div>
                        <el-tag :type="theme.is_active ? 'success' : 'info'">{{ theme.is_active ? '当前启用' : '可切换' }}</el-tag>
                    </div>
                    <el-button
                        class="mt-4 w-full"
                        :disabled="theme.is_active"
                        type="primary"
                        @click="submitPost(payload.routes.theme_activate, { slug: theme.slug })"
                    >
                        {{ theme.is_active ? '已启用' : '启用此主题' }}
                    </el-button>
                </el-card>
            </div>
        </template>

        <template v-else-if="pageKind === 'plugins'">
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2 xl:grid-cols-3">
                <el-card v-for="plugin in plugins" :key="plugin.slug" shadow="never">
                    <div class="zfy-card-title">
                        <div>
                            <h2>{{ plugin.name }}</h2>
                            <p>{{ plugin.slug }} · v{{ plugin.version }}</p>
                        </div>
                        <el-switch :model-value="plugin.enabled" @change="submitPost(plugin.toggle_url)" />
                    </div>
                    <el-divider />
                    <el-tag v-for="permission in plugin.permissions" :key="permission" class="mr-2 mb-2">{{ permission }}</el-tag>
                </el-card>
            </div>
        </template>

        <template v-else-if="pageKind === 'builder'">
            <div class="grid grid-cols-1 gap-5 xl:grid-cols-[220px_minmax(0,1fr)]">
                <BitsSpotlightCard tone="slate">
                    <h2 class="mb-3 text-base font-semibold">组件库</h2>
                    <div class="zfy-builder-parts">
                        <el-button>内容流</el-button>
                        <el-button>轮播</el-button>
                        <el-button>榜单</el-button>
                        <el-button>VIP</el-button>
                        <el-button>自定义 HTML</el-button>
                    </div>
                </BitsSpotlightCard>
                <el-card shadow="never">
                    <template #header>页面布局 JSON</template>
                    <el-collapse>
                        <el-collapse-item v-for="layout in layouts" :key="layout.id" :title="layout.title" :name="layout.id">
                            <el-input type="textarea" :rows="12" :model-value="layout.schema" />
                            <el-button class="mt-3" type="primary">保存布局</el-button>
                        </el-collapse-item>
                    </el-collapse>
                </el-card>
            </div>
        </template>

        <template v-else-if="pageKind === 'settings'">
            <SettingsForm :schema="settingsSchema" />
        </template>

        <template v-else>
            <BitsSpotlightCard class="zfy-placeholder-card" tone="blue">
                <el-tag effect="plain">{{ currentPage ? '菜单入口' : '后台' }}</el-tag>
                <h2><BitsGradientText :text="title" subtle /></h2>
                <p>{{ description }}。这个功能页面还没有接入实际业务，当前先保留入口和说明，方便后台菜单结构先完整起来。</p>
                <el-space wrap>
                    <el-button type="primary" @click="goAdmin('dashboard')">返回仪表盘</el-button>
                    <el-button @click="copyPlaceholder">记录占位</el-button>
                </el-space>
            </BitsSpotlightCard>
        </template>

        <el-dialog v-model="quickSettingsVisible" title="快捷设置" width="680px" destroy-on-close :close-on-click-modal="true" :lock-scroll="false">
            <el-form label-position="top">
                <el-form-item label="标题" required>
                    <el-input v-model="quickSettingsForm.title" maxlength="180" show-word-limit />
                </el-form-item>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <el-form-item label="类型">
                        <el-select v-model="quickSettingsForm.type" class="w-full">
                            <el-option
                                v-for="type in contentTypes"
                                :key="type.value"
                                :label="type.label"
                                :value="type.value"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item label="状态">
                        <el-select v-model="quickSettingsForm.status" class="w-full">
                            <el-option label="草稿" value="draft" />
                            <el-option label="已发布" value="published" />
                        </el-select>
                    </el-form-item>
                    <el-form-item label="分类">
                        <el-select v-model="quickSettingsForm.category_id" class="w-full" clearable placeholder="未分类">
                            <el-option
                                v-for="category in categories"
                                :key="category.id"
                                :label="category.name"
                                :value="category.id"
                            />
                        </el-select>
                    </el-form-item>
                </div>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <el-form-item label="专题">
                        <el-input v-model="quickSettingsForm.topic" placeholder="例如：运营专题" maxlength="120" />
                    </el-form-item>
                    <el-form-item label="标签">
                        <el-input v-model="quickSettingsForm.tags" placeholder="多个标签用逗号分隔" />
                    </el-form-item>
                </div>

                <el-form-item label="封面">
                    <CoverImageField
                        v-model="quickSettingsForm.cover_url"
                        :media="editorPayload.media"
                        :routes="payload.routes"
                    />
                </el-form-item>

                <el-form-item label="摘要">
                    <el-input v-model="quickSettingsForm.excerpt" type="textarea" :rows="4" maxlength="1000" show-word-limit />
                </el-form-item>
            </el-form>

            <template #footer>
                <el-button @click="quickSettingsVisible = false">取消</el-button>
                <el-button :loading="quickSettingsSaving" type="primary" @click="saveQuickSettings">保存设置</el-button>
            </template>
        </el-dialog>
    </section>
</template>
