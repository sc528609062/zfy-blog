<script setup lang="ts">
import { ref } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { RefreshLeft } from '@element-plus/icons-vue';
import { adminRequest } from './adminRequest';
import LayoutBlocks from './LayoutBlocks.vue';
const props = defineProps<{ layouts: Record<string, any>[]; csrf: string; blocks?: Record<string, any>[] }>();
const layouts = ref<(Record<string, any> & { data: { blocks: Record<string, any>[]; enabled: boolean } })[]>(props.layouts.map((layout) => ({ ...layout, data: { enabled: false, ...JSON.parse(typeof layout.schema === 'string' ? layout.schema : JSON.stringify(layout.schema || { blocks: [] })) } })));
const active = ref(layouts.value[0]?.id);
const saving = ref(false);
async function reset(layout: Record<string, any>) {
    try {
        await ElMessageBox.confirm(`将“${layout.title}”恢复为初始区块并关闭自定义布局，保存后使用主题原生页面。布局名称和发布状态保持当前值。`, '恢复默认布局', {
            type: 'warning', confirmButtonText: '恢复默认', cancelButtonText: '取消',
        });
        layout.data = JSON.parse(JSON.stringify(layout.default_schema));
        ElMessage.success('已恢复默认布局，请保存');
    } catch (error) { if (error instanceof Error) ElMessage.error(error.message); }
}
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
                <el-form-item label="启用自定义布局"><el-switch v-model="layout.data.enabled" /></el-form-item>
                <LayoutBlocks v-model="layout.data.blocks" :definitions="blocks || []" />
                <el-button type="primary" :loading="saving" @click="save(layout)">保存布局</el-button>
                <el-button :icon="RefreshLeft" :disabled="saving" @click="reset(layout)">恢复默认</el-button>
            </el-form>
        </el-tab-pane>
    </el-tabs>
</template>
