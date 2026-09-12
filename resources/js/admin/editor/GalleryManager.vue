<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { ElMessage } from 'element-plus';
import { Upload, ArrowUp, ArrowDown, Delete } from '@element-plus/icons-vue';
import { adminRequest } from '../components/adminRequest';
const props = defineProps<{ contentId: number; csrf: string }>();
const emit = defineEmits<{ cover: [url: string] }>();
const items = ref<Record<string, any>[]>([]);
const busy = ref(false);
const picker = ref<HTMLInputElement>();
const coverId = ref<number | null>(null);
const endpoint = `/admin/contents/${props.contentId}/gallery`;
onMounted(async () => { try { items.value = (await adminRequest(endpoint, props.csrf)).items; } catch (error) { ElMessage.error((error as Error).message); } });
async function upload(event: Event) {
    const input = event.target as HTMLInputElement;
    busy.value = true;
    try {
        for (const file of Array.from(input.files || [])) {
            const body = new FormData(); body.append('file', file);
            const response = await fetch(endpoint, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': props.csrf, 'X-Requested-With': 'XMLHttpRequest' }, body });
            const result = await response.json();
            if (!response.ok) throw new Error(Object.values(result.errors || {}).flat().join('\n') || result.message);
            items.value = result.items;
        }
        ElMessage.success('图片已上传');
    } catch (error) { ElMessage.error((error as Error).message); }
    finally { input.value = ''; busy.value = false; }
}
async function save() {
    busy.value = true;
    try {
        await adminRequest(endpoint, props.csrf, 'PUT', { items: items.value, cover_id: coverId.value });
        if (coverId.value) emit('cover', `/gallery/${coverId.value}`);
        ElMessage.success('图集已保存');
    } catch (error) { ElMessage.error((error as Error).message); }
    finally { busy.value = false; }
}
function move(index: number, offset: number) {
    [items.value[index], items.value[index + offset]] = [items.value[index + offset], items.value[index]];
}
const dragIndex = ref<number | null>(null);
function drop(index: number) {
    if (dragIndex.value === null || dragIndex.value === index) return;
    items.value.splice(index, 0, ...items.value.splice(dragIndex.value, 1));
    dragIndex.value = null;
}
</script>
<template>
    <section class="gallery-manager">
        <h2>图集图片</h2>
        <input ref="picker" type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple hidden @change="upload">
        <div v-for="(item, index) in items" :key="item.id" class="gallery-item" draggable="true" @dragstart="dragIndex = index" @dragover.prevent @drop.prevent="drop(index)" @dragend="dragIndex = null">
            <img :src="item.url" :alt="item.title">
            <el-form label-position="top">
                <el-form-item label="图片标题"><el-input v-model="item.title" maxlength="180" /></el-form-item>
                <el-form-item label="图片说明"><el-input v-model="item.caption" type="textarea" maxlength="2000" /></el-form-item>
                <el-form-item label="版权"><el-input v-model="item.copyright" maxlength="255" /></el-form-item>
                <el-form-item label="可见范围"><el-select v-model="item.visibility"><el-option v-for="(label, value) in {public:'公开',member:'登录',vip:'VIP',purchased:'已购买',comment:'评论通过',password:'内容密码'}" :key="value" :value="value" :label="label" /></el-select></el-form-item>
                <el-checkbox v-model="item.original_download">允许下载原图</el-checkbox>
                <el-checkbox :model-value="coverId === item.id" :disabled="item.visibility !== 'public'" @update:model-value="coverId = $event ? item.id : null">设为封面</el-checkbox>
                <div class="gallery-actions"><el-button :icon="ArrowUp" :disabled="index === 0" aria-label="上移图片" title="上移图片" @click="move(index, -1)" /><el-button :icon="ArrowDown" :disabled="index === items.length - 1" aria-label="下移图片" title="下移图片" @click="move(index, 1)" /><el-button :icon="Delete" aria-label="移除图片" title="移除图片" @click="items.splice(index, 1)" /></div>
            </el-form>
        </div>
        <el-button :icon="Upload" :loading="busy" @click="picker?.click()">上传图片</el-button><el-button :loading="busy" @click="save">保存图集</el-button>
    </section>
</template>
<style scoped>
.gallery-manager{padding-block:24px}.gallery-manager h2{font-size:16px;margin-bottom:16px}.gallery-item{display:grid;grid-template-columns:160px minmax(0,1fr);gap:20px;padding-block:18px;border-bottom:1px solid var(--zfy-admin-line);margin-bottom:16px}.gallery-item>img{width:160px;height:140px;object-fit:contain;background:var(--zfy-admin-surface-soft)}.gallery-actions{display:flex;gap:8px;margin-top:12px}.gallery-actions .el-button{margin:0}@media(max-width:760px){.gallery-item{grid-template-columns:minmax(0,1fr)}.gallery-item>img{width:100%;height:160px}}
</style>
