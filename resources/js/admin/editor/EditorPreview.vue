<script setup lang="ts">
import { ElMessage } from 'element-plus';
import { nextTick, useTemplateRef, watch } from 'vue';
import {
    copyEnlighterCode,
    getEnlighterBlock,
    openEnlighterWindow,
    pulseEnlighterButton,
    toggleEnlighterRaw,
} from '../../shared/enlighterBlocks';
import { mountZfyTimes } from '../../shared/zfyTime';

const props = defineProps<{
    html: string;
    visible: boolean;
    loading?: boolean;
}>();

const previewArticleRef = useTemplateRef<HTMLElement>('previewArticle');

watch(
    () => props.html,
    (_html, _previousHtml, onCleanup) => {
        let cleanupTimes: (() => void) | undefined;
        let disposed = false;

        void nextTick(() => {
            if (disposed) {
                return;
            }

            const preview = previewArticleRef.value;
            cleanupTimes = mountZfyTimes(preview || document);

            preview?.querySelectorAll<HTMLElement>('.zfy-shortcode-tabs').forEach((tabs) => {
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

        onCleanup(() => {
            disposed = true;
            cleanupTimes?.();
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

async function handlePreviewClick(event: MouseEvent): Promise<void> {
    const target = event.target as HTMLElement | null;
    if (!target) {
        return;
    }

    const enlighterButton = target.closest<HTMLElement>('.enlighter-btn');
    if (enlighterButton) {
        const block = getEnlighterBlock(enlighterButton);
        if (!block) {
            return;
        }

        if (enlighterButton.classList.contains('enlighter-btn-raw')) {
            toggleEnlighterRaw(block);
            return;
        }

        if (enlighterButton.classList.contains('enlighter-btn-copy')) {
            try {
                if (await copyEnlighterCode(block)) {
                    pulseEnlighterButton(enlighterButton);
                    ElMessage.success('已复制');
                }
            } catch {
                ElMessage.error('复制失败，请手动选择复制');
            }
            return;
        }

        if (enlighterButton.classList.contains('enlighter-btn-window')) {
            openEnlighterWindow(block);
            return;
        }
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
        <article ref="previewArticle" v-else class="zfy-editor-preview" v-html="html || '<p>暂无预览内容</p>'" />
    </aside>
</template>
