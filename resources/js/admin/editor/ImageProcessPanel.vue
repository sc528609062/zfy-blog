<script setup lang="ts">
import { Crop, FolderOpened, Operation, RefreshLeft, UploadFilled } from '@element-plus/icons-vue';
import { ElMessage } from 'element-plus';
import { computed, nextTick, onBeforeUnmount, reactive, shallowRef, useTemplateRef, watch } from 'vue';
import type { MediaLibraryItem } from './types';

type OutputMime = 'image/jpeg' | 'image/png' | 'image/webp';
type ToolMode = 'crop' | 'mosaic';
type CropAction = 'create' | 'move' | 'resize-n' | 'resize-s' | 'resize-e' | 'resize-w' | 'resize-ne' | 'resize-nw' | 'resize-se' | 'resize-sw';
type PointerAction = CropAction | 'brush' | null;

interface Point {
    x: number;
    y: number;
}

interface BrushStroke {
    points: Point[];
    brushSize: number;
    blockSize: number;
}

const props = defineProps<{
    selectedMedia?: MediaLibraryItem | null;
    uploading?: boolean;
    uploadMaxKb?: number;
}>();

const emit = defineEmits<{
    submit: [file: File];
}>();

const fileInputRef = useTemplateRef<HTMLInputElement>('processFileInput');
const canvasRef = useTemplateRef<HTMLCanvasElement>('processCanvas');

const sourceImage = shallowRef<HTMLImageElement | null>(null);
const sourceName = shallowRef('');
const sourceUrl = shallowRef('');
const sourceWidth = shallowRef(0);
const sourceHeight = shallowRef(0);
const toolMode = shallowRef<ToolMode>('crop');
const outputMime = shallowRef<OutputMime>('image/webp');
const outputQuality = shallowRef(0.9);
const mosaicStrokes = shallowRef<BrushStroke[]>([]);
const currentStroke = shallowRef<BrushStroke | null>(null);
const hoverCropAction = shallowRef<CropAction | null>(null);

const crop = reactive({
    x: 0,
    y: 0,
    width: 0,
    height: 0,
});

const mosaic = reactive({
    enabled: false,
    brushSize: 42,
    blockSize: 16,
});

const pointer = reactive({
    active: false,
    startX: 0,
    startY: 0,
    pointerId: 0,
    action: null as PointerAction,
    cropX: 0,
    cropY: 0,
    cropWidth: 0,
    cropHeight: 0,
});

const outputOptions = [
    { label: 'WebP', value: 'image/webp' },
    { label: 'JPG', value: 'image/jpeg' },
    { label: 'PNG', value: 'image/png' },
];

const hasSource = computed(() => Boolean(sourceImage.value));
const sourceLabel = computed(() => sourceName.value || '未选择图片');
const sourceSizeLabel = computed(() => {
    if (!sourceWidth.value || !sourceHeight.value) {
        return '请先载入图片';
    }

    return `${sourceWidth.value} × ${sourceHeight.value}px`;
});
const cropSizeLabel = computed(() => {
    if (!hasSource.value) {
        return '未选择';
    }

    return `${Math.round(crop.width)} × ${Math.round(crop.height)}px`;
});
const maxUploadText = computed(() => {
    const maxKb = Number(props.uploadMaxKb || 0);

    if (!maxKb) {
        return '';
    }

    return maxKb >= 1024 ? `${Math.round(maxKb / 1024)} MB` : `${maxKb} KB`;
});
const outputExtension = computed(() => {
    if (outputMime.value === 'image/jpeg') {
        return 'jpg';
    }

    if (outputMime.value === 'image/png') {
        return 'png';
    }

    return 'webp';
});
const hasMosaic = computed(() => mosaicStrokes.value.length > 0);
const canvasCursorClass = computed(() => {
    if (toolMode.value === 'mosaic') {
        return 'is-mosaic';
    }

    const action = pointer.active ? pointer.action : hoverCropAction.value;

    if (action) {
        return `is-${action}`;
    }

    return 'is-create';
});

watch(
    [
        () => crop.x,
        () => crop.y,
        () => crop.width,
        () => crop.height,
        () => mosaic.enabled,
        () => mosaic.brushSize,
        () => mosaic.blockSize,
        toolMode,
        mosaicStrokes,
    ],
    () => {
        renderStage();
    },
);

onBeforeUnmount(() => {
    clearObjectUrl();
});

function chooseLocalFile(): void {
    fileInputRef.value?.click();
}

async function handleFilePick(event: Event): Promise<void> {
    const target = event.target as HTMLInputElement | null;
    const file = target?.files?.[0] || null;

    if (!file) {
        return;
    }

    await loadBlob(file, file.name);

    if (target) {
        target.value = '';
    }
}

async function loadSelectedMedia(): Promise<void> {
    if (!props.selectedMedia?.url) {
        ElMessage.warning('请先在媒体库中选中一张图片');
        return;
    }

    try {
        const response = await fetch(props.selectedMedia.url, { credentials: 'same-origin' });

        if (!response.ok) {
            throw new Error(`图片读取失败：${response.status}`);
        }

        const blob = await response.blob();
        await loadBlob(blob, props.selectedMedia.name || 'media-image');
    } catch (error) {
        ElMessage.error(error instanceof Error ? error.message : '图片读取失败');
    }
}

async function loadBlob(blob: Blob, name: string): Promise<void> {
    if (!blob.type.startsWith('image/')) {
        ElMessage.error('请选择图片文件');
        return;
    }

    if (blob.type === 'image/svg+xml' || blob.type === 'image/gif') {
        ElMessage.warning('SVG 和 GIF 可上传，但浏览器内处理会丢失矢量或动图特性');
    }

    clearObjectUrl();
    clearMosaic();

    const objectUrl = URL.createObjectURL(blob);
    const image = new Image();

    image.decoding = 'async';
    image.onload = async () => {
        sourceImage.value = image;
        sourceName.value = name;
        sourceUrl.value = objectUrl;
        sourceWidth.value = image.naturalWidth;
        sourceHeight.value = image.naturalHeight;
        toolMode.value = 'crop';
        resetCropToFull();
        await nextTick();
        renderStage();
    };
    image.onerror = () => {
        URL.revokeObjectURL(objectUrl);
        ElMessage.error('图片加载失败');
    };
    image.src = objectUrl;
}

function resetCropToFull(): void {
    crop.x = 0;
    crop.y = 0;
    crop.width = sourceWidth.value;
    crop.height = sourceHeight.value;
}

function applyAspectPreset(ratio: number): void {
    if (!sourceWidth.value || !sourceHeight.value) {
        return;
    }

    const imageRatio = sourceWidth.value / sourceHeight.value;

    if (imageRatio > ratio) {
        crop.height = sourceHeight.value;
        crop.width = Math.round(crop.height * ratio);
        crop.x = Math.round((sourceWidth.value - crop.width) / 2);
        crop.y = 0;
    } else {
        crop.width = sourceWidth.value;
        crop.height = Math.round(crop.width / ratio);
        crop.x = 0;
        crop.y = Math.round((sourceHeight.value - crop.height) / 2);
    }

    toolMode.value = 'crop';
}

function setToolMode(mode: ToolMode): void {
    toolMode.value = mode;
    hoverCropAction.value = null;

    if (mode === 'mosaic') {
        mosaic.enabled = true;
    }
}

function undoMosaic(): void {
    if (!hasMosaic.value) {
        return;
    }

    mosaicStrokes.value = mosaicStrokes.value.slice(0, -1);
}

function clearMosaic(): void {
    mosaicStrokes.value = [];
    currentStroke.value = null;
}

function handleCanvasPointerDown(event: PointerEvent): void {
    if (!hasSource.value) {
        return;
    }

    const point = pointFromEvent(event);
    if (!point) {
        return;
    }

    pointer.active = true;
    pointer.startX = point.x;
    pointer.startY = point.y;
    pointer.pointerId = event.pointerId;
    pointer.cropX = crop.x;
    pointer.cropY = crop.y;
    pointer.cropWidth = crop.width;
    pointer.cropHeight = crop.height;
    canvasRef.value?.setPointerCapture(event.pointerId);

    if (toolMode.value === 'mosaic') {
        mosaic.enabled = true;
        pointer.action = 'brush';
        currentStroke.value = {
            brushSize: mosaic.brushSize,
            blockSize: mosaic.blockSize,
            points: [point],
        };
        renderStage();
        return;
    }

    pointer.action = cropActionFromPoint(point) || 'create';

    if (pointer.action === 'create') {
        updateCropFromPoints({ x: pointer.startX, y: pointer.startY }, point);
    }
}

function handleCanvasPointerMove(event: PointerEvent): void {
    if (!pointer.active && toolMode.value === 'crop') {
        const point = pointFromEvent(event);
        hoverCropAction.value = point ? cropActionFromPoint(point) : null;
        return;
    }

    if (!pointer.active) {
        return;
    }

    const point = pointFromEvent(event);
    if (!point) {
        return;
    }

    if (toolMode.value === 'mosaic') {
        appendBrushPoint(point);
        renderStage();
        return;
    }

    applyCropPointerAction(point);
}

function handleCanvasPointerUp(event: PointerEvent): void {
    if (!pointer.active) {
        return;
    }

    if (toolMode.value === 'mosaic' && currentStroke.value) {
        mosaicStrokes.value = [...mosaicStrokes.value, currentStroke.value];
        currentStroke.value = null;
    }

    pointer.active = false;
    pointer.action = null;
    canvasRef.value?.releasePointerCapture(event.pointerId);
    clampCrop();
    renderStage();
}

function handleCanvasPointerCancel(event: PointerEvent): void {
    pointer.active = false;
    pointer.action = null;
    currentStroke.value = null;
    canvasRef.value?.releasePointerCapture(event.pointerId);
    renderStage();
}

function handleCanvasPointerLeave(event: PointerEvent): void {
    if (!pointer.active) {
        hoverCropAction.value = null;
        return;
    }

    if (event.pointerId === pointer.pointerId && toolMode.value === 'crop') {
        const point = pointFromEvent(event);
        if (point) {
            applyCropPointerAction(point);
        }
    }
}

function updateCropFromPoints(start: Point, end: Point): void {
    crop.x = Math.min(start.x, end.x);
    crop.y = Math.min(start.y, end.y);
    crop.width = Math.abs(end.x - start.x);
    crop.height = Math.abs(end.y - start.y);
    clampCrop();
}

function applyCropPointerAction(point: Point): void {
    const action = pointer.action;
    if (!action || action === 'brush') {
        return;
    }

    if (action === 'create') {
        updateCropFromPoints({ x: pointer.startX, y: pointer.startY }, point);
        return;
    }

    const deltaX = point.x - pointer.startX;
    const deltaY = point.y - pointer.startY;

    if (action === 'move') {
        crop.x = pointer.cropX + deltaX;
        crop.y = pointer.cropY + deltaY;
        crop.width = pointer.cropWidth;
        crop.height = pointer.cropHeight;
        clampCropPosition();
        return;
    }

    resizeCrop(action, deltaX, deltaY);
}

function resizeCrop(action: CropAction, deltaX: number, deltaY: number): void {
    if (action === 'create' || action === 'move') {
        return;
    }

    const direction = action.replace('resize-', '');
    let left = pointer.cropX;
    let top = pointer.cropY;
    let right = pointer.cropX + pointer.cropWidth;
    let bottom = pointer.cropY + pointer.cropHeight;

    if (direction.includes('w')) {
        left += deltaX;
    }

    if (direction.includes('e')) {
        right += deltaX;
    }

    if (direction.includes('n')) {
        top += deltaY;
    }

    if (direction.includes('s')) {
        bottom += deltaY;
    }

    applyCropEdges(left, top, right, bottom);
}

function applyCropEdges(left: number, top: number, right: number, bottom: number): void {
    const minSize = Math.max(12, Math.round(Math.min(sourceWidth.value, sourceHeight.value) * 0.01));
    const normalizedLeft = clamp(Math.min(left, right - minSize), 0, Math.max(sourceWidth.value - minSize, 0));
    const normalizedTop = clamp(Math.min(top, bottom - minSize), 0, Math.max(sourceHeight.value - minSize, 0));
    const normalizedRight = clamp(Math.max(right, normalizedLeft + minSize), normalizedLeft + minSize, sourceWidth.value);
    const normalizedBottom = clamp(Math.max(bottom, normalizedTop + minSize), normalizedTop + minSize, sourceHeight.value);

    crop.x = normalizedLeft;
    crop.y = normalizedTop;
    crop.width = normalizedRight - normalizedLeft;
    crop.height = normalizedBottom - normalizedTop;
}

function updateCropWidth(value: number | undefined): void {
    crop.width = Number(value || 1);
    clampCrop();
}

function updateCropHeight(value: number | undefined): void {
    crop.height = Number(value || 1);
    clampCrop();
}

function clampCropPosition(): void {
    crop.width = clamp(Math.round(crop.width), 1, sourceWidth.value);
    crop.height = clamp(Math.round(crop.height), 1, sourceHeight.value);
    crop.x = clamp(Math.round(crop.x), 0, Math.max(sourceWidth.value - crop.width, 0));
    crop.y = clamp(Math.round(crop.y), 0, Math.max(sourceHeight.value - crop.height, 0));
}

function cropActionFromPoint(point: Point): CropAction | null {
    if (!hasSource.value) {
        return null;
    }

    const tolerance = cropHitTolerance();
    const left = crop.x;
    const right = crop.x + crop.width;
    const top = crop.y;
    const bottom = crop.y + crop.height;
    const nearLeft = Math.abs(point.x - left) <= tolerance;
    const nearRight = Math.abs(point.x - right) <= tolerance;
    const nearTop = Math.abs(point.y - top) <= tolerance;
    const nearBottom = Math.abs(point.y - bottom) <= tolerance;
    const insideX = point.x >= left - tolerance && point.x <= right + tolerance;
    const insideY = point.y >= top - tolerance && point.y <= bottom + tolerance;

    if (nearLeft && nearTop) {
        return 'resize-nw';
    }

    if (nearRight && nearTop) {
        return 'resize-ne';
    }

    if (nearLeft && nearBottom) {
        return 'resize-sw';
    }

    if (nearRight && nearBottom) {
        return 'resize-se';
    }

    if (nearTop && insideX) {
        return 'resize-n';
    }

    if (nearBottom && insideX) {
        return 'resize-s';
    }

    if (nearLeft && insideY) {
        return 'resize-w';
    }

    if (nearRight && insideY) {
        return 'resize-e';
    }

    if (point.x > left && point.x < right && point.y > top && point.y < bottom) {
        return 'move';
    }

    return null;
}

function cropHitTolerance(): number {
    const canvas = canvasRef.value;
    if (!canvas) {
        return 10;
    }

    const rect = canvas.getBoundingClientRect();
    if (!rect.width) {
        return 10;
    }

    return Math.max(10, Math.round(canvas.width / rect.width * 8));
}

function appendBrushPoint(point: Point): void {
    const stroke = currentStroke.value;
    if (!stroke) {
        return;
    }

    const lastPoint = stroke.points[stroke.points.length - 1];
    const minimumDistance = Math.max(2, stroke.brushSize / 4);

    if (distance(lastPoint, point) < minimumDistance) {
        return;
    }

    stroke.points.push(point);
}

function pointFromEvent(event: PointerEvent): Point | null {
    const canvas = canvasRef.value;
    if (!canvas) {
        return null;
    }

    const rect = canvas.getBoundingClientRect();
    if (!rect.width || !rect.height) {
        return null;
    }

    return {
        x: clamp(Math.round((event.clientX - rect.left) * (canvas.width / rect.width)), 0, Math.max(canvas.width - 1, 0)),
        y: clamp(Math.round((event.clientY - rect.top) * (canvas.height / rect.height)), 0, Math.max(canvas.height - 1, 0)),
    };
}

function clampCrop(): void {
    crop.x = clamp(Math.round(crop.x), 0, Math.max(sourceWidth.value - 1, 0));
    crop.y = clamp(Math.round(crop.y), 0, Math.max(sourceHeight.value - 1, 0));
    crop.width = clamp(Math.round(crop.width), 1, Math.max(sourceWidth.value - crop.x, 1));
    crop.height = clamp(Math.round(crop.height), 1, Math.max(sourceHeight.value - crop.y, 1));
}

function renderStage(): void {
    const image = sourceImage.value;
    const canvas = canvasRef.value;

    if (!image || !canvas) {
        return;
    }

    if (canvas.width !== sourceWidth.value) {
        canvas.width = sourceWidth.value;
    }

    if (canvas.height !== sourceHeight.value) {
        canvas.height = sourceHeight.value;
    }

    const context = canvas.getContext('2d', { willReadFrequently: true });
    if (!context) {
        return;
    }

    context.clearRect(0, 0, canvas.width, canvas.height);
    context.imageSmoothingEnabled = true;
    context.imageSmoothingQuality = 'high';
    context.drawImage(image, 0, 0, canvas.width, canvas.height);

    if (mosaic.enabled) {
        drawMosaicStrokes(context, mosaicStrokes.value);
        if (currentStroke.value) {
            drawMosaicStrokes(context, [currentStroke.value]);
        }
    }

    drawCropOverlay(context);
}

function drawCropOverlay(context: CanvasRenderingContext2D): void {
    clampCrop();

    const lineWidth = Math.max(2, Math.round(Math.min(sourceWidth.value, sourceHeight.value) / 320));
    const handleSize = Math.max(lineWidth * 6, Math.round(Math.min(sourceWidth.value, sourceHeight.value) / 42));

    context.save();
    context.fillStyle = 'rgba(15, 23, 42, 0.36)';
    context.beginPath();
    context.rect(0, 0, context.canvas.width, context.canvas.height);
    context.rect(crop.x, crop.y, crop.width, crop.height);
    context.fill('evenodd');

    context.lineWidth = lineWidth;
    context.strokeStyle = '#4f8cff';
    context.strokeRect(crop.x + lineWidth / 2, crop.y + lineWidth / 2, Math.max(crop.width - lineWidth, 1), Math.max(crop.height - lineWidth, 1));

    context.fillStyle = '#4f8cff';
    [
        [crop.x, crop.y],
        [crop.x + crop.width / 2, crop.y],
        [crop.x + crop.width, crop.y],
        [crop.x, crop.y + crop.height / 2],
        [crop.x + crop.width, crop.y + crop.height / 2],
        [crop.x, crop.y + crop.height],
        [crop.x + crop.width / 2, crop.y + crop.height],
        [crop.x + crop.width, crop.y + crop.height],
    ].forEach(([x, y]) => {
        context.fillRect(x - handleSize / 2, y - handleSize / 2, handleSize, handleSize);
    });
    context.restore();
}

function drawMosaicStrokes(context: CanvasRenderingContext2D, strokes: BrushStroke[], offsetX = 0, offsetY = 0): void {
    strokes.forEach((stroke) => {
        stroke.points.forEach((point, index) => {
            const previous = stroke.points[index - 1];
            if (!previous) {
                applyMosaicCircle(context, point.x - offsetX, point.y - offsetY, stroke.brushSize / 2, stroke.blockSize);
                return;
            }

            const steps = Math.max(1, Math.ceil(distance(previous, point) / Math.max(stroke.brushSize / 3, 1)));
            for (let step = 1; step <= steps; step++) {
                const progress = step / steps;
                applyMosaicCircle(
                    context,
                    previous.x + (point.x - previous.x) * progress - offsetX,
                    previous.y + (point.y - previous.y) * progress - offsetY,
                    stroke.brushSize / 2,
                    stroke.blockSize,
                );
            }
        });
    });
}

function applyMosaicCircle(context: CanvasRenderingContext2D, centerX: number, centerY: number, radius: number, blockSize: number): void {
    const size = clamp(Math.round(blockSize), 4, 80);
    const width = context.canvas.width;
    const height = context.canvas.height;
    const startX = Math.max(0, Math.floor((centerX - radius) / size) * size);
    const startY = Math.max(0, Math.floor((centerY - radius) / size) * size);
    const endX = Math.min(width, Math.ceil((centerX + radius) / size) * size);
    const endY = Math.min(height, Math.ceil((centerY + radius) / size) * size);

    for (let top = startY; top < endY; top += size) {
        for (let left = startX; left < endX; left += size) {
            const blockCenterX = left + size / 2;
            const blockCenterY = top + size / 2;

            if (distance({ x: blockCenterX, y: blockCenterY }, { x: centerX, y: centerY }) > radius + size * 0.35) {
                continue;
            }

            const sampleX = clamp(Math.round(blockCenterX), 0, Math.max(width - 1, 0));
            const sampleY = clamp(Math.round(blockCenterY), 0, Math.max(height - 1, 0));
            const pixel = context.getImageData(sampleX, sampleY, 1, 1).data;

            context.fillStyle = `rgb(${pixel[0]}, ${pixel[1]}, ${pixel[2]})`;
            context.fillRect(left, top, Math.min(size, width - left), Math.min(size, height - top));
        }
    }
}

async function uploadProcessedImage(): Promise<void> {
    if (!sourceImage.value) {
        ElMessage.warning('请先载入图片');
        return;
    }

    const canvas = createOutputCanvas();
    if (!canvas) {
        return;
    }

    const blob = await canvasToBlob(canvas, outputMime.value, outputQuality.value);

    if (!blob) {
        ElMessage.error('图片转换失败');
        return;
    }

    const maxBytes = Number(props.uploadMaxKb || 0) * 1024;
    if (maxBytes > 0 && blob.size > maxBytes) {
        ElMessage.error(`处理后的图片超过上传限制 ${maxUploadText.value}`);
        return;
    }

    const filename = `${safeBaseName(sourceName.value || 'processed-image')}-processed.${outputExtension.value}`;
    emit('submit', new File([blob], filename, { type: outputMime.value }));
}

function createOutputCanvas(): HTMLCanvasElement | null {
    const image = sourceImage.value;
    if (!image) {
        return null;
    }

    clampCrop();

    const canvas = document.createElement('canvas');
    canvas.width = crop.width;
    canvas.height = crop.height;

    const context = canvas.getContext('2d', { willReadFrequently: true });
    if (!context) {
        return null;
    }

    if (outputMime.value === 'image/jpeg') {
        context.fillStyle = '#ffffff';
        context.fillRect(0, 0, canvas.width, canvas.height);
    }

    context.imageSmoothingEnabled = true;
    context.imageSmoothingQuality = 'high';
    context.drawImage(image, crop.x, crop.y, crop.width, crop.height, 0, 0, crop.width, crop.height);

    if (mosaic.enabled && hasMosaic.value) {
        drawMosaicStrokes(context, mosaicStrokes.value, crop.x, crop.y);
    }

    return canvas;
}

function canvasToBlob(canvas: HTMLCanvasElement, type: OutputMime, quality: number): Promise<Blob | null> {
    return new Promise((resolve) => {
        canvas.toBlob(resolve, type, type === 'image/png' ? undefined : quality);
    });
}

function safeBaseName(name: string): string {
    return name
        .replace(/\.[^.]+$/, '')
        .replace(/[^\w\u4e00-\u9fa5-]+/g, '-')
        .replace(/^-+|-+$/g, '')
        || 'image';
}

function clearObjectUrl(): void {
    if (sourceUrl.value) {
        URL.revokeObjectURL(sourceUrl.value);
        sourceUrl.value = '';
    }
}

function distance(first: Point, second: Point): number {
    return Math.hypot(first.x - second.x, first.y - second.y);
}

function clamp(value: number, min: number, max: number): number {
    if (!Number.isFinite(value)) {
        return min;
    }

    return Math.min(Math.max(value, min), max);
}
</script>

<template>
    <div class="zfy-image-process">
        <section class="zfy-image-process__stage">
            <div class="zfy-image-process__toolbar">
                <input
                    ref="processFileInput"
                    accept="image/*"
                    class="zfy-image-process__file"
                    type="file"
                    @change="handleFilePick"
                >
                <el-button :icon="UploadFilled" type="primary" plain @click="chooseLocalFile">本地图片</el-button>
                <el-button :disabled="!selectedMedia" :icon="FolderOpened" @click="loadSelectedMedia">载入选中</el-button>
                <span>{{ sourceLabel }} · {{ sourceSizeLabel }}</span>
            </div>

            <div class="zfy-image-process__tools" role="toolbar" aria-label="图片处理工具">
                <button
                    :class="['zfy-image-process__tool', { 'is-active': toolMode === 'crop' }]"
                    :disabled="!hasSource"
                    type="button"
                    @click="setToolMode('crop')"
                >
                    <el-icon><Crop /></el-icon>
                    <strong>裁剪</strong>
                    <span>{{ cropSizeLabel }}</span>
                </button>
                <button
                    :class="['zfy-image-process__tool', { 'is-active': toolMode === 'mosaic' }]"
                    :disabled="!hasSource"
                    type="button"
                    @click="setToolMode('mosaic')"
                >
                    <el-icon><Operation /></el-icon>
                    <strong>打码</strong>
                    <span>{{ hasMosaic ? `${mosaicStrokes.length} 笔` : `${mosaic.brushSize}px` }}</span>
                </button>
            </div>

            <div class="zfy-image-process__canvas">
                <canvas
                    v-show="hasSource"
                    ref="processCanvas"
                    :class="['zfy-image-process__surface', canvasCursorClass]"
                    @pointercancel.prevent="handleCanvasPointerCancel"
                    @pointerdown.prevent="handleCanvasPointerDown"
                    @pointerleave="handleCanvasPointerLeave"
                    @pointermove.prevent="handleCanvasPointerMove"
                    @pointerup.prevent="handleCanvasPointerUp"
                />
                <el-empty v-if="!hasSource" description="从本地或媒体库载入图片后开始处理" :image-size="90" />
            </div>
        </section>

        <aside class="zfy-image-process__controls">
            <section class="zfy-image-process__panel">
                <div class="zfy-image-process__panel-title">
                    <el-icon><Crop /></el-icon>
                    <strong>裁剪</strong>
                    <span>{{ cropSizeLabel }}</span>
                </div>
                <div class="zfy-image-process__presets">
                    <el-button size="small" @click="resetCropToFull">原图</el-button>
                    <el-button size="small" @click="applyAspectPreset(16 / 9)">16:9</el-button>
                    <el-button size="small" @click="applyAspectPreset(4 / 3)">4:3</el-button>
                    <el-button size="small" @click="applyAspectPreset(1)">1:1</el-button>
                </div>
                <div class="zfy-image-process__crop-grid">
                    <label>
                        <span>宽度</span>
                        <el-input-number
                            :disabled="!hasSource"
                            :max="sourceWidth"
                            :min="1"
                            :model-value="Math.round(crop.width)"
                            controls-position="right"
                            size="small"
                            @update:model-value="updateCropWidth"
                        />
                    </label>
                    <label>
                        <span>高度</span>
                        <el-input-number
                            :disabled="!hasSource"
                            :max="sourceHeight"
                            :min="1"
                            :model-value="Math.round(crop.height)"
                            controls-position="right"
                            size="small"
                            @update:model-value="updateCropHeight"
                        />
                    </label>
                </div>
            </section>

            <section class="zfy-image-process__panel">
                <div class="zfy-image-process__panel-title">
                    <el-icon><Operation /></el-icon>
                    <strong>打码画笔</strong>
                    <el-switch v-model="mosaic.enabled" :disabled="!hasSource" />
                </div>
                <div class="zfy-image-process__slider">
                    <span>画笔 {{ mosaic.brushSize }}px</span>
                    <el-slider v-model="mosaic.brushSize" :disabled="!hasSource" :min="12" :max="120" />
                </div>
                <div class="zfy-image-process__slider">
                    <span>颗粒 {{ mosaic.blockSize }}px</span>
                    <el-slider v-model="mosaic.blockSize" :disabled="!hasSource" :min="4" :max="80" />
                </div>
                <div class="zfy-image-process__actions">
                    <el-button :disabled="!hasMosaic" :icon="RefreshLeft" size="small" @click="undoMosaic">撤销一笔</el-button>
                    <el-button :disabled="!hasMosaic" size="small" @click="clearMosaic">清除打码</el-button>
                </div>
            </section>

            <section class="zfy-image-process__panel">
                <div class="zfy-image-process__panel-title">
                    <strong>转换与上传</strong>
                </div>
                <el-select v-model="outputMime" class="w-full" :disabled="!hasSource">
                    <el-option
                        v-for="option in outputOptions"
                        :key="option.value"
                        :label="option.label"
                        :value="option.value"
                    />
                </el-select>
                <el-slider v-model="outputQuality" :disabled="!hasSource || outputMime === 'image/png'" :min="0.5" :max="1" :step="0.05" />
                <el-button :disabled="!hasSource" :loading="uploading" class="w-full" type="primary" @click="uploadProcessedImage">
                    上传处理后图片
                </el-button>
                <small v-if="maxUploadText">上传限制：{{ maxUploadText }}</small>
            </section>
        </aside>
    </div>
</template>

<style scoped>
.zfy-image-process {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 286px;
    gap: 14px;
    min-height: 0;
}

.zfy-image-process__stage,
.zfy-image-process__controls,
.zfy-image-process__panel {
    min-width: 0;
}

.zfy-image-process__stage,
.zfy-image-process__controls {
    display: grid;
    gap: 12px;
    align-content: start;
}

.zfy-image-process__toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
}

.zfy-image-process__toolbar span {
    min-width: 0;
    overflow: hidden;
    color: var(--zfy-admin-muted);
    font-size: 12px;
    font-weight: 800;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.zfy-image-process__tools {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
}

.zfy-image-process__tool {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    align-items: center;
    gap: 8px;
    min-height: 44px;
    padding: 8px 10px;
    border: 1px solid #d9e5f2;
    border-radius: 8px;
    background: #fff;
    color: var(--zfy-admin-text);
    cursor: pointer;
    text-align: left;
    transition: border-color .15s ease, background .15s ease, box-shadow .15s ease;
}

.zfy-image-process__tool:disabled {
    cursor: not-allowed;
    opacity: .56;
}

.zfy-image-process__tool:not(:disabled):hover {
    border-color: #9fbcff;
    background: #f8fbff;
}

.zfy-image-process__tool.is-active {
    border-color: var(--zfy-admin-primary);
    background: #eef5ff;
    box-shadow: 0 0 0 2px rgba(79, 140, 255, .14);
}

.zfy-image-process__tool .el-icon {
    font-size: 18px;
    color: var(--zfy-admin-primary);
}

.zfy-image-process__tool strong {
    min-width: 0;
    overflow: hidden;
    font-size: 13px;
    font-weight: 900;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.zfy-image-process__tool span {
    color: var(--zfy-admin-muted);
    font-size: 12px;
    font-weight: 800;
    white-space: nowrap;
}

.zfy-image-process__file {
    display: none;
}

.zfy-image-process__canvas {
    display: grid;
    min-height: 420px;
    place-items: center;
    overflow: auto;
    border: 1px solid #d9e5f2;
    border-radius: 8px;
    background:
        linear-gradient(45deg, #f8fafc 25%, transparent 25%),
        linear-gradient(-45deg, #f8fafc 25%, transparent 25%),
        linear-gradient(45deg, transparent 75%, #f8fafc 75%),
        linear-gradient(-45deg, transparent 75%, #f8fafc 75%),
        #fff;
    background-position: 0 0, 0 8px, 8px -8px, -8px 0;
    background-size: 16px 16px;
}

.zfy-image-process__surface {
    display: block;
    max-width: 100%;
    max-height: 560px;
    object-fit: contain;
    touch-action: none;
    user-select: none;
}

.zfy-image-process__surface.is-create {
    cursor: crosshair;
}

.zfy-image-process__surface.is-move {
    cursor: move;
}

.zfy-image-process__surface.is-resize-n,
.zfy-image-process__surface.is-resize-s {
    cursor: ns-resize;
}

.zfy-image-process__surface.is-resize-e,
.zfy-image-process__surface.is-resize-w {
    cursor: ew-resize;
}

.zfy-image-process__surface.is-resize-ne,
.zfy-image-process__surface.is-resize-sw {
    cursor: nesw-resize;
}

.zfy-image-process__surface.is-resize-nw,
.zfy-image-process__surface.is-resize-se {
    cursor: nwse-resize;
}

.zfy-image-process__surface.is-mosaic {
    cursor: cell;
}

.zfy-image-process__panel {
    display: grid;
    gap: 10px;
    padding: 10px;
    border: 1px solid #d9e5f2;
    border-radius: 8px;
    background: #fff;
}

.zfy-image-process__panel-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    color: var(--zfy-admin-text);
    font-size: 13px;
    font-weight: 900;
}

.zfy-image-process__panel-title strong {
    margin-right: auto;
}

.zfy-image-process__panel-title span {
    color: var(--zfy-admin-muted);
    font-size: 12px;
}

.zfy-image-process__presets,
.zfy-image-process__actions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 6px;
}

.zfy-image-process__presets {
    grid-template-columns: repeat(4, minmax(0, 1fr));
}

.zfy-image-process__presets .el-button,
.zfy-image-process__actions .el-button {
    margin-left: 0;
}

.zfy-image-process__crop-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
}

.zfy-image-process__crop-grid label {
    display: grid;
    gap: 4px;
    min-width: 0;
}

.zfy-image-process__crop-grid span {
    color: var(--zfy-admin-muted);
    font-size: 11px;
    font-weight: 800;
}

.zfy-image-process__crop-grid :deep(.el-input-number) {
    width: 100%;
}

.zfy-image-process__slider {
    display: grid;
    gap: 2px;
}

.zfy-image-process__slider span,
.zfy-image-process__panel small {
    color: var(--zfy-admin-muted);
    font-size: 12px;
    font-weight: 800;
}

@media (max-width: 900px) {
    .zfy-image-process {
        grid-template-columns: 1fr;
    }

    .zfy-image-process__canvas {
        min-height: 300px;
    }
}
</style>
