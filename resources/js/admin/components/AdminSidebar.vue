<script setup lang="ts">
import { computed } from 'vue';
import { Close } from '@element-plus/icons-vue';
import { adminMenus } from '../useAdminMenu';

const props = defineProps<{
    activeSection: string;
    mobileOpen: boolean;
}>();

const emit = defineEmits<{
    navigate: [section: string];
    close: [];
}>();

const defaultOpeneds = computed(() =>
    adminMenus
        .filter((group) => group.items.some((item) => item.key === props.activeSection))
        .map((group) => group.label),
);
</script>

<template>
    <el-aside :class="['zfy-admin-sidebar', { 'is-mobile-open': mobileOpen }]">
        <div class="zfy-admin-sidebar-head">
            <a class="zfy-admin-brand" href="/admin">
                <span>Z</span>
                <strong>zfy-blog</strong>
                <small>站点后台</small>
            </a>
            <el-button
                aria-label="关闭后台菜单"
                class="zfy-sidebar-close"
                :icon="Close"
                circle
                @click="emit('close')"
            />
        </div>

        <el-scrollbar class="zfy-admin-menu-scroll">
            <el-menu
                :default-active="activeSection"
                :default-openeds="defaultOpeneds"
                class="zfy-admin-menu"
                unique-opened
                @select="(index: string) => emit('navigate', index)"
            >
                <el-sub-menu
                    v-for="group in adminMenus"
                    :key="group.label"
                    :index="group.label"
                >
                    <template #title>
                        <el-icon><component :is="group.icon" /></el-icon>
                        <span>{{ group.label }}</span>
                    </template>
                    <el-menu-item
                        v-for="item in group.items"
                        :key="item.key"
                        :index="item.key"
                    >
                        {{ item.label }}
                    </el-menu-item>
                </el-sub-menu>
            </el-menu>
        </el-scrollbar>
    </el-aside>
</template>
