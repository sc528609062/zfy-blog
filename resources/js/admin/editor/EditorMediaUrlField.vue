<script setup lang="ts">
import { FolderOpened } from '@element-plus/icons-vue';
import { computed, shallowRef } from 'vue';
import EditorImageDialog from './EditorImageDialog.vue';
import type { EditorMediaConfig, MediaLibraryType } from './types';

const props = withDefaults(defineProps<{
    routes?: Record<string, string>;
    media?: EditorMediaConfig;
    libraryType?: MediaLibraryType;
    placeholder?: string;
}>(), {
    libraryType: 'all',
    placeholder: '请选择媒体库文件或粘贴媒体地址',
});

const model = defineModel<string>({ required: true });
const mediaVisible = shallowRef(false);
const mediaTypeNames: Partial<Record<MediaLibraryType, string>> = {
    image: '图片',
    video: '视频',
    audio: '音频',
};
const libraryUrl = computed(() => String(props.routes?.media_library || '/admin/media/library'));
const uploadUrl = computed(() => String(props.routes?.media_upload || '/admin/media/upload'));
const destroyUrl = computed(() => String(props.routes?.media_destroy || '/admin/media/__MEDIA__'));
const mediaTypeName = computed(() => mediaTypeNames[props.libraryType] || '媒体');
const dialogTitle = computed(() => `选择${mediaTypeName.value}`);
const confirmText = computed(() => `使用此${mediaTypeName.value}`);

function handleMediaSubmit(url: string): void {
    model.value = url.trim();
    mediaVisible.value = false;
}
</script>

<template>
    <div class="zfy-editor-media-url-field">
        <el-input v-model="model" clearable :placeholder="placeholder">
            <template #append>
                <el-button :icon="FolderOpened" @click="mediaVisible = true">媒体库</el-button>
            </template>
        </el-input>

        <EditorImageDialog
            v-model:visible="mediaVisible"
            :confirm-text="confirmText"
            :destroy-url="destroyUrl"
            :library-type="libraryType"
            :library-url="libraryUrl"
            :media="media"
            output="url"
            :title="dialogTitle"
            :upload-url="uploadUrl"
            @submit="handleMediaSubmit"
        />
    </div>
</template>

<style scoped>
.zfy-editor-media-url-field {
    width: 100%;
}
</style>
