<script setup lang="ts">
import { NodeViewContent, NodeViewWrapper, nodeViewProps } from '@tiptap/vue-3';
const props = defineProps(nodeViewProps);
const rules = { member: '登录可见', vip: 'VIP 可见', purchased: '购买可见', comment: '评论后可见', password: '内容密码可见' };
</script>
<template>
    <NodeViewWrapper class="restricted-block">
        <div class="restricted-controls" contenteditable="false">
            <el-select :model-value="node.attrs.rule" aria-label="访问条件" style="width:150px" @change="props.updateAttributes({ rule: $event })"><el-option v-for="(label, value) in rules" :key="value" :label="label" :value="value" /></el-select>
            <el-input :model-value="node.attrs.label" aria-label="无权限时显示的文字" maxlength="150" @update:model-value="props.updateAttributes({ label: $event })" />
        </div>
        <NodeViewContent class="restricted-content" />
    </NodeViewWrapper>
</template>
<style scoped>
.restricted-block{border:1px dashed var(--el-color-warning);padding:12px;margin:12px 0;border-radius:4px}.restricted-controls{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px}.restricted-controls>.el-input{flex:1;min-width:160px}.restricted-content{min-height:50px}
</style>
