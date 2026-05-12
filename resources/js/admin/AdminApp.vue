<script setup lang="ts">
import { computed } from 'vue';
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

function navigate(section: string) {
    window.location.href = `/admin/${section}`;
}
</script>

<template>
    <el-container class="zfy-admin-shell">
        <AdminSidebar :active-section="activeSection" @navigate="navigate" />
        <el-container class="zfy-admin-workspace">
            <AdminTopbar
                :description="pageDescription"
                :title="pageTitle"
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
