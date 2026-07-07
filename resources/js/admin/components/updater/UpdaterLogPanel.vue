<script setup lang="ts">
import type { UpgradeLogPayload } from './useUpdater';

defineProps<{
    activeLog: UpgradeLogPayload | null;
    recentLogs: UpgradeLogPayload[];
    running: boolean;
}>();

function tagType(status: string) {
    if (status === 'success') return 'success';
    if (status === 'failed') return 'danger';
    if (status === 'running') return 'warning';
    return 'info';
}
</script>

<template>
    <el-card shadow="never">
        <template #header>
            <div class="zfy-card-title">
                <div>
                    <h2>更新日志</h2>
                    <p>{{ running ? '任务执行中，日志每 3 秒刷新' : '最近的在线更新记录' }}</p>
                </div>
                <el-tag v-if="activeLog" :type="tagType(activeLog.status)">{{ activeLog.status }}</el-tag>
            </div>
        </template>

        <pre v-if="activeLog" class="updater-log">{{ activeLog.log || '等待日志写入...' }}</pre>
        <el-empty v-else description="暂无更新任务" />

        <el-divider />
        <el-table :data="recentLogs" size="small">
            <el-table-column prop="to_version" label="版本" width="120" />
            <el-table-column label="状态" width="100">
                <template #default="{ row }">
                    <el-tag :type="tagType(row.status)" size="small">{{ row.status }}</el-tag>
                </template>
            </el-table-column>
            <el-table-column prop="updated_at" label="更新时间" min-width="160" />
        </el-table>
    </el-card>
</template>

<style scoped>
.updater-log {
    min-height: 220px;
    max-height: 420px;
    overflow: auto;
    border: 1px solid #1f2937;
    border-radius: 8px;
    padding: 14px;
    background: #111827;
    color: #d1fae5;
    font-size: 12px;
    line-height: 1.7;
    white-space: pre-wrap;
}
</style>
