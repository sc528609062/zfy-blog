<script setup lang="ts">
import { computed, reactive, watch } from 'vue';
import ColorPalettePicker from './ColorPalettePicker.vue';
import EditorCloudResourcesField from './EditorCloudResourcesField.vue';
import EditorIconPicker from './EditorIconPicker.vue';
import EditorMediaUrlField from './EditorMediaUrlField.vue';
import type { EditorMediaConfig, EditorPromptSpec } from './types';

const props = defineProps<{
    spec: EditorPromptSpec | null;
    routes?: Record<string, string>;
    media?: EditorMediaConfig;
}>();

const emit = defineEmits<{
    submit: [values: Record<string, string>];
}>();

const visible = defineModel<boolean>('visible', { required: true });
const values = reactive<Record<string, string>>({});
const dialogTitle = computed(() => props.spec?.title || '插入内容');

watch(
    () => props.spec,
    (spec) => {
        Object.keys(values).forEach((key) => {
            delete values[key];
        });

        spec?.fields.forEach((field) => {
            values[field.name] = field.defaultValue || '';
        });
    },
    { immediate: true },
);

function handleConfirm(): void {
    emit('submit', { ...values });
    visible.value = false;
}

function handleCancel(): void {
    visible.value = false;
}
</script>

<template>
    <el-dialog
        v-model="visible"
        :width="spec?.width || '520px'"
        append-to-body
        :class="['zfy-editor-insert-dialog', { 'is-repeatable': spec?.id === 'zfy-cloud' }]"
        :close-on-click-modal="true"
        :lock-scroll="false"
        :show-close="false"
    >
        <template #header>
            <div class="zfy-editor-insert-header">
                <div class="zfy-editor-insert-title">{{ dialogTitle }}</div>
                <button class="zfy-editor-insert-close" type="button" aria-label="关闭" @click="handleCancel">×</button>
            </div>
        </template>

        <div class="zfy-editor-insert-body">
            <el-form v-if="spec" class="zfy-editor-insert-form" label-position="top">
                <el-form-item v-for="field in spec.fields" :key="field.name" :label="field.label">
                    <el-select
                        v-if="field.type === 'select'"
                        v-model="values[field.name]"
                        :allow-create="field.allowCreate"
                        class="zfy-editor-insert-control"
                        :clearable="field.clearable"
                        :default-first-option="field.allowCreate"
                        :filterable="field.filterable"
                        :placeholder="field.placeholder"
                    >
                        <el-option
                            v-for="option in field.options || []"
                            :key="option.value"
                            :label="option.label"
                            :value="option.value"
                        />
                    </el-select>
                    <el-input
                        v-else-if="field.type === 'textarea'"
                        v-model="values[field.name]"
                        :placeholder="field.placeholder"
                        :rows="5"
                        resize="vertical"
                        type="textarea"
                    />
                    <el-input
                        v-else-if="field.type === 'number'"
                        v-model="values[field.name]"
                        :max="field.max"
                        :min="field.min"
                        class="zfy-editor-insert-control"
                        type="number"
                    />
                    <ColorPalettePicker
                        v-else-if="field.type === 'color'"
                        v-model="values[field.name]"
                        class="zfy-editor-color-field"
                        :placeholder="field.placeholder || '#00a2e3'"
                    />
                    <EditorIconPicker
                        v-else-if="field.type === 'icon'"
                        v-model="values[field.name]"
                    />
                    <EditorMediaUrlField
                        v-else-if="field.type === 'media'"
                        v-model="values[field.name]"
                        :library-type="field.mediaType || 'all'"
                        :media="media"
                        :placeholder="field.placeholder"
                        :routes="routes"
                    />
                    <EditorCloudResourcesField
                        v-else-if="field.type === 'cloud-list'"
                        v-model="values[field.name]"
                        :options="field.options || []"
                    />
                    <el-input
                        v-else
                        v-model="values[field.name]"
                        :placeholder="field.placeholder"
                    />
                </el-form-item>
            </el-form>
        </div>

        <template #footer>
            <div class="zfy-editor-insert-footer">
                <el-button @click="handleCancel">取消</el-button>
                <el-button type="primary" @click="handleConfirm">确定</el-button>
            </div>
        </template>
    </el-dialog>
</template>
