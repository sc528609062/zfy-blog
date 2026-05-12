<script setup lang="ts">
import { House, Plus, Refresh, Search, UserFilled } from '@element-plus/icons-vue';

defineProps<{
    title: string;
    description: string;
}>();

const emit = defineEmits<{
    navigate: [section: string];
}>();
</script>

<template>
    <el-header class="zfy-admin-topbar">
        <div class="min-w-0">
            <el-breadcrumb separator="/">
                <el-breadcrumb-item>后台</el-breadcrumb-item>
                <el-breadcrumb-item>{{ title }}</el-breadcrumb-item>
            </el-breadcrumb>
            <p>{{ description }}</p>
        </div>

        <el-input
            class="zfy-admin-search"
            placeholder="搜索文章、订单、用户"
            :prefix-icon="Search"
            clearable
        />

        <div class="zfy-admin-top-actions">
            <el-button :icon="Refresh" circle @click="window.location.reload()" />
            <el-button :icon="Plus" type="primary" @click="emit('navigate', 'editor')">发布</el-button>
            <el-button :icon="House" @click="window.location.href = '/'">前台</el-button>
            <el-avatar :icon="UserFilled" />
        </div>
    </el-header>
</template>
