<script setup lang="ts">
import { Close, FolderOpened, Picture } from '@element-plus/icons-vue';
import { computed, shallowRef, watch } from 'vue';
import EditorImageDialog from './EditorImageDialog.vue';
import type { EditorMediaConfig } from './types';

const props = defineProps<{
    routes?: Record<string, string>;
    media?: EditorMediaConfig;
}>();

const coverUrl = defineModel<string>({ required: true });

const mediaVisible = shallowRef(false);
const imageBroken = shallowRef(false);
const fallbackCover = '/assets/zfy/placeholders/cover-blue.svg';
const libraryUrl = computed(() => String(props.routes?.media_library || '/admin/media/library'));
const uploadUrl = computed(() => String(props.routes?.media_upload || '/admin/media/upload'));
const destroyUrl = computed(() => String(props.routes?.media_destroy || '/admin/media/__MEDIA__'));
const coverPreview = computed(() => coverUrl.value.trim());
const previewSrc = computed(() => {
    if (!coverPreview.value || imageBroken.value) {
        return fallbackCover;
    }

    return coverPreview.value;
});
const hasCover = computed(() => coverPreview.value !== '');

watch(coverPreview, () => {
    imageBroken.value = false;
});

function openMediaLibrary(): void {
    mediaVisible.value = true;
}

function clearCover(): void {
    coverUrl.value = '';
}

function handleImageError(): void {
    imageBroken.value = true;
}

function handleCoverSubmit(url: string): void {
    coverUrl.value = url.trim();
    mediaVisible.value = false;
}
</script>

<template>
    <div class="zfy-cover-field">
        <button
            class="zfy-cover-field__preview"
            type="button"
            @click="openMediaLibrary"
        >
            <img :alt="hasCover ? '封面预览' : '默认封面'" :src="previewSrc" @error="handleImageError">
            <span v-if="!hasCover" class="zfy-cover-field__empty">
                <el-icon><Picture /></el-icon>
                选择封面
            </span>
        </button>

        <div class="zfy-cover-field__control">
            <el-input v-model="coverUrl" placeholder="请选择媒体库图片或粘贴图片地址">
                <template #append>
                    <el-button :icon="FolderOpened" @click="openMediaLibrary">媒体库</el-button>
                </template>
            </el-input>
            <div class="zfy-cover-field__actions">
                <el-button :icon="FolderOpened" type="primary" plain @click="openMediaLibrary">选择图片</el-button>
                <el-button :disabled="!hasCover" :icon="Close" @click="clearCover">清空</el-button>
            </div>
        </div>

        <EditorImageDialog
            v-model:visible="mediaVisible"
            confirm-text="设为封面"
            output="url"
            title="选择封面"
            library-type="image"
            :destroy-url="destroyUrl"
            :library-url="libraryUrl"
            :media="media"
            :upload-url="uploadUrl"
            @submit="handleCoverSubmit"
        />
    </div>
</template>

<style scoped>
.zfy-cover-field {
    display: grid;
    grid-template-columns: 118px minmax(0, 1fr);
    gap: 12px;
    align-items: stretch;
}

.zfy-cover-field__preview {
    position: relative;
    display: block;
    overflow: hidden;
    width: 118px;
    aspect-ratio: 16 / 10;
    padding: 0;
    border: 1px solid var(--zfy-admin-line);
    border-radius: 8px;
    background: #f2f6fb;
    cursor: pointer;
}

.zfy-cover-field__preview img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.zfy-cover-field__empty {
    position: absolute;
    inset: 0;
    display: grid;
    place-content: center;
    gap: 4px;
    color: #667085;
    font-size: 12px;
    text-align: center;
    background: rgba(248, 250, 252, 0.82);
}

.zfy-cover-field__control {
    display: grid;
    min-width: 0;
    gap: 8px;
    align-content: start;
}

.zfy-cover-field__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

@media (max-width: 640px) {
    .zfy-cover-field {
        grid-template-columns: 1fr;
    }

    .zfy-cover-field__preview {
        width: 100%;
        max-width: 260px;
    }
}
</style>
