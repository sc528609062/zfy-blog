<script setup lang="ts">
import { Picture, Refresh, Search, UploadFilled } from '@element-plus/icons-vue';
import { ElMessage } from 'element-plus';
import { computed, shallowRef, useTemplateRef, watch } from 'vue';
import { formatMediaSize, useMediaLibrary } from './useMediaLibrary';
import type { EditorMediaConfig, MediaLibraryItem } from './types';

const props = defineProps<{
    libraryUrl: string;
    uploadUrl: string;
    media?: EditorMediaConfig;
}>();

const emit = defineEmits<{
    submit: [snippet: string];
}>();

const visible = defineModel<boolean>('visible', { required: true });
const fileInputRef = useTemplateRef<HTMLInputElement>('fileInput');

const activeTab = shallowRef<'upload' | 'library'>('upload');
const directoryMode = shallowRef<string>('custom');
const customDirectory = shallowRef('');
const searchQuery = shallowRef('');
const selectedId = shallowRef<number | null>(null);
const imageUrl = shallowRef('');
const imageAlt = shallowRef('');
const page = shallowRef(1);
const perPage = shallowRef(Number(props.media?.libraryPerPage || 24));

const directoryOptions = computed(() => props.media?.directories || []);
const directoryPresetValues = computed(() => new Set(directoryOptions.value.map((directory) => normalizeDirectory(directory.value))));
const storageRoot = computed(() => normalizeDirectorySegment(props.media?.storageRoot || 'media'));
const defaultDirectory = computed(() => normalizeDirectory(props.media?.defaultDirectory || directoryOptions.value[0]?.value || 'editor/images'));
const uploadMaxKb = computed(() => Number(props.media?.uploadMaxKb || 20480));
const uploadMaxText = computed(() => (uploadMaxKb.value >= 1024 ? `${Math.round(uploadMaxKb.value / 1024)} MB` : `${uploadMaxKb.value} KB`));
const useCustomDirectory = computed(() => directoryMode.value === 'custom' || !directoryPresetValues.value.has(normalizeDirectory(directoryMode.value)));

const { items, meta, loading, uploading, loadMedia, uploadMedia } = useMediaLibrary(
    computed(() => props.libraryUrl),
    computed(() => props.uploadUrl),
);

const selectedMedia = computed<MediaLibraryItem | null>(() =>
    items.value.find((item) => item.id === selectedId.value) || null,
);

const directory = computed(() => {
    if (useCustomDirectory.value) {
        return normalizeDirectory(customDirectory.value);
    }

    return normalizeDirectory(directoryMode.value);
});

const directoryPreview = computed(() => `public/${storageRoot.value}/${directory.value || defaultDirectory.value}`);

watch(
    visible,
    (isOpen) => {
        if (!isOpen) {
            return;
        }

        resetSelection();
        void loadLibrary(true);
    },
    { immediate: true },
);

function resetSelection(): void {
    activeTab.value = 'upload';
    selectedId.value = null;
    imageUrl.value = '';
    imageAlt.value = '';
    searchQuery.value = '';
    page.value = 1;
    resetDirectorySelection();
}

async function loadLibrary(resetPage = false): Promise<void> {
    if (resetPage) {
        page.value = 1;
    }

    try {
        await loadMedia({
            query: searchQuery.value,
            page: page.value,
            perPage: perPage.value,
            type: 'image',
        });
    } catch (error) {
        ElMessage.error(error instanceof Error ? error.message : '媒体库加载失败');
    }
}

async function handlePageChange(nextPage: number): Promise<void> {
    page.value = nextPage;
    await loadLibrary();
}

function handleResetSearch(): void {
    searchQuery.value = '';
    void loadLibrary(true);
}

function handleSelectMedia(media: MediaLibraryItem): void {
    selectedId.value = media.id;
    imageUrl.value = media.url;
    imageAlt.value = media.name;
}

async function handleFilePick(event: Event): Promise<void> {
    const target = event.target as HTMLInputElement | null;
    const file = target?.files?.[0] || null;

    if (!file) {
        return;
    }

    await handleUploadFile(file);

    if (target) {
        target.value = '';
    }
}

async function handleDrop(event: DragEvent): Promise<void> {
    const file = event.dataTransfer?.files?.[0] || null;
    if (!file) {
        return;
    }

    await handleUploadFile(file);
}

async function handleUploadFile(file: File): Promise<void> {
    try {
        const media = await uploadMedia(file, directory.value);
        selectedId.value = media.id;
        imageUrl.value = media.url;
        imageAlt.value = media.name;
        activeTab.value = 'library';
        await loadLibrary(true);
        highlightUploaded(media);
        ElMessage.success('图片已上传');
    } catch (error) {
        ElMessage.error(error instanceof Error ? error.message : '图片上传失败');
    }
}

function highlightUploaded(media: MediaLibraryItem): void {
    const exists = items.value.find((item) => item.id === media.id);
    if (exists) {
        selectedId.value = media.id;
    }
}

function chooseFile(): void {
    fileInputRef.value?.click();
}

function handlePreviewUrl(value: string): void {
    const next = value.trim();
    imageUrl.value = next;
    if (selectedMedia.value && next !== selectedMedia.value.url) {
        selectedId.value = null;
    }
}

function handleConfirm(): void {
    const url = imageUrl.value.trim();
    if (!url) {
        ElMessage.error('请先选择或上传一张图片');
        return;
    }

    const alt = sanitizeMarkdownText(imageAlt.value.trim() || selectedMedia.value?.name || '图片');
    emit('submit', `![${alt}](${url})`);
    visible.value = false;
}

function handleCancel(): void {
    visible.value = false;
}

function normalizeDirectory(value: string): string {
    const directory = value.trim().replace(/^\/+|\/+$/g, '');
    return directory || 'editor/images';
}

function normalizeDirectorySegment(value: string): string {
    const segment = value.trim().replace(/^\/+|\/+$/g, '');
    return segment || 'media';
}

function resetDirectorySelection(): void {
    const defaultValue = defaultDirectory.value;

    if (directoryPresetValues.value.has(defaultValue)) {
        directoryMode.value = defaultValue;
        customDirectory.value = '';
        return;
    }

    directoryMode.value = 'custom';
    customDirectory.value = defaultValue;
}

function sanitizeMarkdownText(value: string): string {
    return value
        .replace(/\\/g, '\\\\')
        .replace(/\[/g, '\\[')
        .replace(/\]/g, '\\]')
        .replace(/\(/g, '\\(')
        .replace(/\)/g, '\\)');
}
</script>

<template>
    <el-dialog
        v-model="visible"
        :close-on-click-modal="false"
        :show-close="false"
        append-to-body
        class="zfy-editor-image-dialog"
        width="1100px"
    >
        <template #header>
            <div class="zfy-editor-insert-header">
                <div class="zfy-editor-insert-title">插入图片</div>
                <button class="zfy-editor-insert-close" type="button" aria-label="关闭" @click="handleCancel">×</button>
            </div>
        </template>

        <div class="zfy-editor-image-body">
            <div class="zfy-editor-image-main">
                <el-tabs v-model="activeTab" class="zfy-editor-image-tabs">
                    <el-tab-pane label="上传图片" name="upload">
                        <div class="zfy-media-upload">
                            <div class="zfy-media-upload__field">
                                <label class="zfy-media-upload__label">保存位置</label>
                                <div class="zfy-media-upload__row">
                                    <el-select v-model="directoryMode" class="zfy-media-upload__select">
                                        <el-option
                                            v-for="option in directoryOptions"
                                            :key="option.value"
                                            :label="option.label"
                                            :value="option.value"
                                        />
                                        <el-option label="自定义" value="custom" />
                                    </el-select>
                                    <el-input
                                        v-if="useCustomDirectory"
                                        v-model="customDirectory"
                                        placeholder="例如：editor/gallery"
                                    />
                                </div>
                                <div class="zfy-media-upload__hint">最终保存到 {{ directoryPreview }}，单个文件不超过 {{ uploadMaxText }}</div>
                            </div>

                            <div
                                :class="['zfy-media-upload__dropzone', { 'is-loading': uploading }]"
                                role="button"
                                tabindex="0"
                                @click="chooseFile"
                                @keydown.enter.prevent="chooseFile"
                                @keydown.space.prevent="chooseFile"
                                @dragover.prevent
                                @drop.prevent="handleDrop"
                            >
                                <input
                                    ref="fileInput"
                                    accept="image/*,.svg"
                                    class="zfy-media-upload__input"
                                    type="file"
                                    @change="handleFilePick"
                                >
                                <el-icon class="zfy-media-upload__icon">
                                    <UploadFilled />
                                </el-icon>
                                <strong>{{ uploading ? '正在上传' : '选择图片上传' }}</strong>
                                <span>支持 JPG、PNG、WebP、GIF、AVIF、SVG，选择后自动上传</span>
                            </div>

                            <div v-if="selectedMedia" class="zfy-media-upload__recent">
                                <span>最近上传</span>
                                <strong>{{ selectedMedia.name }}</strong>
                                <small>{{ selectedMedia.url }}</small>
                            </div>
                        </div>
                    </el-tab-pane>

                    <el-tab-pane label="媒体库" name="library">
                        <div class="zfy-media-library">
                            <div class="zfy-media-library__toolbar">
                                <el-input
                                    v-model="searchQuery"
                                    clearable
                                    placeholder="搜索图片名称或路径"
                                    :prefix-icon="Search"
                                    @keyup.enter="loadLibrary(true)"
                                />
                                <el-button :icon="Search" type="primary" @click="loadLibrary(true)">搜索</el-button>
                                <el-button :icon="Refresh" @click="handleResetSearch">重置</el-button>
                            </div>

                            <div v-if="loading && items.length === 0" class="zfy-media-library__skeleton">
                                <el-skeleton :rows="8" animated />
                            </div>

                            <div v-else class="zfy-media-grid">
                                <button
                                    v-for="media in items"
                                    :key="media.id"
                                    :class="['zfy-media-card', { 'is-active': selectedId === media.id }]"
                                    type="button"
                                    @click="handleSelectMedia(media)"
                                >
                                    <img :alt="media.name" :src="media.thumbUrl">
                                    <span>{{ media.name }}</span>
                                    <small>{{ formatMediaSize(media.size) }}</small>
                                </button>
                            </div>

                            <el-empty
                                v-if="!loading && items.length === 0"
                                description="暂无可用图片"
                            />

                            <div class="zfy-media-library__footer">
                                <div class="zfy-media-library__meta">
                                    <span>共 {{ meta.total }} 张</span>
                                    <span v-if="selectedMedia">已选中 {{ selectedMedia.name }}</span>
                                </div>
                                <el-pagination
                                    v-if="meta.lastPage > 1"
                                    :current-page="meta.currentPage"
                                    :page-size="meta.perPage"
                                    :total="meta.total"
                                    layout="prev, pager, next"
                                    small
                                    @current-change="handlePageChange"
                                />
                            </div>
                        </div>
                    </el-tab-pane>
                </el-tabs>
            </div>

            <aside class="zfy-media-preview">
                <div class="zfy-media-preview__frame">
                    <img v-if="imageUrl" :alt="imageAlt || '图片预览'" :src="imageUrl">
                    <div v-else class="zfy-media-preview__empty">
                        <el-icon>
                            <Picture />
                        </el-icon>
                        <span>选择图片后会显示预览</span>
                    </div>
                </div>

                <div class="zfy-media-preview__fields">
                    <div class="zfy-media-preview__field">
                        <label>图片地址</label>
                        <el-input :model-value="imageUrl" placeholder="请选择或粘贴图片地址" @update:model-value="handlePreviewUrl" />
                    </div>
                    <div class="zfy-media-preview__field">
                        <label>图片名称</label>
                        <el-input v-model="imageAlt" placeholder="请输入图片名称" />
                    </div>
                    <div class="zfy-media-preview__field">
                        <label>当前选中</label>
                        <div class="zfy-media-preview__meta">
                            <span>{{ selectedMedia?.name || '未选择' }}</span>
                            <small>{{ selectedMedia?.path || imageUrl || '请先在左侧选择图片' }}</small>
                        </div>
                    </div>
                </div>
            </aside>
        </div>

        <template #footer>
            <div class="zfy-editor-insert-footer">
                <el-button @click="handleCancel">取消</el-button>
                <el-button type="primary" @click="handleConfirm">插入图片</el-button>
            </div>
        </template>
    </el-dialog>
</template>
