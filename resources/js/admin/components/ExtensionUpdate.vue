<script setup lang="ts">
import { ref } from 'vue';
import { Refresh, Download, Setting } from '@element-plus/icons-vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { adminRequest } from './adminRequest';
const props = defineProps<{ type: 'theme' | 'plugin'; slug: string; csrf: string; active: boolean }>();
const open = ref(false);
const busy = ref(false);
const editable = ref(false);
const source = ref({ provider: 'github', repository: '', public_key: '' });
const release = ref<Record<string, any> | null>(null);
const updates = ref<Record<string, any>[]>([]);
const endpoint = () => `/admin/extensions/${props.type}/${props.slug}/update`;
async function configure() {
    try {
        const result = await adminRequest(endpoint(), props.csrf);
        source.value = result.data; editable.value = result.can_configure; updates.value = result.updates || []; open.value = true;
    } catch (error) { ElMessage.error((error as Error).message); }
}
async function save() {
    busy.value = true;
    try { await adminRequest(endpoint(), props.csrf, 'PUT', source.value); release.value = null; ElMessage.success('更新源已保存'); }
    catch (error) { ElMessage.error((error as Error).message); }
    finally { busy.value = false; }
}
async function check() {
    busy.value = true;
    try { release.value = (await adminRequest(endpoint() + '/check', props.csrf, 'POST')).data; }
    catch (error) { ElMessage.error((error as Error).message); }
    finally { busy.value = false; }
}
async function install() {
    try { await ElMessageBox.confirm(`安装已验证版本 ${release.value?.version}？`, '更新扩展'); }
    catch { return; }
    busy.value = true;
    try { const result = await adminRequest(endpoint() + '/apply', props.csrf, 'POST', { id: release.value?.id }); release.value = null; ElMessage.success(result.message); }
    catch (error) { ElMessage.error((error as Error).message); }
    finally { busy.value = false; }
}
</script>
<template>
    <el-button :icon="Setting" @click="configure">版本更新</el-button>
    <el-dialog v-model="open" title="扩展更新" width="min(640px, 94vw)">
        <el-form label-position="top" :disabled="busy || !editable">
            <el-form-item label="平台"><el-select v-model="source.provider"><el-option label="GitHub" value="github" /><el-option label="Gitee" value="gitee" /></el-select></el-form-item>
            <el-form-item label="仓库"><el-input v-model="source.repository" placeholder="owner/repository" /></el-form-item>
            <el-form-item label="信任的发布公钥 (PEM)"><el-input v-model="source.public_key" type="textarea" :rows="6" /></el-form-item>
            <el-button v-if="editable" :loading="busy" @click="save">保存更新源</el-button>
        </el-form>
        <el-divider />
        <el-button :icon="Refresh" :loading="busy" @click="check">检查更新</el-button>
        <el-button :icon="Refresh" @click="configure">刷新任务</el-button>
        <el-table v-if="updates.length" :data="updates"><el-table-column prop="version" label="版本" /><el-table-column prop="status" label="状态" /><el-table-column prop="error" label="错误" /></el-table>
        <template v-if="release">
            <p>{{ release.available ? `可用版本 ${release.version}` : `当前版本 ${release.version}` }}</p>
            <p style="white-space:pre-wrap;overflow-wrap:anywhere">{{ release.notes }}</p>
            <el-button v-if="release.available" type="primary" :icon="Download" :loading="busy" :disabled="active" @click="install">安装更新</el-button>
            <el-alert v-if="release.available && active" type="warning" :closable="false" title="请先停用插件或切换其他主题" class="mt-3" />
        </template>
    </el-dialog>
</template>
