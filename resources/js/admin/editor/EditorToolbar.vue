<script setup lang="ts">
import * as ElementPlusIcons from '@element-plus/icons-vue';
import { computed, type Component } from 'vue';
import type { EditorTool } from './types';

const props = defineProps<{
    tools: EditorTool[];
    busy?: boolean;
    previewVisible?: boolean;
    fullscreen?: boolean;
}>();

const emit = defineEmits<{
    tool: [tool: EditorTool];
}>();

const groupedTools = computed(() => {
    const groups = new Map<string, EditorTool[]>();
    props.tools.forEach((tool) => {
        const group = tool.group || 'default';
        groups.set(group, [...(groups.get(group) || []), tool]);
    });

    return [...groups.entries()].map(([key, tools]) => ({ key, tools }));
});

function iconFor(name?: string): Component | null {
    if (!name) {
        return null;
    }

    return (ElementPlusIcons as unknown as Record<string, Component>)[name] || null;
}

function buttonType(tool: EditorTool): 'primary' | 'default' {
    return tool.primary || tool.action === 'publish' ? 'primary' : 'default';
}

function toolLabel(tool: EditorTool): string {
    if (tool.id === 'preview' && props.previewVisible) {
        return '关闭预览';
    }

    if (tool.id === 'fullscreen' && props.fullscreen) {
        return '退出全屏';
    }

    return tool.label;
}
</script>

<template>
    <div class="zfy-editor-toolbar" role="toolbar" aria-label="写文章工具栏">
        <div v-for="group in groupedTools" :key="group.key" class="zfy-editor-tool-group">
            <template v-for="tool in group.tools" :key="tool.id">
                <el-dropdown
                    v-if="tool.action === 'dropdown' && tool.children?.length"
                    trigger="click"
                    @command="(child: EditorTool) => emit('tool', child)"
                >
                    <el-button class="zfy-editor-tool" :disabled="busy">
                        <el-icon v-if="iconFor(tool.icon)"><component :is="iconFor(tool.icon)" /></el-icon>
                        <span>{{ tool.label }}</span>
                    </el-button>
                    <template #dropdown>
                        <el-dropdown-menu>
                            <el-dropdown-item v-for="child in tool.children" :key="child.id" :command="child">
                                {{ child.label }}
                            </el-dropdown-item>
                        </el-dropdown-menu>
                    </template>
                </el-dropdown>

                <el-tooltip v-else :content="toolLabel(tool)" placement="bottom">
                    <el-button
                        :class="['zfy-editor-tool', { 'is-active': (tool.id === 'preview' && previewVisible) || (tool.id === 'fullscreen' && fullscreen) }]"
                        :disabled="busy && !['preview', 'fullscreen', 'download'].includes(tool.action)"
                        :type="buttonType(tool)"
                        @click="emit('tool', tool)"
                    >
                        <el-icon v-if="iconFor(tool.icon)"><component :is="iconFor(tool.icon)" /></el-icon>
                        <span v-if="tool.primary || ['save', 'publish'].includes(tool.action)">{{ toolLabel(tool) }}</span>
                    </el-button>
                </el-tooltip>
            </template>
        </div>
    </div>
</template>
