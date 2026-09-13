<script setup lang="ts">
import { reactive, ref } from 'vue';
import { ElMessage } from 'element-plus';
import { adminRequest } from './adminRequest';
const props = defineProps<{ profile: Record<string, any>; csrf: string }>();
const form = reactive({ name: props.profile.name || '', email: props.profile.email || '', bio: props.profile.bio || '', current_password: '', password: '', password_confirmation: '' });
const saving = ref(false);
async function save() {
    saving.value = true;
    try {
        await adminRequest('/admin/profile', props.csrf, 'PUT', form);
        form.current_password = form.password = form.password_confirmation = '';
        ElMessage.success('个人资料已保存');
    } catch (error) { ElMessage.error((error as Error).message); }
    finally { saving.value = false; }
}
</script>
<template>
    <el-form class="admin-profile-form" label-position="top" style="max-width:800px" @submit.prevent="save">
        <el-form-item label="昵称"><el-input v-model="form.name" /></el-form-item>
        <el-form-item label="邮箱"><el-input v-model="form.email" type="email" /></el-form-item>
        <el-form-item class="admin-form-wide" label="个人介绍"><el-input v-model="form.bio" type="textarea" :rows="4" /></el-form-item>
        <el-form-item label="当前密码"><el-input v-model="form.current_password" type="password" show-password autocomplete="current-password" /></el-form-item>
        <el-form-item label="新密码"><el-input v-model="form.password" type="password" show-password autocomplete="new-password" /></el-form-item>
        <el-form-item label="确认新密码"><el-input v-model="form.password_confirmation" type="password" show-password autocomplete="new-password" /></el-form-item>
        <footer class="admin-form-actions"><el-button type="primary" :loading="saving" native-type="submit">保存资料</el-button></footer>
    </el-form>
</template>
