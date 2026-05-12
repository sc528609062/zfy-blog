<script setup lang="ts">
defineProps<{
    rows: Array<Record<string, any>>;
    emptyText?: string;
}>();
</script>

<template>
    <el-table :data="rows" :empty-text="emptyText || '暂无数据'" class="zfy-admin-table">
        <el-table-column prop="id" label="ID" width="86" />
        <el-table-column prop="title" label="标题 / 对象" min-width="240" show-overflow-tooltip />
        <el-table-column prop="status" label="状态" width="120">
            <template #default="{ row }">
                <el-tag effect="light" size="small" type="success">{{ row.status || 'published' }}</el-tag>
            </template>
        </el-table-column>
        <el-table-column prop="type" label="类型" width="130" />
        <el-table-column prop="created_at" label="时间" width="170" />
        <el-table-column label="操作" width="190" fixed="right">
            <template #default>
                <el-button link type="primary">编辑</el-button>
                <el-button link>审核</el-button>
                <el-button link>日志</el-button>
            </template>
        </el-table-column>
    </el-table>
</template>
