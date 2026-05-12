<script setup lang="ts">
import { computed, shallowRef } from 'vue';
import AdminPage from './components/AdminPage.vue';
import AdminSidebar from './components/AdminSidebar.vue';
import AdminTopbar from './components/AdminTopbar.vue';
import { findAdminMenuItem } from './useAdminMenu';

const props = defineProps<{
    payload: Record<string, any>;
}>();

const activeSection = computed(() => props.payload.section || 'dashboard');
const currentItem = computed(() => findAdminMenuItem(activeSection.value));
const pageTitle = computed(() => currentItem.value?.label || '后台');
const pageDescription = computed(() => currentItem.value?.description || '后台管理页面');
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
                    :current-item="currentItem"
                    :description="pageDescription"
                    :payload="payload"
                    :section="activeSection"
                    :title="pageTitle"
                />
            </el-main>
        </el-container>
    </el-container>
</template>
