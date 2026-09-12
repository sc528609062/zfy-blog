<script setup lang="ts">
import { computed } from 'vue';
import { NodeViewWrapper, nodeViewProps } from '@tiptap/vue-3';
import ExtensionFields from '../components/ExtensionFields.vue';
const props = defineProps(nodeViewProps);
const definitions = props.extension.options.definitions || [];
const definition = computed(() => definitions.find((item: any) => item.key === props.node.attrs.key));
const values = computed(() => { try { return JSON.parse(props.node.attrs.values); } catch { return {}; } });
function update(value: Record<string, any>) { props.updateAttributes({ values: JSON.stringify(value) }); }
</script>
<template>
    <NodeViewWrapper class="extension-block" contenteditable="false">
        <strong>{{ definition?.label || node.attrs.key }}</strong>
        <el-form v-if="definition" label-position="top">
            <ExtensionFields :model-value="values" :fields="definition.fields" @update:model-value="update" />
        </el-form>
        <el-alert v-else title="组件扩展未启用，原始数据已保留" :closable="false" />
    </NodeViewWrapper>
</template>
<style scoped>.extension-block{border:1px solid var(--zfy-admin-line);padding:16px;margin:12px 0;border-radius:6px}.extension-block strong{display:block;margin-bottom:12px}</style>
