<script setup lang="ts">
import CoverImageField from './CoverImageField.vue';
import type { EditorCategory, EditorForm, EditorMediaConfig, EditorOption, SavedContent } from './types';

const props = defineProps<{
    contentTypes: EditorOption[];
    categories: EditorCategory[];
    routes?: Record<string, string>;
    media?: EditorMediaConfig;
    busy?: boolean;
    previewLoading?: boolean;
    savedContent?: SavedContent | null;
}>();

const form = defineModel<EditorForm>({ required: true });

const emit = defineEmits<{
    save: [];
    publish: [];
    preview: [];
}>();

function visitSavedContent(): void {
    if (props.savedContent?.show_url) {
        window.location.href = props.savedContent.show_url;
    }
}
</script>

<template>
    <aside class="zfy-editor-sidebar">
        <el-card shadow="never">
            <template #header>发布设置</template>
            <el-form label-position="top">
                <el-form-item label="内容类型">
                    <el-select v-model="form.type" class="w-full">
                        <el-option v-for="type in contentTypes" :key="type.value" :label="type.label" :value="type.value" />
                    </el-select>
                </el-form-item>
                <el-form-item label="状态">
                    <el-select v-model="form.status" class="w-full">
                        <el-option label="草稿" value="draft" />
                        <el-option label="发布" value="published" />
                    </el-select>
                </el-form-item>
                <el-form-item label="分类">
                    <el-select v-model="form.category_id" class="w-full" clearable>
                        <el-option v-for="category in categories" :key="category.id" :label="category.name" :value="category.id" />
                    </el-select>
                </el-form-item>
                <el-form-item label="标签">
                    <el-input v-model="form.tags" placeholder="多个标签用英文逗号分隔" />
                </el-form-item>
                <el-form-item label="封面">
                    <CoverImageField v-model="form.cover_url" :media="media" :routes="routes" />
                </el-form-item>
                <el-form-item label="摘要">
                    <el-input v-model="form.excerpt" :rows="4" type="textarea" />
                </el-form-item>

                <div class="zfy-editor-sidebar-actions">
                    <el-button :loading="previewLoading" @click="emit('preview')">预览</el-button>
                    <el-button :loading="busy" @click="emit('save')">保存草稿</el-button>
                    <el-button :loading="busy" type="primary" @click="emit('publish')">发布文章</el-button>
                </div>
            </el-form>
        </el-card>

        <el-card v-if="savedContent" shadow="never">
            <template #header>当前内容</template>
            <dl class="zfy-editor-saved">
                <div>
                    <dt>ID</dt>
                    <dd>{{ savedContent.id }}</dd>
                </div>
                <div>
                    <dt>Slug</dt>
                    <dd>{{ savedContent.slug }}</dd>
                </div>
                <div>
                    <dt>状态</dt>
                    <dd>{{ savedContent.status }}</dd>
                </div>
            </dl>
            <el-button v-if="savedContent.show_url" class="w-full" @click="visitSavedContent">查看文章</el-button>
        </el-card>
    </aside>
</template>
