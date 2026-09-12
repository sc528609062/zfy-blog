<script setup lang="ts">
import { computed } from 'vue';
import {
    Brush,
    ChatDotRound,
    Close,
    Connection,
    Document,
    Files,
    Link,
    Menu,
    Monitor,
    Picture,
    Setting,
    ShoppingCart,
    User,
} from '@element-plus/icons-vue';
import type { AdminMenuGroup } from '../useAdminMenu';

const props = defineProps<{
    activeSection: string;
    menus: AdminMenuGroup[];
    mobileOpen: boolean;
    collapsed: boolean;
}>();

const emit = defineEmits<{
    navigate: [section: string];
    close: [];
}>();

const defaultOpeneds = computed(() =>
    props.menus
        .filter((group) => group.items.some((item) => item.key === props.activeSection))
        .map((group) => group.key),
);

const iconMap: Record<string, unknown> = {
    Brush,
    ChatDotRound,
    Connection,
    Document,
    Files,
    Link,
    Menu,
    Monitor,
    Picture,
    Setting,
    ShoppingCart,
    User,
};

function iconFor(name: string) {
    return iconMap[name] || Menu;
}
</script>

<template>
    <el-aside :class="['zfy-admin-sidebar', { 'is-mobile-open': mobileOpen }]">
        <div class="zfy-admin-sidebar-head">
            <a class="zfy-admin-brand" href="/admin" aria-label="zfy-blog 后台首页" @click.prevent="emit('navigate', 'dashboard')">
                <span>Z</span>
                <strong>zfy-blog</strong>
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
                :collapse="collapsed"
                :collapse-transition="false"
                popper-class="zfy-art-menu-popup"
                unique-opened
                @select="(index: string) => emit('navigate', index)"
            >
                <el-sub-menu
                    v-for="group in menus"
                    :key="group.key"
                    :index="group.key"
                >
                    <template #title>
                        <el-icon><component :is="iconFor(group.icon)" /></el-icon>
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
        <a class="zfy-sidebar-site" href="/" title="访问站点"><el-icon><Monitor /></el-icon><span v-if="!collapsed">访问站点</span></a>
    </el-aside>
</template>
