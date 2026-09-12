<script setup lang="ts">
import { ref } from 'vue';
import { ElMessage } from 'element-plus';
import { adminRequest } from './adminRequest';
import LayoutBlocks from './LayoutBlocks.vue';
const props = defineProps<{ layouts: Record<string, any>[]; csrf: string; blocks?: Record<string, any>[] }>();
const layouts = ref<(Record<string, any> & { data: { blocks: Record<string, any>[] } })[]>(props.layouts.map((layout) => ({ ...layout, data: typeof layout.schema === 'string' ? JSON.parse(layout.schema || '{"blocks":[]}') : (layout.schema || { blocks: [] }) })));
const active = ref(layouts.value[0]?.id);
const saving = ref(false);
async function save(layout: Record<string, any>) {
    saving.value = true;
    try {
        await adminRequest(layout.save_url, props.csrf, 'POST', { title: layout.title, status: layout.status, schema: JSON.stringify(layout.data) });
        ElMessage.success('布局已保存');
    } catch (error) { ElMessage.error((error as Error).message); }
    finally { saving.value = false; }
}
</script>
<template>
    <el-tabs v-model="active">
        <el-tab-pane v-for="layout in layouts" :key="layout.id" :name="layout.id" :label="layout.title">
            <el-form label-position="top">
                <el-form-item label="布局名称"><el-input v-model="layout.title" /></el-form-item>
                <el-form-item label="状态"><el-radio-group v-model="layout.status"><el-radio-button value="draft">草稿</el-radio-button><el-radio-button value="published">发布</el-radio-button></el-radio-group></el-form-item>
                <LayoutBlocks v-model="layout.data.blocks" :definitions="blocks || []" />
                <el-button type="primary" :loading="saving" @click="save(layout)">保存布局</el-button>
            </el-form>
        </el-tab-pane>
    </el-tabs>
</template>
