<script setup lang="ts">
import { computed, shallowRef, watch } from 'vue';
import { symbolPickerPresets, type SymbolPickerItem, type SymbolPickerKind } from './symbolPresets';

const props = defineProps<{
    kind: SymbolPickerKind;
}>();

const emit = defineEmits<{
    submit: [snippet: string];
}>();

const visible = defineModel<boolean>('visible', { required: true });
const activeCategory = shallowRef('');

const categories = computed(() => symbolPickerPresets[props.kind]);
const dialogTitle = computed(() => (props.kind === 'emoji' ? '插入表情包' : '插入符号'));
const bodyClass = computed(() => ['zfy-editor-symbol-body', `is-${props.kind}`]);

watch(
    () => [props.kind, visible.value] as const,
    () => {
        if (visible.value) {
            activeCategory.value = categories.value[0]?.key || '';
        }
    },
    { immediate: true },
);

function handleSelect(item: SymbolPickerItem): void {
    emit('submit', item.value);
    visible.value = false;
}

function handleCancel(): void {
    visible.value = false;
}
</script>

<template>
    <el-dialog
        v-model="visible"
        append-to-body
        class="zfy-editor-insert-dialog zfy-editor-symbol-dialog"
        :close-on-click-modal="true"
        :lock-scroll="false"
        :show-close="false"
        width="600px"
    >
        <template #header>
            <div class="zfy-editor-insert-header">
                <div class="zfy-editor-insert-title">{{ dialogTitle }}</div>
                <button class="zfy-editor-insert-close" type="button" aria-label="关闭" @click="handleCancel">×</button>
            </div>
        </template>

        <div :class="bodyClass">
            <el-tabs v-model="activeCategory" class="zfy-editor-symbol-tabs">
                <el-tab-pane
                    v-for="category in categories"
                    :key="category.key"
                    :label="category.label"
                    :name="category.key"
                >
                    <div class="zfy-editor-symbol-grid">
                        <button
                            v-for="symbol in category.items"
                            :key="`${category.key}-${symbol.value}`"
                            class="zfy-editor-symbol-item"
                            type="button"
                            :title="symbol.label"
                            :aria-label="`插入${symbol.label}`"
                            @click="handleSelect(symbol)"
                        >
                            <span class="zfy-editor-symbol-value">{{ symbol.value }}</span>
                            <small v-if="symbol.label !== symbol.value">{{ symbol.label }}</small>
                        </button>
                    </div>
                </el-tab-pane>
            </el-tabs>
        </div>

        <template #footer>
            <div class="zfy-editor-insert-footer">
                <el-button @click="handleCancel">取消</el-button>
            </div>
        </template>
    </el-dialog>
</template>
