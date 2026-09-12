<script setup lang="ts">
import { reactive, ref } from 'vue';
import { ElMessage } from 'element-plus';
import { Delete, Upload } from '@element-plus/icons-vue';
import { adminRequest } from '../components/adminRequest';
const props = defineProps<{ contentId: number; content?: Record<string, any>; csrf: string; canManageCommerce: boolean }>();
const form = reactive({ price: Number(props.content?.pricing?.price ?? 0), vip_free: Boolean(props.content?.access_rules?.vip_free), downloads_per_day: Number(props.content?.access_rules?.downloads_per_day ?? 0), downloads_total: Number(props.content?.access_rules?.downloads_total ?? 0), visibility: props.content?.access_rules?.visibility || 'public', password: '', seo_title: props.content?.seo?.title || '', seo_description: props.content?.seo?.description || '' });
const attachments = ref<Record<string, any>[]>(props.content?.attachments || []);
const busy = ref(false);
const picker = ref<HTMLInputElement>();
async function save() {
    busy.value = true;
    try { const { price, vip_free, ...metadata } = form; await adminRequest(`/admin/contents/${props.contentId}/access`, props.csrf, 'PUT', props.canManageCommerce ? form : metadata); ElMessage.success('访问设置已保存'); }
    catch (error) { ElMessage.error((error as Error).message); }
    finally { busy.value = false; }
}
async function upload(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;
    busy.value = true;
    try {
        const body = new FormData(); body.append('file', file);
        const response = await fetch(`/admin/contents/${props.contentId}/attachments`, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': props.csrf, 'X-Requested-With': 'XMLHttpRequest' }, body });
        const result = await response.json();
        if (!response.ok) throw new Error(Object.values(result.errors || {}).flat().join('\n') || result.message);
        attachments.value.push(result.attachment);
        ElMessage.success('附件已上传');
    } catch (error) { ElMessage.error((error as Error).message); }
    finally { busy.value = false; input.value = ''; }
}
async function remove(id: number) {
    try { await adminRequest(`/admin/contents/${props.contentId}/attachments/${id}`, props.csrf, 'DELETE'); attachments.value = attachments.value.filter((item) => item.id !== id); }
    catch (error) { ElMessage.error((error as Error).message); }
}
</script>
<template>
    <section style="padding:20px 0">
        <h2 style="font-size:16px;margin-bottom:16px">购买与下载</h2>
        <el-form label-position="top" inline>
            <el-form-item v-if="canManageCommerce" label="售价"><el-input-number v-model="form.price" :min="0" :precision="2" /></el-form-item>
            <el-form-item v-if="canManageCommerce" label="VIP 免费"><el-switch v-model="form.vip_free" /></el-form-item>
            <el-form-item label="访问范围"><el-select v-model="form.visibility" style="width:180px"><el-option v-for="(label, value) in {public:'公开',member:'登录可见',vip:'VIP 可见',comment:'评论后可见',password:'密码可见'}" :key="value" :label="label" :value="value" /></el-select></el-form-item>
            <el-form-item label="内容密码（留空保留）"><el-input v-model="form.password" type="password" autocomplete="new-password" show-password /></el-form-item>
            <el-form-item label="每日下载上限（0 为不限）"><el-input-number v-model="form.downloads_per_day" :min="0" :max="10000" :precision="0" /></el-form-item>
            <el-form-item label="总下载上限（0 为不限）"><el-input-number v-model="form.downloads_total" :min="0" :max="100000" :precision="0" /></el-form-item>
            <el-form-item label="SEO 标题"><el-input v-model="form.seo_title" maxlength="180" /></el-form-item>
            <el-form-item label="SEO 描述"><el-input v-model="form.seo_description" type="textarea" maxlength="300" /></el-form-item>
            <el-form-item><el-button :loading="busy" @click="save">保存访问设置</el-button></el-form-item>
        </el-form>
        <div v-for="attachment in attachments" :key="attachment.id">{{ attachment.name }} <el-button :icon="Delete" text title="移除附件" aria-label="移除附件" @click="remove(attachment.id)" /></div>
        <input ref="picker" type="file" hidden @change="upload">
        <el-button :icon="Upload" :loading="busy" @click="picker?.click()">上传下载附件</el-button>
    </section>
</template>
