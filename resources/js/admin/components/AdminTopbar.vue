<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { ArrowDown, House, Menu, Plus, Refresh, Setting, SwitchButton, User, UserFilled, Moon, Sunny, Search, FullScreen, Fold, Expand } from '@element-plus/icons-vue';
import { ElMessage } from 'element-plus';
import { useAdminStore } from '../store';
const store = useAdminStore();
import type { AdminBreadcrumbItem, AdminMenuGroup } from '../useAdminMenu';

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
    mobile: boolean;
    menus: AdminMenuGroup[];
}>();

const emit = defineEmits<{
    navigate: [section: string];
    openMenu: [];
    refresh: [];
    search: [];
    appearance: [];
}>();
const canPublish = computed(() => props.menus.some(group => group.items.some(item => item.key === 'editor')));
const fullscreen = ref(false);
function updateFullscreen() { fullscreen.value = Boolean(document.fullscreenElement); }
async function toggleFullscreen() {
    try {
        if (document.fullscreenElement) await document.exitFullscreen();
        else await document.documentElement.requestFullscreen();
    } catch { ElMessage.warning('当前浏览器无法进入全屏'); }
}
onMounted(() => document.addEventListener('fullscreenchange', updateFullscreen));
onBeforeUnmount(() => document.removeEventListener('fullscreenchange', updateFullscreen));

function reloadPage() {
    emit('refresh');
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
        <div class="zfy-art-header-left">
        <el-tooltip :content="mobile ? '打开后台菜单' : store.collapsed ? '展开侧栏' : '收起侧栏'"><el-button :aria-label="mobile ? '打开后台菜单' : store.collapsed ? '展开侧栏' : '收起侧栏'" class="zfy-art-icon" :icon="mobile ? Menu : store.collapsed ? Expand : Fold" text @click="emit('openMenu')" /></el-tooltip>
        <el-tooltip content="刷新当前页"><el-button class="zfy-art-icon zfy-action-refresh" :icon="Refresh" aria-label="刷新当前页" text @click="reloadPage" /></el-tooltip>
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
        </div>
        <div class="zfy-admin-top-actions">
            <el-tooltip content="搜索菜单"><el-button class="zfy-art-icon" :icon="Search" aria-label="搜索菜单" text @click="emit('search')" /></el-tooltip>
            <el-tooltip :content="fullscreen ? '退出全屏' : '全屏'"><el-button class="zfy-art-icon zfy-action-fullscreen" :icon="FullScreen" :aria-label="fullscreen ? '退出全屏' : '全屏'" text @click="toggleFullscreen" /></el-tooltip>
            <el-tooltip :content="store.dark ? '浅色模式' : '深色模式'"><el-button class="zfy-art-icon" :icon="store.dark ? Sunny : Moon" text :aria-label="store.dark ? '浅色模式' : '深色模式'" @click="store.toggleDark()" /></el-tooltip>
            <el-tooltip content="外观设置"><el-button class="zfy-art-icon" :icon="Setting" aria-label="外观设置" text @click="emit('appearance')" /></el-tooltip>
            <el-tooltip content="访问前台"><el-button class="zfy-art-icon zfy-action-site" :icon="House" aria-label="访问前台" text @click="visitSite" /></el-tooltip>
            <el-button v-if="canPublish" class="zfy-action-publish" :icon="Plus" type="primary" @click="emit('navigate', 'editor')">发布</el-button>
            <el-dropdown trigger="click" placement="bottom-end" @command="handleUserCommand">
                <button class="zfy-admin-user-trigger" type="button" aria-label="用户菜单">
                    <el-avatar :src="user?.avatar_url || '/assets/zfy/placeholders/avatar.svg'" :icon="UserFilled" />
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
