<script setup lang="ts">
interface DatabaseForm {
    host: string;
    port: number;
    database: string;
    username: string;
    password: string;
}

const db = defineModel<DatabaseForm>('db', { required: true });
</script>

<template>
    <div class="zfy-install-section">
        <div class="zfy-install-heading">
            <h2>数据库配置</h2>
            <p>安装器只连接已有 MySQL 数据库，不会使用 root 权限自动建库。</p>
        </div>

        <el-form label-position="top">
            <div class="zfy-install-grid">
                <el-form-item label="数据库地址">
                    <el-input v-model="db.host" />
                </el-form-item>
                <el-form-item label="端口">
                    <el-input-number v-model="db.port" :max="65535" :min="1" class="w-full" controls-position="right" />
                </el-form-item>
                <el-form-item label="数据库名">
                    <el-input v-model="db.database" />
                </el-form-item>
                <el-form-item label="用户名">
                    <el-input v-model="db.username" />
                </el-form-item>
            </div>
            <el-form-item label="密码">
                <el-input v-model="db.password" show-password type="password" />
            </el-form-item>
        </el-form>
    </div>
</template>
