<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { editorColorPresets } from './colorPresets';

const props = withDefaults(defineProps<{
    presets?: readonly string[];
    customLabel?: string;
    placeholder?: string;
}>(), {
    presets: () => editorColorPresets,
    customLabel: '自定义',
    placeholder: '#00a2e3',
});

const model = defineModel<string>({ required: true });
const draft = ref('');

const presets = computed(() => props.presets.length > 0 ? props.presets : editorColorPresets);
const fallbackColor = computed(() => presets.value[4] || presets.value[0] || props.placeholder);

watch(
    () => model.value,
    (value) => {
        const normalized = normalizeColor(value);
        const next = normalized || fallbackColor.value;

        if (value !== next) {
            model.value = next;
        }

        draft.value = next;
    },
    { immediate: true },
);

function selectPreset(color: string): void {
    const next = normalizeColor(color) || fallbackColor.value;
    model.value = next;
    draft.value = next;
}

function handleNativeInput(event: Event): void {
    const value = normalizeColor((event.target as HTMLInputElement | null)?.value || '');
    if (!value) {
        return;
    }

    model.value = value;
    draft.value = value;
}

function handleTextInput(value: string): void {
    draft.value = value;

    const normalized = normalizeColor(value);
    if (!normalized) {
        return;
    }

    model.value = normalized;
}

function normalizeColor(value: string): string {
    const raw = value.trim().replace(/^#/, '');
    if (!raw) {
        return '';
    }

    if (/^[0-9a-fA-F]{3,4}$/.test(raw) || /^[0-9a-fA-F]{6}$/.test(raw) || /^[0-9a-fA-F]{8}$/.test(raw)) {
        return `#${raw.toLowerCase()}`;
    }

    return '';
}
</script>

<template>
    <div class="zfy-color-picker">
        <div class="zfy-color-picker__presets" role="list" aria-label="系统色板">
            <button
                v-for="color in presets"
                :key="color"
                :class="['zfy-color-picker__preset', { 'is-active': model === color }]"
                :title="color"
                type="button"
                @click="selectPreset(color)"
            >
                <span class="zfy-color-picker__swatch" :style="{ backgroundColor: color }" />
            </button>
        </div>
        <div class="zfy-color-picker__custom">
            <div class="zfy-color-picker__custom-label">{{ customLabel }}</div>
            <div class="zfy-color-picker__custom-row">
                <input
                    class="zfy-color-picker__native"
                    :value="draft || fallbackColor"
                    type="color"
                    @input="handleNativeInput"
                >
                <el-input
                    :model-value="draft"
                    class="zfy-color-picker__input"
                    :placeholder="placeholder"
                    @update:model-value="handleTextInput"
                />
            </div>
        </div>
    </div>
</template>
