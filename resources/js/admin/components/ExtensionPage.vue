<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref, reactive, computed } from 'vue';
import { ElMessage } from 'element-plus';
import { adminRequest } from './adminRequest';
import AdminPagination from './AdminPagination.vue';
import { Refresh } from '@element-plus/icons-vue';
const props = defineProps<{ definition: Record<string, any>; csrf: string }>();
const mountPoint = ref<HTMLElement>();
const rows = ref<any[]>([]);
const values = reactive<Record<string, any>>({});
const busy = ref(false);
const page = ref(1);
const pageSize = ref(20);
const total = ref(0);
const serverPaged = ref(false);
const visibleRows = computed(() => serverPaged.value ? rows.value : rows.value.slice((page.value - 1) * pageSize.value, page.value * pageSize.value));
let sequence = 0;
const validation = ref<Record<string, string>>({});
let unmount: (() => void) | undefined;
onMounted(async () => {
    try {
        if (props.definition.module) {
            const url = new URL(props.definition.module, window.location.origin);
            if (url.origin !== window.location.origin || !url.pathname.startsWith('/extensions/assets/plugin/')) throw new Error('扩展页面资源地址无效');
            const module = await import(/* @vite-ignore */ url.href);
            const cleanup = await module.mount(mountPoint.value, { csrf: props.csrf, request: (path: string, method = 'GET', data?: unknown) => adminRequest(path, props.csrf, method, data), definition: props.definition });
            if (typeof cleanup === 'function') unmount = cleanup;
        } else if (props.definition.endpoint) {
            await load();
        }
    } catch (error) { ElMessage.error((error as Error).message); }
});
async function load() {
    const current = ++sequence;
    busy.value = true;
    try {
        const url = new URL(props.definition.endpoint, window.location.origin);
        if (!props.definition.fields) { url.searchParams.set('page', String(page.value)); url.searchParams.set('per_page', String(pageSize.value)); }
        const result = await adminRequest(url.pathname + url.search, props.csrf);
        if (current !== sequence) return;
        if (props.definition.fields) Object.assign(values, result.data || {});
        else {
            serverPaged.value = Array.isArray(result.data?.data) && Number.isFinite(result.data.total);
            rows.value = serverPaged.value ? result.data.data : Array.isArray(result.data) ? result.data : [];
            total.value = serverPaged.value ? result.data.total : rows.value.length;
            if (serverPaged.value) { page.value = result.data.current_page; pageSize.value = result.data.per_page; }
            else page.value = Math.min(page.value, Math.max(1, Math.ceil(total.value / pageSize.value)));
        }
    } catch (error) { if (current === sequence) ElMessage.error((error as Error).message); }
    finally { if (current === sequence) busy.value = false; }
}
function paginate(nextPage: number, size: number) { page.value = nextPage; pageSize.value = size; if (serverPaged.value) void load(); }
async function save() {
    validation.value = {};
    for (const field of props.definition.fields || []) {
        if (field.required && (values[field.key] === null || values[field.key] === undefined || values[field.key] === '')) validation.value[field.key] = '此项必填';
    }
    if (Object.keys(validation.value).length) return;
    busy.value = true;
    try {
        await adminRequest(props.definition.save_endpoint || props.definition.endpoint, props.csrf, props.definition.save_method || 'PUT', values);
        ElMessage.success('已保存');
    } catch (error) { ElMessage.error((error as Error).message); }
    finally { busy.value = false; }
}
onBeforeUnmount(() => unmount?.());
</script>
<template>
    <section ref="mountPoint" :class="{ 'admin-table-workspace': !definition.module && !definition.fields }">
        <el-form v-if="!definition.module && definition.fields" label-position="top" style="max-width:720px" @submit.prevent="save">
            <el-form-item v-for="field in definition.fields" :key="field.key" :label="field.label" :required="field.required" :error="validation[field.key]">
                <el-switch v-if="field.type === 'boolean'" v-model="values[field.key]" />
                <el-input-number v-else-if="field.type === 'number'" v-model="values[field.key]" :min="field.min" :max="field.max" />
                <el-color-picker v-else-if="field.type === 'color'" v-model="values[field.key]" />
                <el-select v-else-if="field.type === 'select'" v-model="values[field.key]"><el-option v-for="(label, key) in field.options" :key="key" :label="String(label)" :value="key" /></el-select>
                <el-input v-else v-model="values[field.key]" :type="field.type === 'textarea' ? 'textarea' : 'text'" :maxlength="field.maxlength || 4000" />
            </el-form-item>
            <el-button native-type="submit" type="primary" :loading="busy">保存</el-button>
        </el-form>
        <template v-else-if="!definition.module">
            <div class="admin-list-toolbar"><el-tooltip content="刷新"><el-button :icon="Refresh" aria-label="刷新" :loading="busy" @click="load" /></el-tooltip></div>
            <div class="admin-table-region" v-loading="busy"><el-table :data="visibleRows" height="100%" empty-text="暂无记录"><el-table-column v-for="column in definition.columns || []" :key="column.key" :prop="column.key" :label="column.label" min-width="140" show-overflow-tooltip /></el-table></div>
            <AdminPagination :page="page" :page-size="pageSize" :total="total" :disabled="busy" @change="paginate" />
        </template>
    </section>
</template>
