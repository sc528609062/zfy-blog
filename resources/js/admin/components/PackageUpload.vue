<script setup lang="ts">
import { ref } from 'vue';
import { Upload } from '@element-plus/icons-vue';
import { ElMessage } from 'element-plus';
const props = defineProps<{ type: 'theme' | 'plugin'; csrf: string }>();
const emit = defineEmits<{ installed: [] }>();
const picker = ref<HTMLInputElement>();
const busy = ref(false);
async function upload(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;
    busy.value = true;
    try {
        const body = new FormData();
        body.append('file', file);
        const response = await fetch(`/admin/${props.type}s/install`, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': props.csrf, 'X-Requested-With': 'XMLHttpRequest' }, body });
        const result = await response.json();
        if (!response.ok) throw new Error(Object.values(result.errors || {}).flat().join('\n') || result.message);
        ElMessage.success(result.message);
        emit('installed');
    } catch (error) { ElMessage.error((error as Error).message); }
    finally { busy.value = false; input.value = ''; }
}
</script>
<template>
    <div class="zfy-package-upload"><input ref="picker" type="file" accept=".zip" hidden @change="upload"><el-button :icon="Upload" :loading="busy" @click="picker?.click()">{{ type === 'theme' ? '安装主题' : '安装插件' }}</el-button></div>
</template>
