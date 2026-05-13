<script setup lang="ts">
import { ArrowDown, House, Menu, Plus, Refresh, Setting, SwitchButton, User, UserFilled } from '@element-plus/icons-vue';
import type { AdminBreadcrumbItem } from '../useAdminMenu';

interface AdminUser {
    id?: number;
    name?: string;
    username?: string;
    email?: string;
    avatar_url?: string | null;
}

const props = defineProps<{
    breadcrumbs: AdminBreadcrumbItem[];
    user?: AdminUser | null;
    csrf?: string;
}>();

const emit = defineEmits<{
    navigate: [section: string];
    openMenu: [];
}>();

function reloadPage() {
    window.location.reload();
}

function visitSite() {
    window.location.href = '/';
}

function logout() {
    const form = document.createElement('form');
    const csrf = document.createElement('input');

    form.method = 'POST';
    form.action = '/logout';
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value = props.csrf || document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '';

    form.appendChild(csrf);
    document.body.appendChild(form);
    form.submit();
}

function handleUserCommand(command: string | number | object) {
    if (command === 'profile') {
        window.location.href = '/user';
        return;
    }

    if (command === 'settings') {
        window.location.href = '/user/settings';
        return;
    }

    if (command === 'logout') {
        logout();
    }
}
</script>

<template>
    <el-header class="zfy-admin-topbar">
        <el-button
            aria-label="打开后台菜单"
            class="zfy-mobile-menu-button"
            :icon="Menu"
            circle
            @click="emit('openMenu')"
        />

        <el-breadcrumb
            id="breadcrumb-container"
            class="zfy-admin-breadcrumb app-breadcrumb breadcrumb-container"
            separator="/"
        >
            <el-breadcrumb-item
                v-for="item in breadcrumbs"
                :key="`${item.label}-${item.section || 'label'}`"
            >
                <a
                    v-if="item.section && !item.current"
                    class="zfy-admin-breadcrumb-link"
                    href="/admin"
                    @click.prevent="emit('navigate', item.section)"
                >
                    {{ item.label }}
                </a>
                <span v-else class="no-redirect">{{ item.label }}</span>
            </el-breadcrumb-item>
        </el-breadcrumb>

        <div class="zfy-admin-top-actions">
            <el-button class="zfy-action-refresh" :icon="Refresh" circle @click="reloadPage" />
            <el-button class="zfy-action-publish" :icon="Plus" type="primary" @click="emit('navigate', 'editor')">发布</el-button>
            <el-button class="zfy-action-site" :icon="House" @click="visitSite">前台</el-button>
            <el-dropdown trigger="hover" placement="bottom-end" @command="handleUserCommand">
                <button class="zfy-admin-user-trigger" type="button">
                    <el-avatar :src="user?.avatar_url || undefined" :icon="UserFilled" />
                    <span>{{ user?.name || user?.username || '管理员' }}</span>
                    <el-icon><ArrowDown /></el-icon>
                </button>
                <template #dropdown>
                    <el-dropdown-menu>
                        <el-dropdown-item disabled>
                            <div class="zfy-admin-user-card">
                                <strong>{{ user?.name || '管理员' }}</strong>
                                <small>{{ user?.email || user?.username || 'zfy-blog' }}</small>
                            </div>
                        </el-dropdown-item>
                        <el-dropdown-item command="profile" :icon="User">个人中心</el-dropdown-item>
                        <el-dropdown-item command="settings" :icon="Setting">用户设置</el-dropdown-item>
                        <el-dropdown-item divided command="logout" :icon="SwitchButton">退出登录</el-dropdown-item>
                    </el-dropdown-menu>
                </template>
            </el-dropdown>
        </div>
    </el-header>
</template>
