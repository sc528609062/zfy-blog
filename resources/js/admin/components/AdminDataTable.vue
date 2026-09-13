<script setup lang="ts">
import { computed, ref, onMounted, onBeforeUnmount } from 'vue';
import { Edit, Setting, Delete, Upload, Download, MoreFilled } from '@element-plus/icons-vue';

type TableVariant = 'default' | 'content';

interface ContentTag {
    id?: number | string;
    name?: string;
    color?: string | null;
}

interface ContentMetric {
    key?: string;
    label?: string;
    value?: number | string;
}

interface AdminTableRow {
    id?: number | string;
    title?: string;
    status?: string;
    status_label?: string;
    status_tag_type?: 'success' | 'info' | 'warning' | 'danger' | 'primary';
    type?: string;
    type_label?: string;
    created_at?: string | null;
    published_at?: string | null;
    cover_url?: string | null;
    excerpt?: string | null;
    author_name?: string | null;
    author_avatar?: string | null;
    category_name?: string | null;
    topic_name?: string | null;
    tags?: Array<ContentTag | string>;
    view_count?: number;
    comment_count?: number;
    like_count?: number;
    favorite_count?: number;
    download_count?: number;
    purchase_count?: number;
    metrics?: ContentMetric[];
    edit_url?: string;
    [key: string]: unknown;
}

const props = withDefaults(defineProps<{
    rows: AdminTableRow[];
    emptyText?: string;
    variant?: TableVariant;
    height?: string | number;
}>(), {
    variant: 'default',
});

const emit = defineEmits<{
    edit: [row: AdminTableRow];
    settings: [row: AdminTableRow];
    toggleStatus: [row: AdminTableRow];
    delete: [row: AdminTableRow];
}>();

const isContentTable = computed(() => props.variant === 'content');
const mediaQuery = window.matchMedia('(max-width: 600px)');
const mobile = ref(mediaQuery.matches);
function resize() { mobile.value = mediaQuery.matches; }
onMounted(() => mediaQuery.addEventListener('change', resize));
onBeforeUnmount(() => mediaQuery.removeEventListener('change', resize));
function rowAction(command: 'edit' | 'settings' | 'toggleStatus' | 'delete', row: AdminTableRow) {
    if (command === 'edit') emit('edit', row);
    else if (command === 'settings') emit('settings', row);
    else if (command === 'toggleStatus') emit('toggleStatus', row);
    else emit('delete', row);
}
const fallbackCover = '/assets/zfy/placeholders/cover-blue.svg';
const fallbackAvatar = '/assets/zfy/placeholders/avatar.svg';

const statusLabels: Record<string, string> = {
    published: '已发布',
    draft: '草稿',
    pending: '待审核',
    scheduled: '定时发布',
    private: '私密',
    archived: '已归档',
    enabled: '已启用',
    disabled: '已禁用',
    paid: '已支付',
    unpaid: '未支付',
    refunded: '已退款',
    approved: '已通过',
    rejected: '已拒绝',
};

const statusTypes: Record<string, AdminTableRow['status_tag_type']> = {
    published: 'success',
    enabled: 'success',
    paid: 'success',
    approved: 'success',
    draft: 'warning',
    pending: 'warning',
    unpaid: 'warning',
    rejected: 'danger',
    refunded: 'info',
    archived: 'info',
    disabled: 'info',
};

const typeLabels: Record<string, string> = {
    post: '文章',
    images: '图集',
    files: '资源',
    page: '页面',
};

function statusLabel(row: AdminTableRow): string {
    const status = String(row.status || '');

    return row.status_label || statusLabels[status] || status || '未知';
}

function statusTagType(row: AdminTableRow): AdminTableRow['status_tag_type'] {
    const status = String(row.status || '');

    return row.status_tag_type || statusTypes[status] || 'info';
}

function typeLabel(row: AdminTableRow): string {
    const type = String(row.type || '');

    return row.type_label || typeLabels[type] || type || '内容';
}

function contentTags(row: AdminTableRow): ContentTag[] {
    if (!Array.isArray(row.tags)) {
        return [];
    }

    return row.tags
        .map((tag) => (typeof tag === 'string' ? { name: tag } : tag))
        .filter((tag) => Boolean(tag.name));
}

function tagKey(tag: ContentTag, index: number): string | number {
    return tag.id || `${tag.name}-${index}`;
}

function metricLabel(value: unknown): string {
    const numberValue = typeof value === 'number' ? value : Number(value || 0);

    return Number.isFinite(numberValue) ? numberValue.toLocaleString('zh-CN') : '0';
}

function contentMetrics(row: AdminTableRow): ContentMetric[] {
    if (Array.isArray(row.metrics) && row.metrics.length) {
        return row.metrics;
    }

    return [
        { key: 'view_count', label: '阅读', value: row.view_count },
        { key: 'comment_count', label: '评论', value: row.comment_count },
        { key: 'like_count', label: '点赞', value: row.like_count },
        { key: 'favorite_count', label: '收藏', value: row.favorite_count },
        { key: 'download_count', label: '下载', value: row.download_count },
        { key: 'purchase_count', label: '购买', value: row.purchase_count },
    ];
}

function imageFallback(event: Event, src: string) {
    const image = event.target as HTMLImageElement | null;

    if (image && image.src !== src) {
        image.src = src;
    }
}

function toggleStatusLabel(row: AdminTableRow): string {
    return row.status === 'published' ? '设为草稿' : '发布';
}
</script>

<template>
    <el-table
        :data="props.rows"
        :height="height"
        row-key="id"
        :empty-text="props.emptyText || '暂无数据'"
        :class="['zfy-admin-table', { 'zfy-admin-table--content': isContentTable }]"
    >
        <template v-if="isContentTable">
            <el-table-column type="expand" width="40">
                <template #default="{ row }"><div class="admin-content-details">
                    <p>{{ row.excerpt || '暂无摘要' }}</p>
                    <p>专题：{{ row.topic_name || '未归入专题' }}</p>
                    <div class="zfy-content-tags"><span>标签：</span><el-tag v-for="(tag, index) in contentTags(row)" :key="tagKey(tag, index)" size="small">{{ tag.name }}</el-tag><span v-if="!contentTags(row).length">暂无标签</span></div>
                    <template v-if="mobile"><p>发布时间：{{ row.published_at || row.created_at || '-' }}</p><div class="zfy-content-metrics"><span v-for="metric in contentMetrics(row)" :key="metric.key || metric.label">{{ metricLabel(metric.value) }} {{ metric.label }}</span></div></template>
                </div></template>
            </el-table-column>
            <el-table-column label="内容" :min-width="mobile ? 200 : 330">
                <template #default="{ row }">
                    <div class="zfy-content-cell">
                        <img
                            class="zfy-content-cover"
                            :src="row.cover_url || fallbackCover"
                            :alt="row.title || '文章封面'"
                            @error="imageFallback($event, fallbackCover)"
                        >
                        <div class="zfy-content-main">
                            <div class="zfy-content-heading">
                                <el-tooltip :content="row.title || '未命名文章'" placement="top"><strong class="zfy-content-title">{{ row.title || '未命名文章' }}</strong></el-tooltip>
                                <el-tag v-if="!mobile" effect="plain" size="small">{{ typeLabel(row) }}</el-tag>
                            </div>
                            <div class="zfy-content-meta">
                                <span class="zfy-content-author">
                                    <img
                                        :src="row.author_avatar || fallbackAvatar"
                                        :alt="row.author_name || '作者头像'"
                                        @error="imageFallback($event, fallbackAvatar)"
                                    >
                                    {{ row.author_name || '未设置作者' }}
                                </span>
                                <el-tag v-if="mobile" effect="light" size="small" :type="statusTagType(row)">{{ statusLabel(row) }}</el-tag>
                                <template v-else><span>{{ row.category_name || '未分类' }}</span><span>ID {{ row.id }}</span></template>
                            </div>
                        </div>
                    </div>
                </template>
            </el-table-column>
            <el-table-column v-if="!mobile" label="数据" width="170">
                <template #default="{ row }">
                    <div class="zfy-content-metrics">
                        <span v-for="metric in contentMetrics(row)" :key="metric.key || metric.label">
                            {{ metricLabel(metric.value) }} {{ metric.label }}
                        </span>
                    </div>
                </template>
            </el-table-column>
            <el-table-column v-if="!mobile" prop="status" label="状态" width="100">
                <template #default="{ row }">
                    <el-tag effect="light" size="small" :type="statusTagType(row)">{{ statusLabel(row) }}</el-tag>
                </template>
            </el-table-column>
            <el-table-column v-if="!mobile" label="发布时间" width="155">
                <template #default="{ row }">
                    <span>{{ row.published_at || row.created_at || '-' }}</span>
                </template>
            </el-table-column>
        </template>

        <template v-else>
            <el-table-column prop="id" label="ID" width="86" />
            <el-table-column prop="title" label="标题 / 对象" min-width="240" show-overflow-tooltip />
            <el-table-column prop="status" label="状态" width="120">
                <template #default="{ row }">
                    <el-tag effect="light" size="small" :type="statusTagType(row)">{{ statusLabel(row) }}</el-tag>
                </template>
            </el-table-column>
            <el-table-column prop="type" label="类型" width="130" />
            <el-table-column prop="created_at" label="时间" width="170" />
        </template>

        <el-table-column v-if="isContentTable" :width="mobile ? 60 : 145" label="操作" fixed="right">
            <template #default="{ row }">
                <template v-if="isContentTable">
                    <el-dropdown v-if="mobile" trigger="click" @command="rowAction($event, row)">
                        <el-button class="admin-content-more" text :icon="MoreFilled" aria-label="内容操作" title="内容操作" />
                        <template #dropdown><el-dropdown-menu>
                            <el-dropdown-item command="edit" :icon="Edit">编辑</el-dropdown-item>
                            <el-dropdown-item command="settings" :icon="Setting">设置</el-dropdown-item>
                            <el-dropdown-item command="toggleStatus" :icon="row.status === 'published' ? Download : Upload">{{ toggleStatusLabel(row) }}</el-dropdown-item>
                            <el-dropdown-item command="delete" :icon="Delete" divided>删除</el-dropdown-item>
                        </el-dropdown-menu></template>
                    </el-dropdown>
                    <div v-else class="zfy-art-row-actions">
                        <el-tooltip content="编辑"><el-button text :icon="Edit" aria-label="编辑" type="primary" @click="emit('edit', row)" /></el-tooltip>
                        <el-tooltip content="设置"><el-button text :icon="Setting" aria-label="设置" @click="emit('settings', row)" /></el-tooltip>
                        <el-tooltip :content="toggleStatusLabel(row)"><el-button text :icon="row.status === 'published' ? Download : Upload" :aria-label="toggleStatusLabel(row)" type="warning" @click="emit('toggleStatus', row)" /></el-tooltip>
                        <el-tooltip content="删除"><el-button text :icon="Delete" aria-label="删除" type="danger" @click="emit('delete', row)" /></el-tooltip>
                    </div>
                </template>
            </template>
        </el-table-column>
    </el-table>
</template>

<style scoped>
.zfy-content-cell {
    display: grid;
    grid-template-columns: 64px minmax(0, 1fr);
    gap: 12px;
    align-items: center;
    min-width: 0;
    padding: 2px 0;
}

.zfy-content-cover {
    width: 64px;
    aspect-ratio: 16 / 10;
    border: 1px solid var(--zfy-admin-line);
    border-radius: 4px;
    background: #f2f6fb;
    object-fit: cover;
}

.zfy-content-main {
    display: grid;
    gap: 7px;
    min-width: 0;
}

.zfy-content-heading,
.zfy-content-meta,
.zfy-content-tags,
.zfy-content-metrics {
    display: flex;
    align-items: center;
    min-width: 0;
}

.zfy-content-heading {
    gap: 8px;
}

.zfy-content-title {
    min-width: 0;
    overflow: hidden;
    color: var(--zfy-admin-text);
    font-size: 14px;
    font-weight: 700;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.zfy-content-excerpt {
    display: -webkit-box;
    margin: 0;
    overflow: hidden;
    color: var(--zfy-admin-muted);
    font-size: 12px;
    line-height: 1.5;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}

.zfy-content-meta {
    flex-wrap: wrap;
    gap: 6px 12px;
    color: var(--zfy-admin-muted);
    font-size: 12px;
}

.zfy-content-author {
    display: inline-flex;
    align-items: center;
    min-width: 0;
    gap: 6px;
}

.zfy-content-author img {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    object-fit: cover;
}

.zfy-content-tags {
    flex-wrap: wrap;
    gap: 6px;
}

.zfy-content-empty {
    color: #98a2b3;
    font-size: 12px;
}

.zfy-content-metrics {
    flex-wrap: wrap;
    gap: 2px 10px;
    color: var(--zfy-admin-muted);
    font-size: 12px;
    line-height: 1.6;
}

.zfy-content-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0 4px;
}

:deep(.zfy-admin-table--content .el-table__row > td) {
    padding-top: 10px;
    padding-bottom: 10px;
}

@media (max-width: 768px) {
    .zfy-content-cell {
        grid-template-columns: 50px minmax(0, 1fr);
    }

    .zfy-content-cover {
        width: 50px;
    }
}
.admin-content-details { padding: 8px 24px 16px; color: var(--zfy-admin-muted); overflow-wrap: anywhere; }
.admin-content-more.el-button { width: 30px; height: 30px; padding: 6px; }
</style>
