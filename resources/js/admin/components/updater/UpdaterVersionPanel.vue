<script setup lang="ts">
import { computed } from 'vue';

const version = defineModel<string>('version', { required: true });

const props = defineProps<{
    currentVersion: string;
    latestVersion: string;
    tags: string[];
    repository: string;
    running: boolean;
    canStart: boolean;
    starting: boolean;
}>();

const hasTags = computed(() => props.tags.length > 0);

const emit = defineEmits<{
    refresh: [];
    start: [];
}>();
</script>

<template>
    <el-card shadow="never">
        <template #header>
            <div class="zfy-card-title">
                <div>
                    <h2>核心版本</h2>
                    <p>{{ repository }}</p>
                </div>
                <el-tag :type="latestVersion ? 'success' : 'warning'">
                    {{ latestVersion ? `最新 ${latestVersion}` : '未发现 tag' }}
                </el-tag>
            </div>
        </template>

        <div class="updater-version-grid">
            <div class="updater-version-card">
                <span>当前版本</span>
                <strong>v{{ currentVersion }}</strong>
            </div>
            <div class="updater-version-card">
                <span>目标版本</span>
                <el-select v-model="version" :disabled="running || !hasTags" placeholder="选择 Gitee tag" class="updater-version-select">
                    <el-option v-for="tag in tags" :key="tag" :label="tag" :value="tag" />
                </el-select>
            </div>
        </div>

        <el-alert
            v-if="!hasTags"
            class="updater-alert"
            title="Gitee 远端还没有版本 tag，请先创建 v1.0.1 这类 tag。"
            type="warning"
            show-icon
            :closable="false"
        />

        <div class="updater-actions">
            <el-button :disabled="running" @click="emit('refresh')">刷新状态</el-button>
            <el-button type="primary" :loading="starting" :disabled="!canStart" @click="emit('start')">
                在线更新
            </el-button>
        </div>
    </el-card>
</template>

<style scoped>
.updater-version-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}

.updater-version-card {
    min-height: 96px;
    border: 1px solid #e8edf4;
    border-radius: 8px;
    padding: 16px;
    background: #fff;
}

.updater-version-card span {
    display: block;
    color: #667085;
    font-weight: 700;
}

.updater-version-card strong {
    display: block;
    margin-top: 12px;
    font-size: 24px;
}

.updater-version-select {
    width: 100%;
    margin-top: 10px;
}

.updater-alert,
.updater-actions {
    margin-top: 16px;
}

.updater-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

@media (max-width: 768px) {
    .updater-version-grid {
        grid-template-columns: 1fr;
    }
}
</style>
