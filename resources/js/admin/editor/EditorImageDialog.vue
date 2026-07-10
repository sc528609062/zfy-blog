<script setup lang="ts">
import { Document, Files, Headset, Picture, Refresh, Search, UploadFilled, VideoPlay } from '@element-plus/icons-vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { computed, shallowRef, useTemplateRef, watch } from 'vue';
import ImageProcessPanel from './ImageProcessPanel.vue';
import { formatMediaSize, useMediaLibrary } from './useMediaLibrary';
import type { Component } from 'vue';
import type { EditorMediaConfig, MediaItemType, MediaLibraryItem, MediaLibraryType } from './types';

const props = withDefaults(defineProps<{
    libraryUrl: string;
    uploadUrl: string;
    media?: EditorMediaConfig;
    title?: string;
    confirmText?: string;
    output?: 'markdown' | 'url';
    libraryType?: MediaLibraryType;
    destroyUrl?: string;
}>(), {
    title: '媒体库',
    confirmText: '插入媒体',
    output: 'markdown',
    libraryType: 'all',
});

const emit = defineEmits<{
    submit: [value: string, media?: MediaLibraryItem | null];
}>();

const visible = defineModel<boolean>('visible', { required: true });
const fileInputRef = useTemplateRef<HTMLInputElement>('fileInput');
const detailsRef = useTemplateRef<HTMLElement>('details');

const activeTab = shallowRef<'upload' | 'library' | 'process'>('library');
const directoryMode = shallowRef<string>('custom');
const customDirectory = shallowRef('');
const searchQuery = shallowRef('');
const selectedId = shallowRef<number | null>(null);
const imageUrl = shallowRef('');
const imageAlt = shallowRef('');
const selectedType = shallowRef<MediaLibraryType>(props.libraryType);
const manualMediaType = shallowRef<MediaItemType>('image');
const page = shallowRef(1);
const perPage = shallowRef(Number(props.media?.libraryPerPage || 24));

const itemTypeOptions: Array<{ label: string; value: MediaItemType }> = [
    { label: '图片', value: 'image' },
    { label: '视频', value: 'video' },
    { label: '音频', value: 'audio' },
    { label: '压缩包', value: 'archive' },
    { label: '文件', value: 'file' },
];

const libraryTypeOptions: Array<{ label: string; value: MediaLibraryType }> = [
    { label: '全部媒体', value: 'all' },
    ...itemTypeOptions,
];

const directoryOptions = computed(() => props.media?.directories || []);
const directoryPresetValues = computed(() => new Set(directoryOptions.value.map((directory) => normalizeDirectory(directory.value))));
const storageRoot = computed(() => normalizeDirectorySegment(props.media?.storageRoot || 'media'));
const defaultDirectory = computed(() => normalizeDirectory(props.media?.defaultDirectory || directoryOptions.value[0]?.value || 'editor/images'));
const uploadMaxKb = computed(() => Number(props.media?.uploadMaxKb || 20480));
const uploadMaxText = computed(() => (uploadMaxKb.value >= 1024 ? `${Math.round(uploadMaxKb.value / 1024)} MB` : `${uploadMaxKb.value} KB`));
const useCustomDirectory = computed(() => directoryMode.value === 'custom' || !directoryPresetValues.value.has(normalizeDirectory(directoryMode.value)));
const canFilterType = computed(() => props.libraryType === 'all');
const visibleLibraryTypeOptions = computed(() => (canFilterType.value ? libraryTypeOptions : libraryTypeOptions.filter((option) => option.value === props.libraryType)));
const uploadAccept = computed(() => {
    if (props.libraryType === 'image') {
        return 'image/*,.svg';
    }

    if (props.libraryType === 'video') {
        return 'video/*,.m3u8';
    }

    if (props.libraryType === 'audio') {
        return 'audio/*,.mp3,.wav,.ogg,.m4a,.aac,.flac';
    }

    return 'image/*,video/*,audio/*,.m3u8,.zip,.rar,.7z,.tar,.gz,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.json';
});
const uploadSupportText = computed(() => {
    if (props.libraryType === 'image') {
        return '支持 JPG、PNG、WebP、GIF、AVIF、SVG';
    }

    if (props.libraryType === 'video') {
        return '支持 MP4、WebM、MOV、M3U8 等视频文件';
    }

    if (props.libraryType === 'audio') {
        return '支持 MP3、WAV、OGG、M4A、AAC、FLAC';
    }

    return '支持图片、视频、音频、压缩包和常见文档';
});
const isProcessTab = computed(() => activeTab.value === 'process');
const bodyClasses = computed(() => ['zfy-editor-image-body', { 'is-process-mode': isProcessTab.value }]);
const hasPreviewUrl = computed(() => imageUrl.value.trim() !== '');
const downloadFileName = computed(() => selectedMedia.value ? mediaDownloadName(selectedMedia.value) : `${imageAlt.value.trim() || previewTypeLabel.value}`);

const { items, meta, loading, uploading, loadMedia, uploadMedia, deleteMedia } = useMediaLibrary(
    computed(() => props.libraryUrl),
    computed(() => props.uploadUrl),
    computed(() => props.destroyUrl),
);

const selectedMedia = computed<MediaLibraryItem | null>(() =>
    items.value.find((item) => item.id === selectedId.value) || null,
);
const previewMediaType = computed<MediaItemType>(() => selectedMedia.value?.type || manualMediaType.value);
const previewTypeLabel = computed(() => mediaTypeLabel(previewMediaType.value));
const processMedia = computed<MediaLibraryItem | null>(() => (selectedMedia.value?.type === 'image' ? selectedMedia.value : null));

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

watch(
    () => props.libraryType,
    (type) => {
        selectedType.value = type;
        if (visible.value) {
            void loadLibrary(true);
        }
    },
);

function resetSelection(): void {
    activeTab.value = 'library';
    selectedId.value = null;
    imageUrl.value = '';
    imageAlt.value = '';
    searchQuery.value = '';
    selectedType.value = props.libraryType;
    manualMediaType.value = props.libraryType === 'all' ? 'image' : props.libraryType;
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
            type: selectedType.value,
        });
    } catch (error) {
        ElMessage.error(error instanceof Error ? error.message : '媒体库加载失败');
    }
}

function handleTypeChange(value: string | number | boolean): void {
    selectedType.value = isMediaLibraryType(value) ? value : props.libraryType;
    selectedId.value = null;
    void loadLibrary(true);
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
    manualMediaType.value = media.type;
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
    if (props.libraryType === 'image' && !isImageFile(file)) {
        ElMessage.error('请上传图片文件');
        return;
    }

    if (uploadMaxKb.value > 0 && file.size > uploadMaxKb.value * 1024) {
        ElMessage.error(`文件超过上传限制 ${uploadMaxText.value}`);
        return;
    }

    try {
        const media = await uploadMedia(file, directory.value);
        selectedId.value = media.id;
        imageUrl.value = media.url;
        imageAlt.value = media.name;
        manualMediaType.value = media.type;
        if (props.libraryType === 'all' && selectedType.value !== 'all' && selectedType.value !== media.type) {
            selectedType.value = media.type;
        }
        activeTab.value = 'library';
        await loadLibrary(true);
        highlightUploaded(media);
        ElMessage.success('媒体已上传');
    } catch (error) {
        ElMessage.error(error instanceof Error ? error.message : '媒体上传失败');
    }
}

async function handleProcessedFile(file: File): Promise<void> {
    await handleUploadFile(file);
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
    const currentMedia = selectedMedia.value;
    imageUrl.value = next;
    if (currentMedia && next !== currentMedia.url) {
        selectedId.value = null;
    }

    if (!currentMedia || next !== currentMedia.url) {
        manualMediaType.value = inferMediaTypeFromUrl(next);
    }
}

function handleEditDetails(): void {
    if (!hasPreviewUrl.value) {
        ElMessage.warning('请先选择媒体');
        return;
    }

    detailsRef.value?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    detailsRef.value?.querySelector<HTMLInputElement>('input')?.focus();
}

async function handleDeleteSelectedMedia(): Promise<void> {
    const media = selectedMedia.value;
    if (!media) {
        ElMessage.warning('请先选择媒体库中的文件');
        return;
    }

    try {
        await ElMessageBox.confirm(`确定永久删除“${media.name}”？此操作不可恢复。`, '永久删除媒体', {
            confirmButtonText: '永久删除',
            cancelButtonText: '取消',
            closeOnClickModal: true,
            confirmButtonClass: 'el-button--danger',
            lockScroll: false,
            type: 'warning',
        });

        await deleteMedia(media);
        resetPreviewSelection();

        if (items.value.length === 0 && page.value > 1) {
            page.value -= 1;
        }

        await loadLibrary();
        ElMessage.success('媒体已永久删除');
    } catch (error) {
        if (error === 'cancel' || error === 'close') {
            return;
        }

        ElMessage.error(error instanceof Error ? error.message : '媒体删除失败');
    }
}

function handleConfirm(): void {
    const url = imageUrl.value.trim();
    if (!url) {
        const typeName = ({ image: '图片', video: '视频', audio: '音频' } as Record<string, string>)[props.libraryType] || '媒体';
        ElMessage.error(props.output === 'url'
            ? `请先选择、上传或填写${typeName}地址`
            : '请先选择、上传或填写媒体地址');
        return;
    }

    if (props.output === 'url') {
        emit('submit', url, selectedMedia.value);
        visible.value = false;
        return;
    }

    emit('submit', mediaSnippet(url, previewMediaType.value, imageAlt.value.trim() || selectedMedia.value?.name || previewTypeLabel.value), selectedMedia.value);
    visible.value = false;
}

function handleCancel(): void {
    visible.value = false;
}

function resetPreviewSelection(): void {
    selectedId.value = null;
    imageUrl.value = '';
    imageAlt.value = '';
    manualMediaType.value = props.libraryType === 'all' ? 'image' : props.libraryType;
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

function sanitizeShortcodeAttribute(value: string): string {
    return value
        .replace(/[\r\n]+/g, ' ')
        .replace(/"/g, '\'')
        .trim();
}

function mediaSnippet(url: string, type: MediaItemType, title: string): string {
    const cleanTitle = title || mediaTypeLabel(type);

    if (type === 'image') {
        return `![${sanitizeMarkdownText(cleanTitle || '图片')}](${url})`;
    }

    const shortcodeTitle = sanitizeShortcodeAttribute(cleanTitle);
    const shortcodeUrl = sanitizeShortcodeAttribute(url);

    if (type === 'video') {
        return `{zfy-dplayer title="${shortcodeTitle || '视频'}" url="${shortcodeUrl}" /}`;
    }

    if (type === 'audio') {
        return `{zfy-mp3 title="${shortcodeTitle || '音频'}" url="${shortcodeUrl}" /}`;
    }

    return `{zfy-cloud title="${shortcodeTitle || '下载资源'}" url="${shortcodeUrl}" /}`;
}

function inferMediaTypeFromUrl(url: string): MediaItemType {
    const cleanUrl = url.split(/[?#]/)[0]?.toLowerCase() || '';
    const extension = cleanUrl.match(/\.([a-z0-9]+)$/)?.[1] || '';

    if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg'].includes(extension)) {
        return 'image';
    }

    if (['mp4', 'webm', 'mov', 'm4v', 'avi', 'mkv', 'm3u8'].includes(extension)) {
        return 'video';
    }

    if (['mp3', 'wav', 'ogg', 'oga', 'm4a', 'aac', 'flac'].includes(extension)) {
        return 'audio';
    }

    if (['zip', 'rar', '7z', 'tar', 'gz'].includes(extension)) {
        return 'archive';
    }

    if (extension) {
        return 'file';
    }

    return manualMediaType.value;
}

function isImageFile(file: File): boolean {
    const extension = file.name.split('.').pop()?.toLowerCase() || '';

    return file.type.startsWith('image/') || ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg'].includes(extension);
}

function mediaTypeLabel(type: MediaItemType | MediaLibraryType): string {
    const option = libraryTypeOptions.find((item) => item.value === type);

    return option?.label || '文件';
}

function mediaTypeIcon(type: MediaItemType): Component {
    if (type === 'image') {
        return Picture;
    }

    if (type === 'video') {
        return VideoPlay;
    }

    if (type === 'audio') {
        return Headset;
    }

    if (type === 'archive') {
        return Files;
    }

    return Document;
}

function mediaDownloadName(media: MediaLibraryItem): string {
    const pathName = media.path.split('/').pop()?.trim() || '';
    const extension = pathName.includes('.') ? pathName.split('.').pop() || '' : '';

    if (!extension || media.name.endsWith(`.${extension}`)) {
        return media.name || pathName || 'media';
    }

    return `${media.name || pathName}.${extension}`;
}

function isMediaLibraryType(value: unknown): value is MediaLibraryType {
    return value === 'all' || value === 'image' || value === 'video' || value === 'audio' || value === 'archive' || value === 'file';
}
</script>

<template>
    <el-dialog
        v-model="visible"
        :close-on-click-modal="true"
        :lock-scroll="false"
        :show-close="false"
        align-center
        append-to-body
        class="zfy-editor-image-dialog"
        modal-class="zfy-editor-image-overlay"
        width="1100px"
    >
        <template #header>
            <div class="zfy-editor-insert-header">
                <div class="zfy-editor-insert-title">{{ title }}</div>
                <button class="zfy-editor-insert-close" type="button" aria-label="关闭" @click="handleCancel">×</button>
            </div>
        </template>

        <div :class="bodyClasses">
            <div class="zfy-editor-image-main">
                <el-tabs v-model="activeTab" class="zfy-editor-image-tabs">
                    <el-tab-pane label="上传媒体" name="upload">
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
                                    :accept="uploadAccept"
                                    class="zfy-media-upload__input"
                                    type="file"
                                    @change="handleFilePick"
                                >
                                <el-icon class="zfy-media-upload__icon">
                                    <UploadFilled />
                                </el-icon>
                                <strong>{{ uploading ? '正在上传' : '选择媒体上传' }}</strong>
                                <span>{{ uploadSupportText }}，选择后自动上传</span>
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
                            <div :class="['zfy-media-library__toolbar', { 'has-type-filter': canFilterType }]">
                                <el-select
                                    v-if="canFilterType"
                                    :model-value="selectedType"
                                    class="zfy-media-library__type"
                                    @change="handleTypeChange"
                                >
                                    <el-option
                                        v-for="option in visibleLibraryTypeOptions"
                                        :key="option.value"
                                        :label="option.label"
                                        :value="option.value"
                                    />
                                </el-select>
                                <el-input
                                    v-model="searchQuery"
                                    clearable
                                    placeholder="搜索媒体名称或路径"
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
                                    <span class="zfy-media-card__thumb">
                                        <img v-if="media.type === 'image'" :alt="media.name" :src="media.thumbUrl">
                                        <span v-else class="zfy-media-card__icon">
                                            <el-icon>
                                                <component :is="mediaTypeIcon(media.type)" />
                                            </el-icon>
                                            <b>{{ mediaTypeLabel(media.type) }}</b>
                                        </span>
                                    </span>
                                    <span class="zfy-media-card__name">{{ media.name }}</span>
                                    <small>{{ mediaTypeLabel(media.type) }} · {{ formatMediaSize(media.size) }}</small>
                                </button>
                            </div>

                            <el-empty
                                v-if="!loading && items.length === 0"
                                description="暂无可用媒体"
                            />

                            <div class="zfy-media-library__footer">
                                <div class="zfy-media-library__meta">
                                    <span>共 {{ meta.total }} 个</span>
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

                    <el-tab-pane v-if="props.libraryType === 'all' || props.libraryType === 'image'" label="图片处理" name="process">
                        <ImageProcessPanel
                            :selected-media="processMedia"
                            :uploading="uploading"
                            :upload-max-kb="uploadMaxKb"
                            @submit="handleProcessedFile"
                        />
                    </el-tab-pane>
                </el-tabs>
            </div>

            <aside v-if="!isProcessTab" class="zfy-media-preview">
                <div class="zfy-media-preview__frame">
                    <img v-if="imageUrl && previewMediaType === 'image'" :alt="imageAlt || '媒体预览'" :src="imageUrl">
                    <video v-else-if="imageUrl && previewMediaType === 'video'" :src="imageUrl" controls />
                    <audio v-else-if="imageUrl && previewMediaType === 'audio'" :src="imageUrl" controls />
                    <div v-else-if="imageUrl" class="zfy-media-preview__file">
                        <el-icon>
                            <component :is="mediaTypeIcon(previewMediaType)" />
                        </el-icon>
                        <strong>{{ imageAlt || previewTypeLabel }}</strong>
                        <span>{{ imageUrl }}</span>
                    </div>
                    <div v-else class="zfy-media-preview__empty">
                        <el-icon>
                            <Picture />
                        </el-icon>
                        <span>选择媒体后会显示预览</span>
                    </div>
                </div>

                <div class="zfy-media-preview__actions" aria-label="媒体操作">
                    <a
                        v-if="hasPreviewUrl"
                        class="zfy-media-preview__action"
                        :href="imageUrl"
                        rel="noopener noreferrer"
                        target="_blank"
                    >查看媒体文件</a>
                    <button v-else class="zfy-media-preview__action is-disabled" type="button" disabled>查看媒体文件</button>
                    <span>|</span>
                    <button
                        :disabled="!hasPreviewUrl"
                        :class="['zfy-media-preview__action', { 'is-disabled': !hasPreviewUrl }]"
                        type="button"
                        @click="handleEditDetails"
                    >编辑详细信息</button>
                    <span>|</span>
                    <a
                        v-if="hasPreviewUrl"
                        class="zfy-media-preview__action"
                        :download="downloadFileName"
                        :href="imageUrl"
                    >下载文件</a>
                    <button v-else class="zfy-media-preview__action is-disabled" type="button" disabled>下载文件</button>
                    <span>|</span>
                    <button
                        :disabled="!selectedMedia"
                        :class="['zfy-media-preview__action', 'is-danger', { 'is-disabled': !selectedMedia }]"
                        type="button"
                        @click="handleDeleteSelectedMedia"
                    >永久删除</button>
                </div>

                <div ref="details" class="zfy-media-preview__fields">
                    <div class="zfy-media-preview__field">
                        <label>媒体地址</label>
                        <el-input :model-value="imageUrl" placeholder="请选择或粘贴媒体地址" @update:model-value="handlePreviewUrl" />
                    </div>
                    <div class="zfy-media-preview__field">
                        <label>媒体名称</label>
                        <el-input v-model="imageAlt" placeholder="请输入媒体名称" />
                    </div>
                    <div v-if="props.output === 'markdown'" class="zfy-media-preview__field">
                        <label>插入类型</label>
                        <el-select v-model="manualMediaType" :disabled="Boolean(selectedMedia)">
                            <el-option
                                v-for="option in itemTypeOptions"
                                :key="option.value"
                                :label="option.label"
                                :value="option.value"
                            />
                        </el-select>
                    </div>
                    <div class="zfy-media-preview__field">
                        <label>当前选中</label>
                        <div class="zfy-media-preview__meta">
                            <span>{{ selectedMedia?.name || '未选择' }}</span>
                            <small>{{ selectedMedia?.path || imageUrl || '请先在左侧选择媒体' }}</small>
                        </div>
                    </div>
                </div>
            </aside>
        </div>

        <template #footer>
            <div class="zfy-editor-insert-footer">
                <el-button @click="handleCancel">取消</el-button>
                <el-button type="primary" @click="handleConfirm">{{ confirmText }}</el-button>
            </div>
        </template>
    </el-dialog>
</template>
