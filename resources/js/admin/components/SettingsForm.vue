<script setup lang="ts">
import { computed, reactive } from 'vue';
import { ElMessage } from 'element-plus';

interface SettingField {
    key: string;
    label: string;
    type: 'text' | 'url' | 'number' | 'boolean' | 'select';
    default?: string | number | boolean | null;
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
}>();

const values = reactive<Record<string, string | number | boolean | null>>({});

const groups = computed(() => props.schema || []);

groups.value.forEach((group) => {
    group.fields.forEach((field) => {
        values[field.key] = field.default ?? null;
    });
});

function saveSettings() {
    ElMessage.success('设置表单已接入统一 schema，保存接口将在对应模块实现。');
}
</script>

<template>
    <el-card shadow="never">
        <template #header>
            <div class="zfy-card-title">
                <div>
                    <h2>统一设置</h2>
                    <p>系统、主题和插件设置都通过同一个 schema 渲染。</p>
                </div>
                <el-button type="primary" @click="saveSettings">保存设置</el-button>
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
                        <el-input v-else v-model="values[field.key]" />
                    </el-form-item>
                </el-form>
            </el-tab-pane>
        </el-tabs>
    </el-card>
</template>
