<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, shallowRef, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAdminStore } from './store';
import { Close, ArrowDown, Refresh } from '@element-plus/icons-vue';
import { ElMessage } from 'element-plus';
import AdminPage from './components/AdminPage.vue';
import AdminSidebar from './components/AdminSidebar.vue';
import AdminTopbar from './components/AdminTopbar.vue';
import AdminSearch from './components/AdminSearch.vue';
import AdminAppearance from './components/AdminAppearance.vue';
import { buildAdminBreadcrumbs, findAdminMenuItem, type AdminMenuGroup, type AdminPageDefinition } from './useAdminMenu';

const props = defineProps<{
    payload: Record<string, any>;
}>();

interface NavigateOptions {
    force?: boolean;
    replace?: boolean;
}

const activePayload = shallowRef<Record<string, any>>({ ...props.payload });
const router = useRouter();
const route = useRoute();
const store = useAdminStore();
let appliedPath = `${window.location.pathname}${window.location.search}`;
const isMobileSidebarOpen = shallowRef(false);
const mobileQuery = window.matchMedia('(max-width: 900px)');
const isMobile = shallowRef(mobileQuery.matches);
const searchVisible = shallowRef(false);
const appearanceVisible = shallowRef(false);
const navigationLoading = shallowRef(false);
const pageRevision = shallowRef(0);
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

    return `${activeSection.value}:${editorContentId}:${pageRevision.value}`;
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
        if (options.force) pageRevision.value++;
        appliedPath = nextPath;
        store.visit(nextPath, json.payload?.current_page?.label || json.current_page?.label || '后台');

        if (options.replace) {
            await router.replace(nextPath);
        } else {
            await router.push(nextPath);
        }
        await nextTick();
        document.querySelector('.zfy-admin-tab.active')?.scrollIntoView({ block: 'nearest', inline: 'nearest' });
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
    if (isMobile.value) isMobileSidebarOpen.value = !isMobileSidebarOpen.value;
    else store.toggleSidebar();
}

function closeMobileSidebar() {
    isMobileSidebarOpen.value = false;
}

function handleViewport() { isMobile.value = mobileQuery.matches; isMobileSidebarOpen.value = false; }
function handleKey(event: KeyboardEvent) {
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') { event.preventDefault(); searchVisible.value = !searchVisible.value; }
    if (event.key === 'Escape') closeMobileSidebar();
}
function tabsCommand(command: string) {
    if (command === 'refresh') refreshCurrentPage();
    if (command === 'others') store.closeOthers(appliedPath);
    if (command === 'all') { store.tabs = []; store.visit('/admin', '首页'); void navigate('/admin'); }
}

onMounted(() => {
    store.applyAppearance();
    store.visit(appliedPath, pageTitle.value);
    mobileQuery.addEventListener('change', handleViewport);
    document.addEventListener('keydown', handleKey);
});

watch(() => route.fullPath, path => {
    if (path !== appliedPath) void navigate(path, { force: true, replace: true });
});

function closeTab(path: string) {
    store.close(path);
    if (path === appliedPath) void navigate(store.tabs.at(-1)?.path || '/admin', { replace: true });
}

onBeforeUnmount(() => {
    navigationController?.abort();
    mobileQuery.removeEventListener('change', handleViewport);
    document.removeEventListener('keydown', handleKey);
});
</script>

<template>
    <el-container :class="['zfy-admin-shell', { 'is-collapsed': store.collapsed && !isMobile, 'is-compact': store.compact }]">
        <AdminSidebar
            :active-section="activeSection"
            :menus="menus"
            :mobile-open="isMobileSidebarOpen"
            :collapsed="store.collapsed && !isMobile"
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
                :mobile="isMobile"
                :menus="menus"
                @open-menu="openMobileSidebar"
                @search="searchVisible = true"
                @appearance="appearanceVisible = true"
                @refresh="refreshCurrentPage"
                @navigate="navigate"
            />
            <div class="zfy-art-tabs-bar">
                <nav class="zfy-admin-tabs" aria-label="已打开页面">
                    <div v-for="tab in store.tabs" :key="tab.path" :class="['zfy-admin-tab', { active: tab.path === route.fullPath }]">
                        <a :href="tab.path" :aria-current="tab.path === route.fullPath ? 'page' : undefined" @click.prevent="navigate(tab.path)">{{ tab.title }}</a>
                        <el-button v-if="store.tabs.length > 1" :icon="Close" text circle size="small" :aria-label="`关闭${tab.title}`" @click="closeTab(tab.path)" />
                    </div>
                </nav>
                <el-dropdown trigger="click" @command="tabsCommand"><el-button :icon="ArrowDown" class="zfy-art-tab-more" aria-label="页签操作" title="页签操作" /><template #dropdown><el-dropdown-menu><el-dropdown-item command="refresh" :icon="Refresh">刷新当前页</el-dropdown-item><el-dropdown-item command="others">关闭其他页签</el-dropdown-item><el-dropdown-item command="all">关闭全部页签</el-dropdown-item></el-dropdown-menu></template></el-dropdown>
            </div>
            <el-main v-loading="navigationLoading" class="zfy-admin-main">
                <div v-if="currentPage.kind !== 'editor'" class="zfy-art-page-heading"><h1>{{ pageTitle }}</h1><span v-if="activeSection === 'dashboard'">{{ activePayload.today }}</span></div>
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
        <AdminSearch v-model="searchVisible" :menus="menus" @navigate="navigate" />
        <AdminAppearance v-model="appearanceVisible" />
    </el-container>
</template>
