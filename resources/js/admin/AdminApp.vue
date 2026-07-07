<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, shallowRef } from 'vue';
import { ElMessage } from 'element-plus';
import AdminPage from './components/AdminPage.vue';
import AdminSidebar from './components/AdminSidebar.vue';
import AdminTopbar from './components/AdminTopbar.vue';
import { buildAdminBreadcrumbs, findAdminMenuItem, type AdminMenuGroup, type AdminPageDefinition } from './useAdminMenu';

const props = defineProps<{
    payload: Record<string, any>;
}>();

interface NavigateOptions {
    force?: boolean;
    replace?: boolean;
}

const activePayload = shallowRef<Record<string, any>>({ ...props.payload });
const isMobileSidebarOpen = shallowRef(false);
const navigationLoading = shallowRef(false);
let navigationController: AbortController | null = null;

const activeSection = computed(() => activePayload.value.section || 'dashboard');
const menus = computed<AdminMenuGroup[]>(() => activePayload.value.admin_menu || []);
const currentPage = computed<AdminPageDefinition>(() => activePayload.value.current_page || findAdminMenuItem(menus.value, activeSection.value) || {
    key: activeSection.value,
    label: '后台',
    description: '后台管理页面',
    kind: 'placeholder',
});
const pageTitle = computed(() => currentPage.value.label || '后台');
const pageDescription = computed(() => currentPage.value.description || '后台管理页面');
const breadcrumbs = computed(() => buildAdminBreadcrumbs(menus.value, activeSection.value, currentPage.value));
const pageInstanceKey = computed(() => {
    const editorContentId = activePayload.value.editor?.content?.id || 'new';

    return `${activeSection.value}:${editorContentId}`;
});

function adminSectionUrl(section: string): string {
    return section === 'dashboard' ? '/admin' : `/admin/${section}`;
}

function normalizeAdminTarget(target: string): URL {
    const rawTarget = String(target || 'dashboard');

    if (rawTarget.startsWith('/')) {
        return new URL(rawTarget, window.location.origin);
    }

    if (/^https?:\/\//i.test(rawTarget)) {
        return new URL(rawTarget);
    }

    return new URL(adminSectionUrl(rawTarget), window.location.origin);
}

async function navigate(target: string, options: NavigateOptions = {}) {
    isMobileSidebarOpen.value = false;

    const url = normalizeAdminTarget(target);

    if (url.origin !== window.location.origin || !url.pathname.startsWith('/admin')) {
        window.location.href = url.toString();
        return;
    }

    const nextPath = `${url.pathname}${url.search}`;
    const currentPath = `${window.location.pathname}${window.location.search}`;

    if (!options.force && nextPath === currentPath) {
        return;
    }

    navigationController?.abort();
    const controller = new AbortController();
    navigationController = controller;
    navigationLoading.value = true;

    try {
        const response = await fetch(nextPath, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            signal: controller.signal,
        });
        const contentType = response.headers.get('content-type') || '';

        if (!contentType.includes('application/json')) {
            window.location.href = nextPath;
            return;
        }

        const json = await response.json();

        if (!response.ok) {
            throw new Error(json.message || `页面加载失败：${response.status}`);
        }

        activePayload.value = json.payload || json;

        if (options.replace) {
            window.history.replaceState({ zfyAdmin: true }, '', nextPath);
        } else {
            window.history.pushState({ zfyAdmin: true }, '', nextPath);
        }
    } catch (error) {
        if (error instanceof DOMException && error.name === 'AbortError') {
            return;
        }

        ElMessage.error(error instanceof Error ? error.message : '页面加载失败');
    } finally {
        if (navigationController === controller) {
            navigationController = null;
            navigationLoading.value = false;
        }
    }
}

function refreshCurrentPage() {
    void navigate(`${window.location.pathname}${window.location.search}`, { force: true, replace: true });
}

function openMobileSidebar() {
    isMobileSidebarOpen.value = true;
}

function closeMobileSidebar() {
    isMobileSidebarOpen.value = false;
}

function handlePopState() {
    void navigate(`${window.location.pathname}${window.location.search}`, { force: true, replace: true });
}

onMounted(() => {
    window.history.replaceState({ zfyAdmin: true }, '', `${window.location.pathname}${window.location.search}`);
    window.addEventListener('popstate', handlePopState);
});

onBeforeUnmount(() => {
    navigationController?.abort();
    window.removeEventListener('popstate', handlePopState);
});
</script>

<template>
    <el-container class="zfy-admin-shell">
        <AdminSidebar
            :active-section="activeSection"
            :menus="menus"
            :mobile-open="isMobileSidebarOpen"
            @close="closeMobileSidebar"
            @navigate="navigate"
        />
        <button
            aria-label="关闭后台菜单"
            :class="['zfy-admin-sidebar-mask', { 'is-open': isMobileSidebarOpen }]"
            type="button"
            @click="closeMobileSidebar"
        />
        <el-container class="zfy-admin-workspace" direction="vertical">
            <AdminTopbar
                :breadcrumbs="breadcrumbs"
                :csrf="activePayload.csrf"
                :user="activePayload.current_user"
                @open-menu="openMobileSidebar"
                @refresh="refreshCurrentPage"
                @navigate="navigate"
            />
            <el-main v-loading="navigationLoading" class="zfy-admin-main">
                <AdminPage
                    :key="pageInstanceKey"
                    :current-page="currentPage"
                    :description="pageDescription"
                    :payload="activePayload"
                    :section="activeSection"
                    :title="pageTitle"
                    @navigate="navigate"
                />
            </el-main>
        </el-container>
    </el-container>
</template>
