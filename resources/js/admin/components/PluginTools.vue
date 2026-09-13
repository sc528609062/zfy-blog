<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Upload, Delete } from '@element-plus/icons-vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { adminRequest } from './adminRequest';
import ExtensionUpdate from './ExtensionUpdate.vue';
const props = defineProps<{ csrf: string; installer: boolean }>();
const plugins = ref<Record<string, any>[]>([]);
const busy = ref(false);
const picker = ref<HTMLInputElement>();
async function upload(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;
    busy.value = true;
    try {
        const body = new FormData(); body.append('file', file);
        const response = await fetch('/admin/plugins/install', { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': props.csrf, 'X-Requested-With': 'XMLHttpRequest' }, body });
        const data = await response.json();
        if (!response.ok) throw new Error(Object.values(data.errors || {}).flat().join('\n') || data.message);
        ElMessage.success(data.message);
    } catch (error) { ElMessage.error((error as Error).message); }
    finally { busy.value = false; input.value = ''; }
}
async function save(plugin: Record<string, any>) {
    busy.value = true;
    try {
        await adminRequest(`/admin/plugins/${plugin.id}/configuration`, props.csrf, 'PUT', { values: Object.fromEntries(plugin.fields.map((field: any) => [field.key, field.value])) });
        ElMessage.success('设置已保存');
    } catch (error) { ElMessage.error((error as Error).message); }
    finally { busy.value = false; }
}
async function uninstall(plugin: Record<string, any>) {
    try { await ElMessageBox.confirm('卸载插件文件并保留设置及业务数据？', plugin.name); }
    catch { return; }
    busy.value = true;
    try {
        await adminRequest(`/admin/plugins/${plugin.id}`, props.csrf, 'DELETE');
        plugin.installed = false;
        ElMessage.success('插件已卸载，数据已保留');
    } catch (error) { ElMessage.error((error as Error).message); }
    finally { busy.value = false; }
}
async function purge(plugin: Record<string, any>) {
    try { await ElMessageBox.confirm('永久清理插件设置及插件声明的业务数据？此操作不能通过重新安装撤销。', plugin.name, { type: 'warning' }); }
    catch { return; }
    busy.value = true;
    try {
        await adminRequest(`/admin/plugins/${plugin.id}/data`, props.csrf, 'DELETE');
        plugins.value = plugins.value.filter(item => item.id !== plugin.id);
        ElMessage.success('插件数据已清理');
    } catch (error) { ElMessage.error((error as Error).message); }
    finally { busy.value = false; }
}
onMounted(async () => {
    if (!props.installer) {
        try { plugins.value = (await adminRequest('/admin/plugins/configuration', props.csrf)).data; }
        catch (error) { ElMessage.error((error as Error).message); }
    }
});
</script>
<template>
    <section v-if="installer"><input ref="picker" type="file" accept=".zip" hidden @change="upload"><el-button type="primary" :icon="Upload" :loading="busy" @click="picker?.click()">安装 / 升级插件</el-button></section>
    <el-tabs v-else class="admin-plugin-settings">
        <el-tab-pane v-for="plugin in plugins" :key="plugin.id" :label="plugin.name">
            <ExtensionUpdate v-if="plugin.installed" type="plugin" :slug="plugin.slug" :csrf="csrf" :active="plugin.enabled" />
            <el-button v-if="plugin.installed" :icon="Delete" type="danger" :disabled="plugin.enabled || busy" @click="uninstall(plugin)">卸载</el-button>
            <el-button v-else :icon="Delete" type="danger" :disabled="busy" @click="purge(plugin)">清理保留数据</el-button>
            <el-form label-position="top" style="max-width:680px">
                <el-form-item v-for="field in plugin.fields" :key="field.key" :label="field.label">
                    <el-switch v-if="field.type === 'boolean'" v-model="field.value" />
                    <el-input-number v-else-if="field.type === 'number'" v-model="field.value" />
                    <el-select v-else-if="field.type === 'select'" v-model="field.value"><el-option v-for="(label, value) in field.options" :key="value" :label="String(label)" :value="value" /></el-select>
                    <el-input v-else v-model="field.value" :type="field.type === 'password' ? 'password' : 'text'" :show-password="field.type === 'password'" />
                </el-form-item>
                <footer class="admin-form-actions"><el-button type="primary" :loading="busy" @click="save(plugin)">保存设置</el-button></footer>
            </el-form>
        </el-tab-pane>
    </el-tabs>
</template>
