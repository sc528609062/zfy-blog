<script setup lang="ts">
import { ElMessage } from 'element-plus';
import { nextTick, watch } from 'vue';

const props = defineProps<{
    html: string;
    visible: boolean;
    loading?: boolean;
}>();

watch(
    () => props.html,
    () => {
        void nextTick(() => {
            document.querySelectorAll<HTMLElement>('.zfy-editor-preview .zfy-shortcode-tabs').forEach((tabs) => {
                const activeHead = tabs.querySelector('.zfy-tabs-head-item.is-active');
                const activeBody = tabs.querySelector('.zfy-tabs-body-item.is-active');

                if (!activeHead) {
                    tabs.querySelector('.zfy-tabs-head-item')?.classList.add('is-active');
                }

                if (!activeBody) {
                    tabs.querySelector('.zfy-tabs-body-item')?.classList.add('is-active');
                }
            });
        });
    },
    { immediate: true },
);

async function copyText(text: string): Promise<void> {
    if (!text.trim()) {
        return;
    }

    try {
        await navigator.clipboard.writeText(text.trim());
        ElMessage.success('已复制');
    } catch {
        ElMessage.error('复制失败，请手动选择复制');
    }
}

function handlePreviewClick(event: MouseEvent): void {
    const target = event.target as HTMLElement | null;
    if (!target) {
        return;
    }

    const tabHead = target.closest<HTMLElement>('.zfy-tabs-head-item');
    if (tabHead) {
        const tabs = tabHead.closest<HTMLElement>('.zfy-shortcode-tabs');
        const heads = [...(tabs?.querySelectorAll<HTMLElement>('.zfy-tabs-head-item') || [])];
        const bodies = [...(tabs?.querySelectorAll<HTMLElement>('.zfy-tabs-body-item') || [])];
        const index = heads.indexOf(tabHead);

        heads.forEach((item, itemIndex) => item.classList.toggle('is-active', itemIndex === index));
        bodies.forEach((item, itemIndex) => item.classList.toggle('is-active', itemIndex === index));
        return;
    }

    const collapseTitle = target.closest<HTMLElement>('.zfy-shortcode-collapse .zfy-shortcode-title');
    if (collapseTitle) {
        collapseTitle.closest<HTMLElement>('.zfy-collapse-item')?.classList.toggle('is-open');
        return;
    }

    const copyTrigger = target.closest<HTMLElement>('.zfy-copy-trigger');
    if (copyTrigger) {
        const copyBlock = copyTrigger.closest<HTMLElement>('.zfy-shortcode-copy');
        void copyText(copyBlock?.querySelector<HTMLElement>('.zfy-copy-body')?.innerText || '');
    }
}
</script>

<template>
    <aside v-if="visible" class="zfy-editor-preview-pane" @click="handlePreviewClick">
        <el-skeleton v-if="loading && !html" :rows="8" animated />
        <article v-else class="zfy-editor-preview" v-html="html || '<p>暂无预览内容</p>'" />
    </aside>
</template>
