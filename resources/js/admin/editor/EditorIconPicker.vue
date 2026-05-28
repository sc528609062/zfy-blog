<script setup lang="ts">
import { computed, shallowRef, watch } from 'vue';
import { ElMessage } from 'element-plus';
import { Search } from '@element-plus/icons-vue';
import {
    decodeSvgIcon,
    editorIconGroups,
    encodeSvgIcon,
    isSvgIconValue,
    type EditorIconGroup,
    type EditorIconItem,
} from './editorIconPresets';

const model = defineModel<string>({ required: true });

const visible = shallowRef(false);
const query = shallowRef('');
const activeGroup = shallowRef(editorIconGroups[0]?.key || '');
const customSvg = shallowRef('');

const filteredGroups = computed<EditorIconGroup[]>(() => {
    const keyword = query.value.trim().toLowerCase();

    if (!keyword) {
        return editorIconGroups;
    }

    return editorIconGroups
        .map((group) => ({
            ...group,
            items: group.items.filter((item) => {
                const haystack = [item.label, item.value, ...item.keywords].join(' ').toLowerCase();

                return haystack.includes(keyword);
            }),
        }))
        .filter((group) => group.items.length > 0);
});

const selectedSvg = computed(() => sanitizeSvgForPreview(decodeSvgIcon(model.value || '')));
const customPreviewSvg = computed(() => sanitizeSvgForPreview(customSvg.value));
const selectedGlyph = computed(() => iconGlyphForValue(model.value || ''));
const selectedDisplayValue = computed(() => {
    if (!model.value) {
        return '';
    }

    return isSvgIconValue(model.value) ? '已选择 SVG 图标' : model.value;
});

watch(
    () => filteredGroups.value,
    (groups) => {
        if (!groups.some((group) => group.key === activeGroup.value)) {
            activeGroup.value = groups[0]?.key || '';
        }
    },
    { immediate: true },
);

function openDialog(): void {
    visible.value = true;
}

function handleManualInput(value: string | number): void {
    model.value = String(value || '').trim();
}

function handleSelect(item: EditorIconItem): void {
    model.value = item.value;
    visible.value = false;
}

function handleInsertCustomSvg(): void {
    const sanitized = customPreviewSvg.value;

    if (!sanitized) {
        ElMessage.warning('请粘贴合法的 SVG 图标代码');
        return;
    }

    model.value = encodeSvgIcon(sanitized);
    customSvg.value = '';
    visible.value = false;
}

function handleClear(): void {
    model.value = '';
}

function iconGlyphForValue(value: string): string {
    const normalized = value.trim().toLowerCase();
    const preset = editorIconGroups
        .flatMap((group) => group.items)
        .find((item) => item.value.toLowerCase() === normalized);

    if (preset?.glyph) {
        return preset.glyph;
    }

    const match = normalized.match(/\bfa-([a-z0-9-]+)\b/);

    if (!match) {
        return normalized ? normalized.slice(0, 2).toUpperCase() : '';
    }

    return editorIconGroups
        .flatMap((group) => group.items)
        .find((item) => item.kind === 'font-awesome' && item.value.includes(`fa-${match[1]}`))
        ?.glyph || 'FA';
}

function sanitizeSvgForPreview(raw: string): string {
    const source = raw.trim();

    if (!source || source.length > 20000 || !source.toLowerCase().startsWith('<svg')) {
        return '';
    }

    if (/<\s*(script|iframe|object|embed|foreignobject|style)\b/i.test(source)) {
        return '';
    }

    if (/\son[a-z0-9_-]+\s*=/i.test(source) || /(javascript|data)\s*:/i.test(source)) {
        return '';
    }

    try {
        const parser = new DOMParser();
        const doc = parser.parseFromString(source, 'image/svg+xml');

        if (doc.querySelector('parsererror')) {
            return '';
        }

        const svg = doc.documentElement;

        if (!svg || svg.tagName.toLowerCase() !== 'svg') {
            return '';
        }

        scrubSvgElement(svg);
        svg.setAttribute('width', '1em');
        svg.setAttribute('height', '1em');
        svg.setAttribute('aria-hidden', 'true');
        svg.setAttribute('focusable', 'false');

        return new XMLSerializer().serializeToString(svg);
    } catch {
        return '';
    }
}

function scrubSvgElement(element: Element): void {
    const allowedTags = new Set(['svg', 'g', 'path', 'circle', 'rect', 'line', 'polyline', 'polygon', 'ellipse', 'title']);
    const allowedAttributes = new Set([
        'viewbox',
        'width',
        'height',
        'fill',
        'stroke',
        'stroke-width',
        'stroke-linecap',
        'stroke-linejoin',
        'fill-rule',
        'clip-rule',
        'opacity',
        'transform',
        'd',
        'cx',
        'cy',
        'r',
        'x',
        'y',
        'x1',
        'y1',
        'x2',
        'y2',
        'rx',
        'ry',
        'points',
        'xmlns',
    ]);

    Array.from(element.attributes).forEach((attribute) => {
        const name = attribute.name.toLowerCase();

        if (!allowedAttributes.has(name) || !isSafeSvgAttributeValue(attribute.value)) {
            element.removeAttribute(attribute.name);
        }
    });

    Array.from(element.childNodes).forEach((node) => {
        if (node.nodeType === Node.ELEMENT_NODE) {
            const child = node as Element;

            if (!allowedTags.has(child.tagName.toLowerCase())) {
                child.remove();
                return;
            }

            scrubSvgElement(child);
            return;
        }

        if (node.nodeType !== Node.TEXT_NODE || !node.textContent?.trim()) {
            node.remove();
        }
    });
}

function isSafeSvgAttributeValue(value: string): boolean {
    return value.length <= 4000
        && !/[<>]/.test(value)
        && !/(javascript|data)\s*:/i.test(value)
        && !/url\s*\(/i.test(value)
        && !/[\x00-\x08\x0B\x0C\x0E-\x1F]/.test(value);
}
</script>

<template>
    <div class="zfy-icon-picker">
        <div class="zfy-icon-picker__control">
            <span class="zfy-icon-picker__preview" aria-hidden="true">
                <span v-if="selectedSvg" class="zfy-icon-picker__svg" v-html="selectedSvg" />
                <span v-else-if="selectedGlyph" class="zfy-icon-picker__glyph">{{ selectedGlyph }}</span>
                <span v-else class="zfy-icon-picker__empty">无</span>
            </span>
            <el-input
                :model-value="selectedDisplayValue"
                placeholder="选择图标，或输入 fa fa-heart"
                @update:model-value="handleManualInput"
            />
            <el-button type="primary" plain @click="openDialog">选择</el-button>
            <el-button v-if="model" @click="handleClear">清空</el-button>
        </div>

        <el-dialog
            v-model="visible"
            append-to-body
            class="zfy-editor-insert-dialog zfy-editor-icon-dialog"
            :close-on-click-modal="false"
            :show-close="false"
            width="760px"
        >
            <template #header>
                <div class="zfy-editor-insert-header">
                    <div class="zfy-editor-insert-title">选择图标</div>
                    <button class="zfy-editor-insert-close" type="button" aria-label="关闭" @click="visible = false">×</button>
                </div>
            </template>

            <div class="zfy-editor-icon-body">
                <div class="zfy-editor-icon-search">
                    <el-input v-model="query" :prefix-icon="Search" placeholder="搜索图标名称、类名..." clearable />
                </div>

                <section class="zfy-editor-icon-custom">
                    <div class="zfy-editor-icon-section-title">
                        <strong>自定义 SVG 图标代码</strong>
                        <small>粘贴完整 SVG，确认后自动编码保存到 icon 属性</small>
                    </div>
                    <div class="zfy-editor-icon-custom-row">
                        <el-input
                            v-model="customSvg"
                            class="zfy-editor-icon-custom-input"
                            placeholder="<svg viewBox=&quot;0 0 1024 1024&quot;><path d=&quot;...&quot;></path></svg>"
                            :rows="4"
                            type="textarea"
                            resize="none"
                        />
                        <div class="zfy-editor-icon-custom-side">
                            <span class="zfy-editor-icon-custom-preview" aria-hidden="true">
                                <span v-if="customPreviewSvg" v-html="customPreviewSvg" />
                                <span v-else>SVG</span>
                            </span>
                            <el-button type="primary" @click="handleInsertCustomSvg">确认插入</el-button>
                        </div>
                    </div>
                </section>

                <el-empty v-if="filteredGroups.length === 0" description="没有匹配的图标" :image-size="80" />

                <el-tabs v-else v-model="activeGroup" class="zfy-editor-icon-tabs">
                    <el-tab-pane
                        v-for="group in filteredGroups"
                        :key="group.key"
                        :label="group.label"
                        :name="group.key"
                    >
                        <div class="zfy-editor-icon-grid">
                            <button
                                v-for="icon in group.items"
                                :key="icon.key"
                                class="zfy-editor-icon-item"
                                type="button"
                                :title="icon.value"
                                @click="handleSelect(icon)"
                            >
                                <span class="zfy-editor-icon-item-preview" aria-hidden="true">
                                    <span v-if="icon.svg" class="zfy-icon-picker__svg" v-html="icon.svg" />
                                    <span v-else class="zfy-icon-picker__glyph">{{ icon.glyph }}</span>
                                </span>
                                <span class="zfy-editor-icon-item-label">{{ icon.label }}</span>
                            </button>
                        </div>
                    </el-tab-pane>
                </el-tabs>
            </div>

            <template #footer>
                <div class="zfy-editor-insert-footer">
                    <el-button @click="visible = false">取消</el-button>
                </div>
            </template>
        </el-dialog>
    </div>
</template>
