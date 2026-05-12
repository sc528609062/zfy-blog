<script setup lang="ts">
withDefaults(defineProps<{
    tone?: 'blue' | 'green' | 'amber' | 'slate';
}>(), {
    tone: 'blue',
});
</script>

<template>
    <div :class="['bits-spotlight-card', `tone-${tone}`]">
        <slot />
    </div>
</template>
