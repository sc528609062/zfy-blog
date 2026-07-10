<script setup lang="ts">
import type { EditorOutlineItem } from './useEditorOutline';

defineProps<{
    items: EditorOutlineItem[];
}>();

const emit = defineEmits<{
    select: [item: EditorOutlineItem];
}>();
</script>

<template>
    <aside class="zfy-editor-outline" aria-label="文章目录">
        <div class="zfy-editor-outline__head">文章目录</div>
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
