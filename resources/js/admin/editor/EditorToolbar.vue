<script setup lang="ts">
import { computed } from 'vue';
import ZfyIcon from '../icons/ZfyIcon.vue';
import { iconForEditorTool } from './editorToolbarIcons';
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

const toolbarRows = computed(() => {
    const primaryGroups = groupedTools.value.filter((group) => group.key !== 'shortcode');
    const shortcodeGroups = groupedTools.value.filter((group) => group.key === 'shortcode');

    return [
        { key: 'primary', groups: primaryGroups },
        { key: 'secondary', groups: shortcodeGroups },
    ].filter((row) => row.groups.length > 0);
});

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

function showToolText(_tool: EditorTool): boolean {
    return false;
}
</script>

<template>
    <div class="zfy-editor-toolbar" role="toolbar" aria-label="写文章工具栏">
        <div
            v-for="row in toolbarRows"
            :key="row.key"
            :class="['zfy-editor-toolbar-row', { 'is-secondary': row.key === 'secondary' }]"
        >
            <div v-for="group in row.groups" :key="group.key" :class="['zfy-editor-tool-group', `is-${group.key}`]">
                <template v-for="tool in group.tools" :key="tool.id">
                    <el-tooltip
                        v-if="tool.action === 'dropdown' && tool.children?.length"
                        :content="toolLabel(tool)"
                        placement="bottom"
                    >
                        <el-dropdown
                            trigger="click"
                            @command="(child: EditorTool) => emit('tool', child)"
                        >
                            <el-button class="zfy-editor-tool" :aria-label="toolLabel(tool)" :disabled="busy">
                                <ZfyIcon :name="iconForEditorTool(tool)" />
                                <span v-if="showToolText(tool)">{{ toolLabel(tool) }}</span>
                            </el-button>
                            <template #dropdown>
                                <el-dropdown-menu>
                                    <el-dropdown-item v-for="child in tool.children" :key="child.id" :command="child">
                                        <ZfyIcon :name="iconForEditorTool(child)" size="15" />
                                        <span>{{ child.label }}</span>
                                    </el-dropdown-item>
                                </el-dropdown-menu>
                            </template>
                        </el-dropdown>
                    </el-tooltip>

                    <el-tooltip v-else :content="toolLabel(tool)" placement="bottom">
                        <el-button
                            :class="[
                                'zfy-editor-tool',
                                {
                                    'has-text': showToolText(tool),
                                    'is-active': (tool.id === 'preview' && previewVisible) || (tool.id === 'fullscreen' && fullscreen),
                                },
                            ]"
                            :disabled="busy && !['preview', 'fullscreen', 'download'].includes(tool.action)"
                            :aria-label="toolLabel(tool)"
                            :type="buttonType(tool)"
                            @click="emit('tool', tool)"
                        >
                            <ZfyIcon :name="iconForEditorTool(tool)" />
                            <span v-if="showToolText(tool)">{{ toolLabel(tool) }}</span>
                        </el-button>
                    </el-tooltip>
                </template>
            </div>
        </div>
    </div>
</template>
