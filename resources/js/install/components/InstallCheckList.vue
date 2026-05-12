<script setup lang="ts">
defineProps<{
    checks: Array<{
        key: string;
        label: string;
        ok: boolean;
        detail: string;
    }>;
}>();
</script>

<template>
    <div class="zfy-install-section">
        <div class="zfy-install-heading">
            <h2>环境检查</h2>
            <p>确认服务器满足安装要求，避免安装过程中断。</p>
        </div>

        <div class="zfy-check-list">
            <div v-for="item in checks" :key="item.key" class="zfy-check-item">
                <div>
                    <strong>{{ item.label }}</strong>
                    <span>{{ item.detail }}</span>
                </div>
                <el-tag :type="item.ok ? 'success' : 'danger'">{{ item.ok ? '通过' : '未通过' }}</el-tag>
            </div>
        </div>
    </div>
</template>
