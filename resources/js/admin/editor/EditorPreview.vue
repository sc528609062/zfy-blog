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
import { mountJoeNeteasePlayers } from '../../shared/joeNeteasePlayer';
import { mountZfyTimes } from '../../shared/zfyTime';
import { copyTextToClipboard } from '../../shared/clipboard';
import { applyMarkdownPresentation } from '../../shared/markdownPresentation';

const props = defineProps<{
    html: string;
    visible: boolean;
    loading?: boolean;
    markdownTheme?: string;
    codeTheme?: string;
}>();

const previewArticleRef = useTemplateRef<HTMLElement>('previewArticle');

watch(
    () => [previewArticleRef.value, props.markdownTheme, props.codeTheme],
    (_value, _previousValue, onCleanup) => {
        if (previewArticleRef.value) {
            onCleanup(applyMarkdownPresentation(previewArticleRef.value, {
                markdownTheme: props.markdownTheme,
                codeTheme: props.codeTheme,
            }));
        }
    },
    { flush: 'post' },
);

watch(
    () => [props.html, previewArticleRef.value],
    (_value, _previousValue, onCleanup) => {
        let cleanupTimes: (() => void) | undefined;
        let cleanupNetease: (() => void) | undefined;
        let cleanupEnhancements: (() => void) | undefined;
        let disposed = false;
        const controller = new AbortController();

        void nextTick(async () => {
            if (disposed) {
                return;
            }

            const preview = previewArticleRef.value;
            if (!preview) return;
            cleanupTimes = mountZfyTimes(preview);
            cleanupNetease = mountJoeNeteasePlayers(preview);

            if (preview) {
                const { enhanceMarkdownContent } = await import('../../shared/markdownEnhancements');
                if (disposed) return;
                const cleanup = await enhanceMarkdownContent(preview, {
                    presentation: false,
                    signal: controller.signal,
                });

                if (disposed) {
                    cleanup();
                    return;
                }

                cleanupEnhancements = cleanup;
            }

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
            controller.abort();
            cleanupTimes?.();
            cleanupNetease?.();
            cleanupEnhancements?.();
        });
    },
    { flush: 'post' },
);

async function copyText(text: string): Promise<boolean> {
    if (!text.trim()) {
        return false;
    }

    try {
        const copied = await copyTextToClipboard(text.trim());
        if (copied) {
            ElMessage.success('已复制');
            return true;
        }

        ElMessage.error('复制失败，请手动选择复制');
    } catch {
        ElMessage.error('复制失败，请手动选择复制');
    }

    return false;
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

    const cloudPassword = target.closest<HTMLElement>('.zfy-cloud-password[data-copy-text]');
    if (cloudPassword) {
        const copied = await copyText(cloudPassword.dataset.copyText || '');
        if (copied) {
            cloudPassword.classList.add('is-copied');
            window.setTimeout(() => {
                cloudPassword.classList.remove('is-copied');
            }, 1200);
        }
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
        <article
            ref="previewArticle"
            v-else
            class="zfy-editor-preview markdown-body"
            v-html="html || '<p>暂无预览内容</p>'"
        />
    </aside>
</template>
