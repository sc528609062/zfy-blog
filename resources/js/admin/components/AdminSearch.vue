<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue';
import { Search, Right } from '@element-plus/icons-vue';
import type { AdminMenuGroup } from '../useAdminMenu';
const props = defineProps<{ modelValue: boolean; menus: AdminMenuGroup[] }>();
const emit = defineEmits<{ 'update:modelValue': [value: boolean]; navigate: [section: string] }>();
const query = ref('');
const input = ref<{ focus: () => void }>();
const results = computed(() => props.menus.flatMap(group => group.items.map(item => ({ ...item, group: group.label }))).filter(item => `${item.group} ${item.label} ${item.key}`.toLowerCase().includes(query.value.trim().toLowerCase())));
watch(() => props.modelValue, async open => { if (open) { query.value = ''; await nextTick(); input.value?.focus(); } });
function select(section: string) { emit('update:modelValue', false); emit('navigate', section); }
</script>
<template>
    <el-dialog :model-value="modelValue" title="搜索菜单" width="min(560px, 94vw)" class="zfy-art-search" @update:model-value="emit('update:modelValue', $event)" @opened="input?.focus()">
        <el-input ref="input" v-model="query" :prefix-icon="Search" clearable placeholder="搜索菜单" aria-label="搜索菜单" @keydown.enter="results[0] && select(results[0].key)" />
        <div class="zfy-art-search-results">
            <button v-for="item in results" :key="item.key" @click="select(item.key)"><span>{{ item.label }}<small>{{ item.group }}</small></span><el-icon><Right /></el-icon></button>
            <el-empty v-if="!results.length" description="没有匹配的菜单" :image-size="64" />
        </div>
    </el-dialog>
</template>
