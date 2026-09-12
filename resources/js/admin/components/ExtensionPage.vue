<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref, reactive } from 'vue';
import { ElMessage } from 'element-plus';
import { adminRequest } from './adminRequest';
const props = defineProps<{ definition: Record<string, any>; csrf: string }>();
const mountPoint = ref<HTMLElement>();
const rows = ref<any[]>([]);
const values = reactive<Record<string, any>>({});
const busy = ref(false);
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
            const result = await adminRequest(props.definition.endpoint, props.csrf);
            if (props.definition.fields) Object.assign(values, result.data || {});
            else rows.value = result.data?.data || result.data || [];
        }
    } catch (error) { ElMessage.error((error as Error).message); }
});
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
    <section ref="mountPoint">
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
        <el-table v-else-if="!definition.module" :data="rows"><el-table-column v-for="column in definition.columns || []" :key="column.key" :prop="column.key" :label="column.label" /></el-table>
    </section>
</template>
