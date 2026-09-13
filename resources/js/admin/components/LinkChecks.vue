<script setup lang="ts">
import { ref, onMounted, nextTick } from 'vue';
import { Refresh } from '@element-plus/icons-vue';
import { ElMessage, ElTable } from 'element-plus';
import { adminRequest } from './adminRequest';
import AdminPagination from './AdminPagination.vue';
const props = defineProps<{ csrf: string }>();
const links = ref<Record<string, any>[]>([]);
const rows = ref<Record<string, any>[]>([]);
const table = ref<InstanceType<typeof ElTable>>();
const selected = ref<number>();
const page = ref(1);
const pageSize = ref(20);
const loading = ref(false);
let sequence = 0;
const total = ref(0);
const busy = ref(false);
async function load() {
    const current = ++sequence;
    loading.value = true;
    try {
        const result = await adminRequest(`/admin/link-tools/checks?page=${page.value}&per_page=${pageSize.value}`, props.csrf);
        if (current !== sequence) return;
        links.value = result.links; rows.value = result.data.data; total.value = result.data.total;
        page.value = result.data.current_page;
        await nextTick();
        if (current === sequence) table.value?.setScrollTop(0);
    } catch (error) { if (current === sequence) ElMessage.error((error as Error).message); }
    finally { if (current === sequence) loading.value = false; }
}
function paginate(nextPage: number, size: number) { page.value = nextPage; pageSize.value = size; void load(); }
async function check() {
    if (!selected.value) return;
    busy.value = true;
    try { await adminRequest(`/admin/link-tools/${selected.value}/check`, props.csrf, 'POST'); await load(); }
    catch (error) { ElMessage.error((error as Error).message); }
    finally { busy.value = false; }
}
onMounted(load);
</script>
<template>
    <section class="admin-table-workspace" v-loading="loading">
        <div class="admin-list-toolbar"><el-select v-model="selected" filterable aria-label="选择链接" placeholder="选择链接"><el-option v-for="link in links" :key="link.id" :value="link.id" :label="link.name" /></el-select><el-button :icon="Refresh" :loading="busy" :disabled="!selected" @click="check">检测链接</el-button><el-tooltip content="刷新记录"><el-button :icon="Refresh" aria-label="刷新记录" @click="load" /></el-tooltip></div>
        <div class="admin-table-region"><el-table ref="table" :data="rows" height="100%" row-key="id" empty-text="暂无检测记录"><el-table-column prop="link.name" label="链接" min-width="180" show-overflow-tooltip /><el-table-column prop="http_code" label="HTTP 状态" width="120" /><el-table-column prop="message" label="结果" min-width="200" show-overflow-tooltip /><el-table-column prop="checked_at" label="检测时间" min-width="180" /></el-table></div>
        <AdminPagination :page="page" :page-size="pageSize" :total="total" :disabled="loading" @change="paginate" />
    </section>
</template>
