<script setup lang="ts">
import { computed } from 'vue';

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
const fallbackCover = '/assets/zfy/placeholders/cover-blue.svg';
const fallbackAvatar = '/assets/zfy/placeholders/avatar.svg';

const statusLabels: Record<string, string> = {
    published: '已发布',
    draft: '草稿',
    pending: '待审核',
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

function visibleTags(row: AdminTableRow): ContentTag[] {
    return contentTags(row).slice(0, 4);
}

function hiddenTagCount(row: AdminTableRow): number {
    return Math.max(contentTags(row).length - 4, 0);
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
        :empty-text="props.emptyText || '暂无数据'"
        :class="['zfy-admin-table', { 'zfy-admin-table--content': isContentTable }]"
    >
        <template v-if="isContentTable">
            <el-table-column label="文章" min-width="430">
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
                                <strong class="zfy-content-title">{{ row.title || '未命名文章' }}</strong>
                                <el-tag effect="plain" size="small">{{ typeLabel(row) }}</el-tag>
                            </div>
                            <p class="zfy-content-excerpt">{{ row.excerpt || '暂无摘要' }}</p>
                            <div class="zfy-content-meta">
                                <span class="zfy-content-author">
                                    <img
                                        :src="row.author_avatar || fallbackAvatar"
                                        :alt="row.author_name || '作者头像'"
                                        @error="imageFallback($event, fallbackAvatar)"
                                    >
                                    {{ row.author_name || '未设置作者' }}
                                </span>
                                <span>{{ row.category_name || '未分类' }}</span>
                                <span>ID {{ row.id }}</span>
                            </div>
                        </div>
                    </div>
                </template>
            </el-table-column>
            <el-table-column label="专题" min-width="150">
                <template #default="{ row }">
                    <el-tag v-if="row.topic_name && row.topic_name !== '未归入专题'" effect="plain" size="small">
                        {{ row.topic_name }}
                    </el-tag>
                    <span v-else class="zfy-content-empty">未归入专题</span>
                </template>
            </el-table-column>
            <el-table-column label="标签" min-width="190">
                <template #default="{ row }">
                    <div v-if="visibleTags(row).length" class="zfy-content-tags">
                        <el-tag
                            v-for="(tag, index) in visibleTags(row)"
                            :key="tagKey(tag, index)"
                            effect="light"
                            size="small"
                        >
                            {{ tag.name }}
                        </el-tag>
                        <el-tag v-if="hiddenTagCount(row)" effect="plain" size="small">+{{ hiddenTagCount(row) }}</el-tag>
                    </div>
                    <span v-else class="zfy-content-empty">暂无标签</span>
                </template>
            </el-table-column>
            <el-table-column label="数据" width="240">
                <template #default="{ row }">
                    <div class="zfy-content-metrics">
                        <span v-for="metric in contentMetrics(row)" :key="metric.key || metric.label">
                            {{ metricLabel(metric.value) }} {{ metric.label }}
                        </span>
                    </div>
                </template>
            </el-table-column>
            <el-table-column prop="status" label="状态" width="120">
                <template #default="{ row }">
                    <el-tag effect="light" size="small" :type="statusTagType(row)">{{ statusLabel(row) }}</el-tag>
                </template>
            </el-table-column>
            <el-table-column label="发布时间" width="170">
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

        <el-table-column :width="isContentTable ? 240 : 150" label="操作" fixed="right">
            <template #default="{ row }">
                <template v-if="isContentTable">
                    <div class="zfy-content-actions">
                        <el-button link type="primary" @click="emit('edit', row)">编辑</el-button>
                        <el-button link @click="emit('settings', row)">设置</el-button>
                        <el-button link type="warning" @click="emit('toggleStatus', row)">{{ toggleStatusLabel(row) }}</el-button>
                        <el-button link type="danger" @click="emit('delete', row)">删除</el-button>
                    </div>
                </template>
                <template v-else>
                    <el-button link type="primary" @click="emit('edit', row)">编辑</el-button>
                    <el-button link>日志</el-button>
                </template>
            </template>
        </el-table-column>
    </el-table>
</template>

<style scoped>
.zfy-content-cell {
    display: grid;
    grid-template-columns: 96px minmax(0, 1fr);
    gap: 12px;
    align-items: center;
    min-width: 0;
    padding: 6px 0;
}

.zfy-content-cover {
    width: 96px;
    aspect-ratio: 16 / 10;
    border: 1px solid var(--zfy-admin-line);
    border-radius: 8px;
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
    color: #7b8798;
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
    gap: 6px 12px;
    color: #667085;
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
        grid-template-columns: 74px minmax(0, 1fr);
    }

    .zfy-content-cover {
        width: 74px;
    }
}
</style>
