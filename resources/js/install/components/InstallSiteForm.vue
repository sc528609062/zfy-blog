<script setup lang="ts">
import { computed } from 'vue';

interface SiteForm {
    name: string;
    url?: string;
}

interface AdminForm {
    name: string;
    username: string;
    email: string;
    password: string;
    password_confirmation: string;
}

const props = defineProps<{
    site: SiteForm;
    admin: AdminForm;
}>();

const emit = defineEmits<{
    'update:site': [value: SiteForm];
    'update:admin': [value: AdminForm];
}>();

function siteField<Key extends keyof SiteForm>(key: Key) {
    return computed({
        get: () => props.site[key],
        set: (value) => emit('update:site', { ...props.site, [key]: value }),
    });
}

function adminField<Key extends keyof AdminForm>(key: Key) {
    return computed({
        get: () => props.admin[key],
        set: (value) => emit('update:admin', { ...props.admin, [key]: value }),
    });
}

const siteName = siteField('name');
const adminName = adminField('name');
const adminUsername = adminField('username');
const adminEmail = adminField('email');
const adminPassword = adminField('password');
const adminPasswordConfirmation = adminField('password_confirmation');

const passwordError = computed(() => {
    if (!adminPassword.value) {
        return '';
    }

    return adminPassword.value.length < 8 ? '登录密码至少需要 8 位。' : '';
});

const confirmPasswordError = computed(() => {
    if (!adminPasswordConfirmation.value) {
        return '';
    }

    return adminPassword.value !== adminPasswordConfirmation.value ? '两次输入的密码不一致。' : '';
});
</script>

<template>
    <div class="zfy-install-section">
        <div class="zfy-install-heading">
            <h2>站点与管理员</h2>
            <p>这些信息会写入系统设置，并创建第一个超级管理员账号。站点地址会自动使用当前访问地址。</p>
        </div>

        <el-form label-position="top">
            <div class="zfy-install-grid">
                <el-form-item label="站点名称">
                    <el-input v-model="siteName" />
                </el-form-item>
                <el-form-item label="管理员昵称">
                    <el-input v-model="adminName" />
                </el-form-item>
                <el-form-item label="管理员用户名">
                    <el-input v-model="adminUsername" />
                </el-form-item>
                <el-form-item label="管理员邮箱">
                    <el-input v-model="adminEmail" />
                </el-form-item>
                <el-form-item label="登录密码" :error="passwordError">
                    <el-input v-model="adminPassword" show-password type="password" />
                </el-form-item>
                <el-form-item label="确认密码" :error="confirmPasswordError">
                    <el-input v-model="adminPasswordConfirmation" show-password type="password" />
                </el-form-item>
            </div>
        </el-form>
    </div>
</template>
