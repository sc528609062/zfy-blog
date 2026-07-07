<script setup lang="ts">
import { CircleCheck, WarningFilled } from '@element-plus/icons-vue';
import type { UpdaterCheck } from './useUpdater';

defineProps<{
    ok: boolean;
    checks: UpdaterCheck[];
}>();
</script>

<template>
    <el-card shadow="never">
        <template #header>
            <div class="zfy-card-title">
                <div>
                    <h2>更新前检查</h2>
                    <p>服务器需要具备在线更新所需命令和写入权限</p>
                </div>
                <el-tag :type="ok ? 'success' : 'danger'">{{ ok ? '可更新' : '需处理' }}</el-tag>
            </div>
        </template>

        <div class="updater-checks">
            <div v-for="check in checks" :key="check.key" class="updater-check">
                <el-icon :class="['updater-check-icon', { 'is-ok': check.ok }]">
                    <CircleCheck v-if="check.ok" />
                    <WarningFilled v-else />
                </el-icon>
                <div>
                    <strong>{{ check.label }}</strong>
                    <span>{{ check.message }}</span>
                </div>
            </div>
        </div>
    </el-card>
</template>

<style scoped>
.updater-checks {
    display: grid;
    gap: 10px;
}

.updater-check {
    display: grid;
    grid-template-columns: 28px minmax(0, 1fr);
    gap: 10px;
    align-items: start;
    border: 1px solid #e8edf4;
    border-radius: 8px;
    padding: 12px;
    background: #fff;
}

.updater-check-icon {
    margin-top: 2px;
    color: #ef4444;
}

.updater-check-icon.is-ok {
    color: #16a34a;
}

.updater-check strong,
.updater-check span {
    display: block;
}

.updater-check span {
    margin-top: 4px;
    color: #667085;
}
</style>
