<script setup lang="ts">
import { computed } from 'vue';

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
        <span>{{ label }}</span>
        <strong>{{ displayValue }}</strong>
        <small>{{ trend }}</small>
    </div>
</template>
