<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Refresh } from '@element-plus/icons-vue';
import { ElMessage } from 'element-plus';
import { adminRequest } from './adminRequest';
const props = defineProps<{ csrf: string }>();
const links = ref<Record<string, any>[]>([]);
const rows = ref<Record<string, any>[]>([]);
const selected = ref<number>();
const page = ref(1);
const total = ref(0);
const busy = ref(false);
async function load() {
    try {
        const result = await adminRequest(`/admin/link-tools/checks?page=${page.value}`, props.csrf);
        links.value = result.links; rows.value = result.data.data; total.value = result.data.total;
    } catch (error) { ElMessage.error((error as Error).message); }
}
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
    <section>
        <el-space wrap><el-select v-model="selected" filterable aria-label="选择链接" style="width:280px"><el-option v-for="link in links" :key="link.id" :value="link.id" :label="link.name" /></el-select><el-button :icon="Refresh" :loading="busy" :disabled="!selected" @click="check">检测链接</el-button></el-space>
        <el-table :data="rows"><el-table-column prop="link.name" label="链接" /><el-table-column prop="http_code" label="HTTP 状态" width="120" /><el-table-column prop="message" label="结果" /><el-table-column prop="checked_at" label="检测时间" /></el-table>
        <el-pagination v-model:current-page="page" :page-size="20" :total="total" layout="prev, pager, next" @current-change="load" />
    </section>
</template>
