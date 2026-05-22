<script setup lang="ts">
import { computed } from 'vue';
import { zfyIconSet } from './zfyIconSet';

const props = withDefaults(defineProps<{
    name?: string | null;
    size?: number | string;
    strokeWidth?: number | string;
    title?: string;
}>(), {
    size: 18,
    strokeWidth: 1.8,
});

const definition = computed(() => (props.name ? zfyIconSet[props.name as keyof typeof zfyIconSet] || null : null));
const sizeValue = computed(() => (typeof props.size === 'number' ? `${props.size}px` : props.size));
</script>

<template>
    <svg
        v-if="definition"
        class="zfy-icon"
        :height="sizeValue"
        :viewBox="definition.viewBox"
        :width="sizeValue"
        aria-hidden="true"
        fill="none"
        stroke="currentColor"
        stroke-linecap="round"
        stroke-linejoin="round"
        :stroke-width="strokeWidth"
        xmlns="http://www.w3.org/2000/svg"
    >
        <title v-if="title">{{ title }}</title>
        <path v-for="path in definition.paths" :key="path" :d="path" />
    </svg>
</template>
