<script setup lang="ts">
import { computed, inject, onBeforeUnmount, onMounted, ref, type ShallowRef } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Brush, Check, RefreshLeft, Search, View } from '@element-plus/icons-vue';
import { adminRequest } from './adminRequest';
import ExtensionUpdate from './ExtensionUpdate.vue';
import CoverImageField from '../editor/CoverImageField.vue';

const props = defineProps<{ csrf: string }>();
const beforeLeave = inject<ShallowRef<(() => Promise<boolean>) | null>>('admin-before-leave');
const themes = ref<Record<string, any>[]>([]);
const groups = ref<{ key: string; label: string }[]>([]);
const selected = ref<number>();
const group = ref('brand');
const query = ref('');
const loading = ref(true);
const saving = ref(false);
const failed = ref(false);
const theme = computed(() => themes.value.find(item => item.id === selected.value));
const isDirty = (item: Record<string, any>) => JSON.stringify(item.values) !== item.saved;
const dirty = computed(() => themes.value.some(isDirty));
const availableGroups = computed(() => groups.value.filter(item => theme.value?.fields.some((field: any) => field.group === item.key)));
const visibleGroups = computed(() => availableGroups.value.filter(item => query.value || group.value === item.key));
const matches = (field: Record<string, any>) => !query.value.trim() || `${field.label} ${field.key}`.toLowerCase().includes(query.value.trim().toLowerCase());
const fieldsFor = (key: string) => (theme.value?.fields || []).filter((field: any) => field.group === key && matches(field));
const clone = (value: unknown) => JSON.parse(JSON.stringify(value));

async function load() {
    loading.value = true;
    failed.value = false;
    try {
        const result = await adminRequest('/admin/themes/configuration', props.csrf);
        groups.value = result.groups;
        themes.value = result.data.map((item: any) => ({ ...item, saved: JSON.stringify(item.values) }));
        selected.value = themes.value.find(item => item.is_active)?.id || themes.value[0]?.id;
        selectTheme();
    } catch (error) { failed.value = true; ElMessage.error((error as Error).message); }
    finally { loading.value = false; }
}
function selectTheme() {
    group.value = availableGroups.value[0]?.key || 'extension';
    query.value = '';
}
async function reset(currentGroup?: string) {
    const target = theme.value;
    if (!target) return;
    const label = currentGroup ? groups.value.find(item => item.key === currentGroup)?.label : target.name;
    try {
        await ElMessageBox.confirm(`将“${label}”恢复为默认配置，保存后生效。`, '恢复默认配置', { type: 'warning', confirmButtonText: '恢复默认', cancelButtonText: '取消' });
        for (const field of target.fields) {
            if (!currentGroup || field.group === currentGroup) target.values[field.key] = clone(target.defaults[field.key] ?? null);
        }
        ElMessage.success('默认值已回填');
    } catch (error) { if (error instanceof Error) ElMessage.error(error.message); }
}
async function save() {
    const target = theme.value;
    if (!target) return;
    saving.value = true;
    const values = clone(target.values);
    try {
        await adminRequest(`/admin/themes/${target.id}/configuration`, props.csrf, 'PUT', values);
        target.saved = JSON.stringify(values);
        ElMessage.success('主题配置已保存');
    } catch (error) { ElMessage.error((error as Error).message); }
    finally { saving.value = false; }
}
async function preview() {
    if (!theme.value) return;
    const target = window.open('', '_blank');
    try {
        const result = await adminRequest(`/admin/themes/${theme.value.id}/preview`, props.csrf, 'POST', theme.value.values);
        if (target) { target.opener = null; target.location.href = result.url; }
    } catch (error) { target?.close(); ElMessage.error((error as Error).message); }
}
async function uninstall() {
    const target = theme.value;
    if (!target) return;
    try {
        await ElMessageBox.confirm('卸载主题并保留配置？', target.name, { type: 'warning', confirmButtonText: '卸载', cancelButtonText: '取消' });
        await adminRequest(`/admin/themes/${target.id}/installation`, props.csrf, 'DELETE');
        target.installed = false;
        ElMessage.success('主题已卸载');
    } catch (error) { if (error instanceof Error) ElMessage.error(error.message); }
}
async function purge() {
    const target = theme.value;
    if (!target) return;
    try {
        const { value } = await ElMessageBox.prompt(`输入主题标识 ${target.slug} 以清理配置`, '清理已卸载主题', { inputValidator: value => value === target.slug || '主题标识不匹配' });
        await adminRequest(`/admin/themes/${target.id}/data`, props.csrf, 'DELETE', { confirm_slug: value });
        themes.value = themes.value.filter(item => item.id !== target.id);
        selected.value = themes.value[0]?.id;
        selectTheme();
    } catch (error) { if (error instanceof Error) ElMessage.error(error.message); }
}
function beforeUnload(event: BeforeUnloadEvent) { if (dirty.value) { event.preventDefault(); event.returnValue = ''; } }
onMounted(() => { void load(); window.addEventListener('beforeunload', beforeUnload); });
onBeforeUnmount(() => {
    window.removeEventListener('beforeunload', beforeUnload);
    if (beforeLeave) beforeLeave.value = null;
});
async function confirmLeave() {
    if (!dirty.value) return true;
    try {
        await ElMessageBox.confirm('主题配置尚未保存，确定离开？', '未保存的更改', { confirmButtonText: '离开', cancelButtonText: '继续编辑', type: 'warning' });
        return true;
    } catch { return false; }
}
if (beforeLeave) beforeLeave.value = confirmLeave;
</script>

<template>
    <section class="theme-settings" v-loading="loading">
        <div v-if="failed" class="theme-settings-empty"><el-button :icon="RefreshLeft" @click="load">重新加载</el-button></div>
        <template v-else-if="theme">
            <header class="theme-settings-toolbar">
                <el-select v-model="selected" aria-label="选择主题" @change="selectTheme">
                    <el-option v-for="item in themes" :key="item.id" :value="item.id" :label="item.name" />
                </el-select>
                <el-tag :type="theme.is_active ? 'success' : 'info'">{{ theme.is_active ? '当前启用' : theme.installed ? '未启用' : '已卸载' }}</el-tag>
                <el-input v-model="query" :prefix-icon="Search" clearable aria-label="搜索主题设置" placeholder="搜索设置" />
            </header>
            <div class="theme-settings-workspace">
                <nav class="theme-settings-nav" aria-label="主题设置分组">
                    <button v-for="item in availableGroups" :key="item.key" :class="{ active: item.key === group && !query }" @click="group = item.key; query = ''"><el-icon><Brush /></el-icon>{{ item.label }}</button>
                </nav>
                <el-form class="theme-settings-form" label-position="top" :disabled="saving || !theme.installed">
                    <section v-for="item in visibleGroups.filter(item => fieldsFor(item.key).length)" :key="item.key" class="theme-settings-group">
                        <header><h2>{{ item.label }}</h2><el-button :icon="RefreshLeft" text :disabled="saving || !theme.installed" @click="reset(item.key)">重置此分组</el-button></header>
                        <div v-for="field in fieldsFor(item.key)" :key="field.key" :class="['theme-setting-row', { 'is-switch': field.type === 'boolean' }]" v-show="!field.depends_on || theme.values[field.depends_on]">
                            <el-form-item :label="field.label">
                                <el-switch v-if="field.type === 'boolean'" v-model="theme.values[field.key]" :aria-label="field.label" />
                                <el-color-picker v-else-if="field.type === 'color'" v-model="theme.values[field.key]" :aria-label="field.label" />
                                <el-input-number v-else-if="field.type === 'number'" v-model="theme.values[field.key]" :min="field.min" :max="field.max" :step="field.step || 1" :aria-label="field.label" />
                                <el-select v-else-if="field.type === 'select'" v-model="theme.values[field.key]" :aria-label="field.label"><el-option v-for="(label, key) in field.options" :key="key" :label="String(label)" :value="key" /></el-select>
                                <CoverImageField v-else-if="field.type === 'image'" :model-value="theme.values[field.key] ?? ''" @update:model-value="theme.values[field.key] = $event" />
                                <el-input v-else v-model="theme.values[field.key]" :type="field.type === 'textarea' || field.rows ? 'textarea' : 'text'" :rows="field.rows || 3" :aria-label="field.label" :placeholder="field.placeholder" maxlength="4000" />
                            </el-form-item>
                        </div>
                    </section>
                    <el-empty v-if="!visibleGroups.some(item => fieldsFor(item.key).length)" description="没有匹配的设置" />
                </el-form>
            </div>
            <footer class="configuration-actions">
                <span :class="{ dirty: isDirty(theme) }">{{ isDirty(theme) ? '有未保存的更改' : '已保存' }}</span>
                <el-button :icon="RefreshLeft" :disabled="!theme.installed || saving" @click="reset()">恢复默认</el-button>
                <el-button :icon="View" :disabled="!theme.installed || saving" @click="preview">预览</el-button>
                <el-button type="primary" :icon="Check" :loading="saving" :disabled="!theme.installed" @click="save">保存配置</el-button>
            </footer>
            <div class="theme-settings-maintenance">
                <ExtensionUpdate v-if="theme.installed" :key="theme.id" type="theme" :slug="theme.slug" :csrf="csrf" :active="theme.is_active" />
                <el-button v-if="theme.installed && !theme.is_active" type="danger" plain :disabled="saving" @click="uninstall">卸载主题</el-button>
                <el-button v-if="!theme.installed" type="danger" plain @click="purge">清理配置</el-button>
            </div>
        </template>
        <el-empty v-else-if="!loading" description="暂无主题" />
    </section>
</template>

<style scoped>
.theme-settings { min-width: 0; width: 100%; }
.theme-settings-toolbar { display: flex; align-items: center; gap: 14px; padding: 16px 0 24px; border-bottom: 1px solid var(--zfy-admin-line); }
.theme-settings-toolbar > .el-select { width: 270px; max-width: 100%; }
.theme-settings-toolbar > .el-input { max-width: 280px; margin-left: auto; }
.theme-settings-workspace { display: grid; grid-template-columns: 170px minmax(0, 1fr); gap: 32px; align-items: start; }
.theme-settings-nav { display: grid; gap: 4px; padding-top: 24px; position: sticky; top: 76px; }
.theme-settings-nav button { display: flex; align-items: center; gap: 12px; min-height: 44px; border: 0; border-radius: 6px; padding: 10px 14px; background: transparent; color: var(--zfy-admin-text); text-align: left; cursor: pointer; }
.theme-settings-nav button.active { color: var(--zfy-admin-primary); background: var(--zfy-admin-primary-soft); }
.theme-settings-form { width: 100%; min-width: 0; max-width: 860px; padding: 20px 0; }
.theme-settings-group > header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; gap: 10px; }
.theme-settings-group h2 { font-size: 17px; font-weight: 600; margin: 0; }
.theme-setting-row { padding: 18px 0; border-bottom: 1px solid var(--zfy-admin-line); }
.theme-setting-row :deep(.el-form-item) { margin: 0; }
.theme-setting-row :deep(.el-form-item__content) { max-width: 620px; }
.theme-setting-row.is-switch :deep(.el-form-item) { display: flex; align-items: center; justify-content: space-between; }
.theme-setting-row.is-switch :deep(.el-form-item__label) { margin: 0; }
.theme-setting-row.is-switch :deep(.el-form-item__content) { flex: none; }
.configuration-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; position: sticky; bottom: 0; z-index: 12; padding: 16px; border-top: 1px solid var(--zfy-admin-line); background: var(--zfy-admin-surface); }
.configuration-actions > span { margin-right: auto; font-size: 13px; color: var(--zfy-admin-muted); }
.configuration-actions > span.dirty { color: #d17b12; }
.configuration-actions .el-button { margin: 0; }
.theme-settings-maintenance { display: flex; gap: 12px; align-items: center; padding-top: 24px; }
@media (max-width: 760px) {
    .theme-settings-toolbar { flex-wrap: wrap; gap: 10px; }
    .theme-settings-toolbar > .el-select { flex: 1; min-width: 180px; }
    .theme-settings-toolbar > .el-input { max-width: none; flex-basis: 100%; }
    .theme-settings-workspace { display: block; }
    .theme-settings-nav { position: static; display: flex; overflow-x: auto; padding-top: 16px; gap: 6px; }
    .theme-settings-nav button { flex: none; }
    .configuration-actions { padding: 12px 0; }
    .configuration-actions > span { flex-basis: 100%; }
    .theme-settings-group h2 { font-size: 16px; }
}
</style>
