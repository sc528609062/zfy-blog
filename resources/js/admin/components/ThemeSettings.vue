<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { adminRequest } from './adminRequest';
import ExtensionUpdate from './ExtensionUpdate.vue';
const props = defineProps<{ csrf: string }>();
const themes = ref<Record<string, any>[]>([]);
const saving = ref(false);
onMounted(async () => {
    try { themes.value = (await adminRequest('/admin/themes/configuration', props.csrf)).data; }
    catch (error) { ElMessage.error((error as Error).message); }
});
async function save(theme: Record<string, any>) {
    saving.value = true;
    try {
        await adminRequest(`/admin/themes/${theme.id}/configuration`, props.csrf, 'PUT', theme.values);
        ElMessage.success('主题配置已保存');
    } catch (error) { ElMessage.error((error as Error).message); }
    finally { saving.value = false; }
}
async function preview(theme: Record<string, any>) {
    const target = window.open('', '_blank');
    try {
        const result = await adminRequest(`/admin/themes/${theme.id}/preview`, props.csrf, 'POST', theme.values);
        if (target) { target.opener = null; target.location.href = result.url; }
    } catch (error) { target?.close(); ElMessage.error((error as Error).message); }
}
async function uninstall(theme: Record<string, any>) {
    try {
        await ElMessageBox.confirm('卸载主题并保留配置？', theme.name, { type: 'warning' });
        await adminRequest(`/admin/themes/${theme.id}/installation`, props.csrf, 'DELETE');
        theme.installed = false;
        ElMessage.success('主题已卸载');
    } catch (error) { if (error instanceof Error) ElMessage.error(error.message); }
}
async function purge(theme: Record<string, any>) {
    try {
        const { value } = await ElMessageBox.prompt(`输入主题标识 ${theme.slug} 以清理配置`, '清理已卸载主题', { inputValidator: value => value === theme.slug || '主题标识不匹配' });
        await adminRequest(`/admin/themes/${theme.id}/data`, props.csrf, 'DELETE', { confirm_slug: value });
        themes.value = themes.value.filter(item => item.id !== theme.id);
        ElMessage.success('配置已清理');
    } catch (error) { if (error instanceof Error) ElMessage.error(error.message); }
}
</script>
<template>
    <el-tabs class="mt-5">
        <el-tab-pane v-for="theme in themes" :key="theme.id" :label="theme.name">
            <ExtensionUpdate v-if="theme.installed" type="theme" :slug="theme.slug" :csrf="csrf" :active="theme.is_active" />
            <el-form label-position="top" style="max-width:680px">
                <el-form-item v-for="field in theme.fields" :key="field.key" :label="field.label">
                    <el-switch v-if="field.type === 'boolean'" v-model="theme.values[field.key]" />
                    <el-color-picker v-else-if="field.type === 'color'" v-model="theme.values[field.key]" />
                    <el-input-number v-else-if="field.type === 'number'" v-model="theme.values[field.key]" />
                    <el-select v-else-if="field.type === 'select'" v-model="theme.values[field.key]"><el-option v-for="(label, key) in field.options" :key="key" :label="String(label)" :value="key" /></el-select>
                    <el-input v-else v-model="theme.values[field.key]" :type="field.type === 'textarea' ? 'textarea' : 'text'" maxlength="4000" />
                </el-form-item>
                <el-button type="primary" :loading="saving" :disabled="!theme.installed" @click="save(theme)">保存配置</el-button>
                <el-button :disabled="!theme.installed" @click="preview(theme)">预览</el-button>
                <el-button v-if="theme.installed && !theme.is_active" type="danger" plain @click="uninstall(theme)">卸载主题</el-button>
                <el-button v-if="!theme.installed" type="danger" plain @click="purge(theme)">清理配置</el-button>
            </el-form>
        </el-tab-pane>
    </el-tabs>
</template>
