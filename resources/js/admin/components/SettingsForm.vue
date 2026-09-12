<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';
import { ElMessage } from 'element-plus';
import { adminRequest } from './adminRequest';

interface SettingField {
    key: string;
    label: string;
    type: 'text' | 'textarea' | 'url' | 'number' | 'boolean' | 'select';
    default?: string | number | boolean | null;
    value?: string | number | boolean | null;
    options?: Record<string, string>;
}

interface SettingGroup {
    key: string;
    label: string;
    description: string;
    fields: SettingField[];
}

const props = defineProps<{
    schema: SettingGroup[];
    csrf: string;
}>();

const values = reactive<Record<string, string | number | boolean | null>>({});

const groups = computed(() => props.schema || []);
const saving = ref(false);

watch(groups, () => groups.value.forEach((group) => {
    group.fields.forEach((field) => {
        values[field.key] = field.value ?? field.default ?? null;
    });
}), { immediate: true });

async function saveSettings() {
    saving.value = true;
    try {
        for (const group of groups.value) {
            await adminRequest(`/admin/settings/${group.key}`, props.csrf, 'PUT', { values: Object.fromEntries(group.fields.map((field) => [field.key, values[field.key]])) });
        }
        ElMessage.success('设置已保存');
    } catch (error) {
        ElMessage.error((error as Error).message);
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <el-card shadow="never">
        <template #header>
            <div class="zfy-card-title">
                <div>
                    <h2>{{ groups[0]?.label || '设置' }}</h2>
                </div>
                <el-button type="primary" :loading="saving" @click="saveSettings">保存设置</el-button>
            </div>
        </template>

        <el-tabs>
            <el-tab-pane
                v-for="group in groups"
                :key="group.key"
                :label="group.label"
                :name="group.key"
            >
                <p class="zfy-settings-description">{{ group.description }}</p>
                <el-form class="zfy-settings-form" label-position="top">
                    <el-form-item
                        v-for="field in group.fields"
                        :key="field.key"
                        :label="field.label"
                    >
                        <el-switch v-if="field.type === 'boolean'" v-model="values[field.key]" />
                        <el-input-number v-else-if="field.type === 'number'" v-model="values[field.key]" class="w-full" controls-position="right" />
                        <el-select v-else-if="field.type === 'select'" v-model="values[field.key]" class="w-full">
                            <el-option
                                v-for="(label, value) in field.options || {}"
                                :key="value"
                                :label="label"
                                :value="value"
                            />
                        </el-select>
                        <el-input v-else v-model="values[field.key]" :type="field.type === 'textarea' ? 'textarea' : 'text'" :rows="6" />
                    </el-form-item>
                </el-form>
            </el-tab-pane>
        </el-tabs>
    </el-card>
</template>
