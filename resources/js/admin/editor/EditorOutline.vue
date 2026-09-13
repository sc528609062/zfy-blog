<script setup lang="ts">
import type { EditorOutlineItem } from './useEditorOutline';
import { Close } from '@element-plus/icons-vue';

defineProps<{
    items: EditorOutlineItem[];
}>();

const emit = defineEmits<{
    select: [item: EditorOutlineItem];
    close: [];
}>();
</script>

<template>
    <aside class="zfy-editor-outline" aria-label="文章目录">
        <div class="zfy-editor-outline__head">
            <span>文章目录</span>
            <el-button :icon="Close" aria-label="关闭文章目录" title="关闭文章目录" text circle size="small" @click="emit('close')" />
        </div>
        <nav v-if="items.length" class="zfy-editor-outline__list">
            <button
                v-for="item in items"
                :key="item.id"
                :style="{ paddingLeft: `${12 + (item.level - 1) * 12}px` }"
                type="button"
                @click="emit('select', item)"
            >
                <span>{{ item.title }}</span>
            </button>
        </nav>
        <div v-else class="zfy-editor-outline__empty">暂无标题</div>
    </aside>
</template>
