<script setup lang="ts">
import { computed } from 'vue';
import { Plus, Delete, ArrowUp, ArrowDown } from '@element-plus/icons-vue';
import ExtensionFields from './ExtensionFields.vue';
const props = withDefaults(defineProps<{ modelValue: Record<string, any>[]; definitions: Record<string, any>[]; depth?: number }>(), { depth: 1 });
const emit = defineEmits<{ 'update:modelValue': [value: Record<string, any>[]] }>();
const types = computed(() => ({ ...(props.depth < 4 ? { container: '容器' } : {}), hero: '横幅', 'content-feed': '内容列表', rank: '排行榜', vip: '会员', html: 'HTML', ...Object.fromEntries(props.definitions.map(block => [block.key, block.label])) }));
const definition = (type: string) => props.definitions.find(block => block.key === type);
function changeType(index: number, type: string) {
    const next = [...props.modelValue];
    next[index] = { type, title: next[index].title || '', ...(definition(type)?.defaults || {}), ...(type === 'container' ? { children: [], columns: 2, mobile_columns: 1, gap: 20 } : {}) };
    emit('update:modelValue', next);
}
function move(index: number, offset: number) {
    const next = [...props.modelValue];
    [next[index], next[index + offset]] = [next[index + offset], next[index]];
    emit('update:modelValue', next);
}
function add(type: string) {
    emit('update:modelValue', [...props.modelValue, { type, title: '', ...(type === 'container' ? { children: [], columns: 2, mobile_columns: 1, gap: 20 } : {}) }]);
}
</script>
<template>
    <div :class="['layout-blocks', { nested: depth > 1 }]">
        <section v-for="(block, index) in modelValue" :key="index" class="builder-block">
            <div class="builder-actions">
                <el-select :model-value="block.type" aria-label="区块类型" @update:model-value="changeType(index, $event)"><el-option v-for="(label, value) in types" :key="value" :label="String(label)" :value="value" /></el-select>
                <el-button :icon="ArrowUp" :disabled="index === 0" aria-label="上移" title="上移" @click="move(index, -1)" />
                <el-button :icon="ArrowDown" :disabled="index === modelValue.length - 1" aria-label="下移" title="下移" @click="move(index, 1)" />
                <el-button :icon="Delete" aria-label="删除区块" title="删除区块" @click="emit('update:modelValue', modelValue.filter((_, i) => i !== index))" />
            </div>
            <el-form-item label="标题"><el-input v-model="block.title" maxlength="180" /></el-form-item>
            <el-form-item v-if="block.type === 'hero'" label="副标题"><el-input v-model="block.subtitle" maxlength="500" /></el-form-item>
            <el-form-item v-if="block.type === 'html'" label="HTML"><el-input v-model="block.html" type="textarea" :rows="6" /></el-form-item>
            <ExtensionFields :model-value="block" :fields="(definition(block.type)?.fields || []).filter((field: any) => field.key !== 'title')" @update:model-value="Object.assign(block, $event)" />
            <div class="builder-responsive">
                <el-form-item label="显示设备"><el-select v-model="block.visibility" placeholder="所有设备"><el-option label="所有设备" value="all" /><el-option label="仅桌面" value="desktop" /><el-option label="仅手机" value="mobile" /></el-select></el-form-item>
                <el-form-item label="访问条件"><el-select v-model="block.access" placeholder="公开"><el-option label="公开" value="public" /><el-option label="登录可见" value="member" /><el-option label="VIP 可见" value="vip" /></el-select></el-form-item>
                <template v-if="['container', 'content-feed', 'rank'].includes(block.type)"><el-form-item label="桌面列数"><el-input-number v-model="block.columns" :min="1" :max="6" /></el-form-item><el-form-item label="手机列数"><el-input-number v-model="block.mobile_columns" :min="1" :max="2" /></el-form-item></template>
                <el-form-item v-if="block.type === 'container'" label="间距"><el-input-number v-model="block.gap" :min="0" :max="64" /></el-form-item>
            </div>
            <LayoutBlocks v-if="block.type === 'container'" v-model="block.children" :definitions="definitions" :depth="depth + 1" />
        </section>
        <div class="builder-add"><el-button :icon="Plus" @click="add('content-feed')">添加区块</el-button><el-button v-if="depth < 4" :icon="Plus" @click="add('container')">添加容器</el-button></div>
    </div>
</template>
<style scoped>
.builder-block{border-bottom:1px solid var(--zfy-admin-line);padding:16px 0;margin-bottom:16px;min-width:0}.builder-actions,.builder-responsive,.builder-add{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px}.builder-actions>.el-select{width:180px}.builder-actions>.el-button,.builder-add>.el-button{margin-left:0}.builder-responsive{gap:16px}.builder-responsive .el-select{width:160px}.nested{border-left:2px solid var(--zfy-admin-line);padding-left:16px}@media(max-width:760px){.nested{padding-left:8px}.builder-actions>.el-select{width:150px}}
</style>
