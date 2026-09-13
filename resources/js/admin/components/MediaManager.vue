<script setup lang="ts">
import { ref, onMounted, nextTick } from 'vue';
import { Delete, Upload, Search, Refresh } from '@element-plus/icons-vue';
import { ElMessage, ElMessageBox, ElTable } from 'element-plus';
import { adminRequest } from './adminRequest';
import AdminPagination from './AdminPagination.vue';
const props = defineProps<{ csrf: string }>();
const items = ref<Record<string, any>[]>([]);
const table = ref<InstanceType<typeof ElTable>>();
const query = ref('');
const type = ref('all');
const page = ref(1);
const pageSize = ref(24);
let sequence = 0;
const total = ref(0);
const loading = ref(false);
const uploading = ref(false);
const picker = ref<HTMLInputElement>();
async function load() {
    const current = ++sequence;
    loading.value = true;
    try {
        const result = await adminRequest(`/admin/media/library?${new URLSearchParams({ q: query.value, type: type.value, page: String(page.value), per_page: String(pageSize.value) })}`, props.csrf);
        if (current !== sequence) return;
        items.value = result.items;
        total.value = result.meta.total;
        const lastPage = Math.max(1, Math.ceil(total.value / pageSize.value));
        if (page.value > lastPage) { page.value = lastPage; await load(); }
        await nextTick();
        if (current === sequence) table.value?.setScrollTop(0);
    } catch (error) { if (current === sequence) ElMessage.error((error as Error).message); }
    finally { if (current === sequence) loading.value = false; }
}
function paginate(nextPage: number, size: number) { page.value = nextPage; pageSize.value = size; void load(); }
async function upload(event: Event) {
    const input = event.target as HTMLInputElement;
    uploading.value = true;
    try {
        for (const file of Array.from(input.files || [])) {
            const body = new FormData();
            body.append('file', file);
            const response = await fetch('/admin/media/upload', { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': props.csrf, 'X-Requested-With': 'XMLHttpRequest' }, body });
            const result = await response.json();
            if (!response.ok) throw new Error(Object.values(result.errors || {}).flat().join('\n') || result.message);
        }
        page.value = 1;
        await load();
        ElMessage.success('上传完成');
    } catch (error) { ElMessage.error((error as Error).message); }
    finally { uploading.value = false; input.value = ''; }
}
async function remove(item: Record<string, any>) {
    try {
        await ElMessageBox.confirm(`永久删除「${item.name}」？`, '删除媒体', { type: 'warning' });
        await adminRequest(`/admin/media/${item.id}`, props.csrf, 'DELETE');
        if (items.value.length === 1 && page.value > 1) page.value--;
        await load();
    } catch (error) { if (error instanceof Error) ElMessage.error(error.message); }
}
onMounted(load);
</script>
<template>
    <section class="media-manager admin-table-workspace" v-loading="loading">
        <div class="media-toolbar admin-list-toolbar">
            <el-input v-model="query" placeholder="搜索媒体" aria-label="搜索媒体" maxlength="120" clearable @keyup.enter="page = 1; load()" @clear="page = 1; load()" />
            <el-select v-model="type" aria-label="媒体类型" @change="page = 1; load()"><el-option v-for="(label, value) in { all: '全部', image: '图片', video: '视频', audio: '音频', archive: '压缩包', file: '文档' }" :key="value" :value="value" :label="label" /></el-select>
            <el-button :icon="Search" @click="page = 1; load()">搜索</el-button>
            <el-tooltip content="刷新"><el-button :icon="Refresh" aria-label="刷新" @click="load" /></el-tooltip>
            <el-button class="admin-toolbar-primary" :icon="Upload" type="primary" :loading="uploading" @click="picker?.click()">上传媒体</el-button>
            <input ref="picker" type="file" multiple hidden @change="upload">
        </div>
        <div class="admin-table-region"><el-table ref="table" :data="items" height="100%" row-key="id" empty-text="暂无媒体">
            <el-table-column label="预览" width="90"><template #default="{ row }"><img v-if="row.thumb_url" :src="row.thumb_url" :alt="row.name" width="60" height="45" style="object-fit:cover"></template></el-table-column>
            <el-table-column label="名称" min-width="200" show-overflow-tooltip><template #default="{ row }"><a :href="row.url" target="_blank" rel="noopener">{{ row.name }}</a></template></el-table-column>
            <el-table-column prop="mime" label="类型" min-width="160" />
            <el-table-column label="大小" width="120"><template #default="{ row }">{{ (row.size / 1024).toFixed(1) }} KB</template></el-table-column>
            <el-table-column label="操作" width="80" fixed="right"><template #default="{ row }"><el-button :icon="Delete" type="danger" text title="删除" aria-label="删除媒体" @click="remove(row)" /></template></el-table-column>
        </el-table></div>
        <AdminPagination :page="page" :page-size="pageSize" :total="total" :sizes="[12,24,48,96]" :disabled="loading" @change="paginate" />
    </section>
</template>
<style scoped>
.media-toolbar { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 20px; }
.media-toolbar > .el-input { width: 240px; }
.media-toolbar > .el-select { width: 120px; }
.el-pagination { margin-top: 20px; overflow: auto; }
</style>
