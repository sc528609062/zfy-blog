<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Refresh, Download } from '@element-plus/icons-vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { adminRequest } from './adminRequest';
const props = defineProps<{ csrf: string }>();
const data = ref<Record<string, any>>({ pending: [], backups: [], logs: [] });
const busy = ref(false);
const release = ref<Record<string, any> | null>(null);
const packageFile = ref<File>();
const manifestFile = ref<File>();
const signatureFile = ref<File>();
async function saveSource() {
    try { await adminRequest('/admin/maintenance/source', props.csrf, 'PUT', { provider: data.value.update_source.provider, repository: data.value.update_source.repository }); release.value = null; ElMessage.success('更新源已保存'); }
    catch (error) { ElMessage.error((error as Error).message); }
}
async function offline() {
    if (!packageFile.value || !manifestFile.value || !signatureFile.value) return;
    try {
        await ElMessageBox.confirm('校验签名并应用此离线更新包？', '离线更新', { type: 'warning' });
        busy.value = true;
        const body = new FormData(); body.append('package', packageFile.value); body.append('manifest', manifestFile.value); body.append('signature', signatureFile.value);
        const response = await fetch('/admin/maintenance/offline', { method: 'POST', body, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': props.csrf, 'X-Requested-With': 'XMLHttpRequest' } });
        const result = await response.json(); if (!response.ok) throw new Error(result.message);
        ElMessage.success(result.message); await load();
    } catch (error) { if (error instanceof Error) ElMessage.error(error.message); }
    finally { busy.value = false; }
}
async function checkUpdate() {
    busy.value = true;
    try { release.value = (await adminRequest('/admin/maintenance/check-update', props.csrf, 'POST')).data; }
    catch (error) { ElMessage.error((error as Error).message); }
    finally { busy.value = false; }
}
async function load() {
    try { data.value = (await adminRequest('/admin/maintenance/status', props.csrf)).data; }
    catch (error) { ElMessage.error((error as Error).message); }
}
async function run(action: string) {
    if (action === 'migrate' || action === 'install-update') {
        try { await ElMessageBox.confirm('备份当前数据库并执行待应用的更新？', '数据库更新', { type: 'warning' }); }
        catch { return; }
    }
    busy.value = true;
    try {
        const result = await adminRequest(`/admin/maintenance/${action}`, props.csrf, 'POST');
        ElMessage.success(result.message); await load();
    } catch (error) { ElMessage.error((error as Error).message); }
    finally { busy.value = false; }
}
onMounted(load);
</script>
<template>
    <section>
        <h2>系统版本 {{ data.version }}</h2>
        <p v-if="data.update_checks">最近定时检查：{{ data.update_checks.checked_at }}</p>
        <el-button :icon="Refresh" :loading="busy" @click="run('rebuild')">重建搜索索引与模板缓存</el-button>
        <el-form v-if="data.update_source" inline label-position="top">
            <el-form-item label="托管平台"><el-select v-model="data.update_source.provider" style="width:130px"><el-option label="Gitee" value="gitee" /><el-option label="GitHub" value="github" /></el-select></el-form-item>
            <el-form-item label="仓库"><el-input v-model="data.update_source.repository" style="width:280px" /></el-form-item>
            <el-form-item label=" "><el-button @click="saveSource">保存更新源</el-button></el-form-item>
        </el-form>
        <el-alert v-if="data.update_source && !data.update_source.configured" title="尚未配置发布签名公钥" type="warning" :closable="false" />
        <el-space wrap><span>{{ data.update_source?.provider }} / {{ data.update_source?.repository }}</span><el-button :icon="Refresh" :loading="busy" @click="checkUpdate">检查更新</el-button><el-button v-if="release" type="primary" :disabled="release.version === data.version || !data.update_source?.configured" :loading="busy" @click="run('install-update')">更新到 {{ release.version }}</el-button></el-space>
        <p v-if="release">{{ release.notes }}</p>
        <el-collapse><el-collapse-item title="离线更新" name="offline"><el-form label-position="top">
            <el-form-item label="安装包 package.zip"><input type="file" accept=".zip" @change="packageFile = ($event.target as HTMLInputElement).files?.[0]"></el-form-item>
            <el-form-item label="发布清单 release.json"><input type="file" accept=".json" @change="manifestFile = ($event.target as HTMLInputElement).files?.[0]"></el-form-item>
            <el-form-item label="签名 release.sig"><input type="file" accept=".sig" @change="signatureFile = ($event.target as HTMLInputElement).files?.[0]"></el-form-item>
            <el-button :disabled="!packageFile || !manifestFile || !signatureFile || !data.update_source?.configured" :loading="busy" @click="offline">校验并更新</el-button>
        </el-form></el-collapse-item></el-collapse>
        <el-table v-if="data.updates?.length" :data="data.updates"><el-table-column prop="version" label="目标版本" /><el-table-column prop="status" label="更新状态" /><el-table-column prop="error" label="详情" /></el-table>
        <el-space wrap><el-button :icon="Refresh" :disabled="busy" @click="load">刷新</el-button><el-button :icon="Download" :loading="busy" @click="run('backup')">备份数据库</el-button><el-button type="primary" :disabled="!data.pending.length" :loading="busy" @click="run('migrate')">应用数据库更新</el-button></el-space>
        <h3>待更新 {{ data.pending.length }}</h3><ul><li v-for="migration in data.pending" :key="migration">{{ migration }}</li></ul>
        <h3>数据库备份</h3><el-table :data="data.backups"><el-table-column prop="name" label="文件" /><el-table-column prop="size" label="字节" width="130" /><el-table-column label="下载" width="90"><template #default="{ row }"><a :href="`/admin/maintenance/backups/${encodeURIComponent(row.name)}`" aria-label="下载备份"><el-icon><Download /></el-icon></a></template></el-table-column></el-table>
        <h3>主题与插件版本</h3><el-table :data="[...(data.themes || []), ...(data.plugins || [])]"><el-table-column prop="name" label="名称" /><el-table-column prop="version" label="版本" /></el-table>
        <h3>更新记录</h3><el-table :data="data.logs"><el-table-column prop="to_version" label="版本" /><el-table-column prop="status" label="状态" /><el-table-column prop="updated_at" label="时间" /></el-table>
        <h3>扩展更新任务</h3><el-table :data="data.extension_updates || []"><el-table-column prop="slug" label="扩展" /><el-table-column prop="version" label="版本" /><el-table-column prop="status" label="状态" /><el-table-column prop="error" label="错误" /></el-table>
        <el-collapse><el-collapse-item v-for="kind in ['hooks', 'filters']" :key="kind" :title="kind === 'hooks' ? '动作钩子诊断' : '过滤器诊断'" :name="kind">
            <el-table :data="Object.entries(data[kind]?.hooks || {}).map(([name, listeners]) => ({ name, count: (listeners as any[]).length, calls: data[kind]?.counts?.[name] || 0 }))"><el-table-column prop="name" label="名称" /><el-table-column prop="count" label="监听数" width="90" /><el-table-column prop="calls" label="本次调用" width="100" /></el-table>
            <el-table v-if="data[kind]?.failures?.length" :data="data[kind].failures"><el-table-column prop="hook" label="失败钩子" /><el-table-column prop="exception" label="异常" /></el-table>
        </el-collapse-item></el-collapse>
    </section>
</template>
