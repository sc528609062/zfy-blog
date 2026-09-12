<script setup lang="ts">
import { Check, Moon, Sunny } from '@element-plus/icons-vue';
import { adminColors, useAdminStore } from '../store';
defineProps<{ modelValue: boolean }>();
const emit = defineEmits<{ 'update:modelValue': [value: boolean] }>();
const store = useAdminStore();
</script>
<template>
    <el-drawer :model-value="modelValue" title="外观设置" size="min(340px, 94vw)" class="zfy-art-appearance" @update:model-value="emit('update:modelValue', $event)">
        <h3>主题模式</h3>
        <el-radio-group :model-value="store.dark" @change="store.toggleDark()"><el-radio-button :value="false"><el-icon><Sunny /></el-icon> 浅色</el-radio-button><el-radio-button :value="true"><el-icon><Moon /></el-icon> 深色</el-radio-button></el-radio-group>
        <h3>主题色</h3>
        <div class="zfy-art-swatches"><button v-for="color in adminColors" :key="color" :style="{ backgroundColor: color }" :aria-label="`主题色 ${color}`" :title="color" :aria-pressed="store.primary === color" @click="store.setPrimary(color)"><el-icon v-if="store.primary === color"><Check /></el-icon></button></div>
        <h3>界面布局</h3>
        <label class="zfy-art-setting"><span>折叠侧栏</span><el-switch :model-value="store.collapsed" aria-label="折叠侧栏" @change="store.toggleSidebar()" /></label>
        <label class="zfy-art-setting"><span>紧凑表格</span><el-switch :model-value="store.compact" aria-label="紧凑表格" @change="store.setCompact(Boolean($event))" /></label>
    </el-drawer>
</template>
