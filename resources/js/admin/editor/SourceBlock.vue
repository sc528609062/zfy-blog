<script setup lang="ts">
import { NodeViewWrapper, nodeViewProps } from '@tiptap/vue-3';
const props = defineProps(nodeViewProps);
</script>
<template>
    <NodeViewWrapper class="zfy-source-block">
        <textarea :value="node.attrs.source" aria-label="Markdown 内容" rows="10" @input="props.updateAttributes({ source: ($event.target as HTMLTextAreaElement).value })" />
    </NodeViewWrapper>
</template>
