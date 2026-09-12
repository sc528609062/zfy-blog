<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Delete, Upload, Search } from '@element-plus/icons-vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { adminRequest } from './adminRequest';
const props = defineProps<{ csrf: string }>();
const items = ref<Record<string, any>[]>([]);
const query = ref('');
const type = ref('all');
const page = ref(1);
const total = ref(0);
const loading = ref(false);
const uploading = ref(false);
const picker = ref<HTMLInputElement>();
async function load() {
    loading.value = true;
    try {
        const result = await adminRequest(`/admin/media/library?${new URLSearchParams({ q: query.value, type: type.value, page: String(page.value), per_page: '24' })}`, props.csrf);
        items.value = result.items;
        total.value = result.meta.total;
    } catch (error) { ElMessage.error((error as Error).message); }
    finally { loading.value = false; }
}
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
        await load();
    } catch (error) { if (error instanceof Error) ElMessage.error(error.message); }
}
onMounted(load);
</script>
<template>
    <section v-loading="loading">
        <div class="media-toolbar">
            <el-input v-model="query" aria-label="搜索媒体" clearable @keyup.enter="page = 1; load()" />
            <el-select v-model="type" aria-label="媒体类型" @change="page = 1; load()"><el-option v-for="(label, value) in { all: '全部', image: '图片', video: '视频', audio: '音频', archive: '压缩包', file: '文档' }" :key="value" :value="value" :label="label" /></el-select>
            <el-button :icon="Search" @click="page = 1; load()">搜索</el-button>
            <el-button :icon="Upload" type="primary" :loading="uploading" @click="picker?.click()">上传媒体</el-button>
            <input ref="picker" type="file" multiple hidden @change="upload">
        </div>
        <el-table :data="items" empty-text="暂无媒体">
            <el-table-column label="预览" width="90"><template #default="{ row }"><img v-if="row.thumb_url" :src="row.thumb_url" :alt="row.name" width="60" height="45" style="object-fit:cover"></template></el-table-column>
            <el-table-column label="名称" min-width="200"><template #default="{ row }"><a :href="row.url" target="_blank" rel="noopener">{{ row.name }}</a></template></el-table-column>
            <el-table-column prop="mime" label="类型" min-width="160" />
            <el-table-column label="大小" width="120"><template #default="{ row }">{{ (row.size / 1024).toFixed(1) }} KB</template></el-table-column>
            <el-table-column label="操作" width="80"><template #default="{ row }"><el-button :icon="Delete" type="danger" text title="删除" aria-label="删除媒体" @click="remove(row)" /></template></el-table-column>
        </el-table>
        <el-pagination v-model:current-page="page" :total="total" :page-size="24" layout="prev, pager, next, total" @current-change="load" />
    </section>
</template>
<style scoped>
.media-toolbar { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 20px; }
.media-toolbar > .el-input { width: 240px; }
.media-toolbar > .el-select { width: 120px; }
.el-pagination { margin-top: 20px; overflow: auto; }
</style>
