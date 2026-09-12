<script setup lang="ts">
import { computed } from 'vue';
import { Document, ShoppingCart, Goods, Link } from '@element-plus/icons-vue';

const props = withDefaults(defineProps<{
    label: string;
    value: string | number;
    trend?: string;
    tone?: 'blue' | 'green' | 'amber' | 'rose';
}>(), {
    trend: '运行正常',
    tone: 'blue',
});

const displayValue = computed(() =>
    typeof props.value === 'number' ? new Intl.NumberFormat('zh-CN').format(props.value) : props.value,
);
</script>

<template>
    <div :class="['bits-metric-card', `tone-${tone}`]">
        <div class="zfy-art-metric-top"><span>{{ label }}</span><div class="zfy-art-metric-icon"><el-icon><component :is="{blue:Document,green:ShoppingCart,amber:Goods,rose:Link}[tone]" /></el-icon></div></div>
        <strong>{{ displayValue }}</strong>
        <small>{{ trend }}</small>
    </div>
</template>
