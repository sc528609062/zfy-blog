<script setup lang="ts">
import { computed, shallowRef } from 'vue';
import AdminPage from './components/AdminPage.vue';
import AdminSidebar from './components/AdminSidebar.vue';
import AdminTopbar from './components/AdminTopbar.vue';
import { findAdminMenuItem, type AdminMenuGroup, type AdminPageDefinition } from './useAdminMenu';

const props = defineProps<{
    payload: Record<string, any>;
}>();

const activeSection = computed(() => props.payload.section || 'dashboard');
const menus = computed<AdminMenuGroup[]>(() => props.payload.admin_menu || []);
const currentPage = computed<AdminPageDefinition>(() => props.payload.current_page || findAdminMenuItem(menus.value, activeSection.value) || {
    key: activeSection.value,
    label: '后台',
    description: '后台管理页面',
    kind: 'placeholder',
});
const pageTitle = computed(() => currentPage.value.label || '后台');
const pageDescription = computed(() => currentPage.value.description || '后台管理页面');
const isMobileSidebarOpen = shallowRef(false);

function navigate(section: string) {
    isMobileSidebarOpen.value = false;
    window.location.href = `/admin/${section}`;
}

function openMobileSidebar() {
    isMobileSidebarOpen.value = true;
}

function closeMobileSidebar() {
    isMobileSidebarOpen.value = false;
}
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
                :description="pageDescription"
                :title="pageTitle"
                @open-menu="openMobileSidebar"
                @navigate="navigate"
            />
            <el-main class="zfy-admin-main">
                <AdminPage
                    :current-page="currentPage"
                    :description="pageDescription"
                    :payload="payload"
                    :section="activeSection"
                    :title="pageTitle"
                />
            </el-main>
        </el-container>
    </el-container>
</template>
