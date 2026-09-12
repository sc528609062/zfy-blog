<script setup lang="ts">
import { ArrowUp, ArrowDown, Delete, Plus } from '@element-plus/icons-vue';
type Option = { value: string | number; label: string };
type Field = { key: string; label: string; type?: string; fields?: Field[]; defaults?: Record<string, any>; max?: number; options?: Option[] | Record<string, string | Option>; unless?: string };
const props = defineProps<{ modelValue: Record<string, any>; fields: Field[] }>();
const emit = defineEmits<{ 'update:modelValue': [value: Record<string, any>] }>();
function update(key: string, value: unknown) { emit('update:modelValue', { ...props.modelValue, [key]: value }); }
function items(field: Field): Record<string, any>[] { return Array.isArray(props.modelValue[field.key]) ? props.modelValue[field.key] : []; }
function options(field: Field): Option[] { return Object.entries(field.options || {}).map(([key, option]) => typeof option === 'object' ? option : { value: key, label: option }); }
function changeItem(field: Field, index: number, value: Record<string, any>) { update(field.key, items(field).map((item, i) => i === index ? value : item)); }
function move(field: Field, index: number, offset: number) {
    const next = [...items(field)];
    [next[index], next[index + offset]] = [next[index + offset], next[index]];
    update(field.key, next);
}
</script>
<template>
    <template v-for="field in fields" :key="field.key">
        <section v-if="field.type === 'repeater'" class="extension-items" :aria-label="field.label">
            <div v-for="(item, index) in items(field)" :key="index" class="extension-item">
                <div class="extension-item-actions"><span>{{ field.label }} {{ index + 1 }}</span><el-button :icon="ArrowUp" :disabled="index === 0" aria-label="上移子项" title="上移子项" @click="move(field, index, -1)" /><el-button :icon="ArrowDown" :disabled="index === items(field).length - 1" aria-label="下移子项" title="下移子项" @click="move(field, index, 1)" /><el-button :icon="Delete" aria-label="删除子项" title="删除子项" @click="update(field.key, items(field).filter((_, i) => i !== index))" /></div>
                <ExtensionFields :model-value="item" :fields="field.fields || []" @update:model-value="changeItem(field, index, $event)" />
            </div>
            <el-button :icon="Plus" :disabled="items(field).length >= (field.max || 30)" @click="update(field.key, [...items(field), { ...field.defaults }])">添加{{ field.label }}</el-button>
        </section>
        <el-form-item v-else-if="!field.unless || !modelValue[field.unless]?.length" :label="field.label">
            <el-switch v-if="field.type === 'boolean'" :model-value="modelValue[field.key]" @update:model-value="update(field.key, $event)" />
            <el-input-number v-else-if="field.type === 'number'" :model-value="modelValue[field.key]" @update:model-value="update(field.key, $event)" />
            <el-color-picker v-else-if="field.type === 'color'" :model-value="modelValue[field.key]" @update:model-value="update(field.key, $event)" />
            <el-select v-else-if="field.type === 'select'" :model-value="modelValue[field.key]" @update:model-value="update(field.key, $event)"><el-option v-for="option in options(field)" :key="option.value" :value="option.value" :label="option.label" /></el-select>
            <el-input v-else :model-value="modelValue[field.key]" :type="field.type === 'textarea' ? 'textarea' : 'text'" @update:model-value="update(field.key, $event)" />
        </el-form-item>
    </template>
</template>
<style scoped>
.extension-items{width:100%;min-width:0;margin-bottom:18px}.extension-item{padding:12px 0;border-bottom:1px solid var(--zfy-admin-line);margin-bottom:12px;min-width:0}.extension-item-actions{display:flex;gap:4px;align-items:center;margin-bottom:12px}.extension-item-actions>span{flex:1;overflow-wrap:anywhere}.extension-item-actions>.el-button{margin:0}.extension-items :deep(.el-form-item__content){min-width:0}
</style>
