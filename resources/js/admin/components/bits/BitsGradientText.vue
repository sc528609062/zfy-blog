<script setup lang="ts">
withDefaults(defineProps<{
    text: string;
    subtle?: boolean;
}>(), {
    subtle: false,
});
</script>

<template>
    <span :class="['bits-gradient-text', { 'is-subtle': subtle }]">{{ text }}</span>
</template>
