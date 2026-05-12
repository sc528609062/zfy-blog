<script setup lang="ts">
import { computed } from 'vue';
import { ElMessage } from 'element-plus';
import AdminDataTable from './AdminDataTable.vue';
import SettingsForm from './SettingsForm.vue';
import BitsGradientText from './bits/BitsGradientText.vue';
import BitsMetricCard from './bits/BitsMetricCard.vue';
import BitsSpotlightCard from './bits/BitsSpotlightCard.vue';
import type { AdminPageDefinition } from '../useAdminMenu';

const props = defineProps<{
    section: string;
    title: string;
    description: string;
    payload: Record<string, any>;
    currentPage?: AdminPageDefinition;
}>();

const stats = computed(() => props.payload.stats || {});
const contents = computed(() => props.payload.contents || []);
const orders = computed(() => props.payload.orders || []);
const themes = computed(() => props.payload.themes || []);
const plugins = computed(() => props.payload.plugins || []);
const layouts = computed(() => props.payload.layouts || []);
const pageKind = computed(() => props.currentPage?.kind || 'placeholder');
const tableRows = computed(() => props.payload.data_rows || (props.section === 'orders' ? orders.value : contents.value));
const settingsSchema = computed(() => props.payload.settings_schema || []);

function submitPost(url: string, data: Record<string, string | number | boolean | null> = {}) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = url;

    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value = props.payload.csrf || '';
    form.appendChild(csrf);

    Object.entries(data).forEach(([name, value]) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value == null ? '' : String(value);
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
}

function copyPlaceholder() {
    ElMessage.success('页面入口已保留，可继续接入真实业务');
}

function goAdmin(section: string) {
    window.location.href = `/admin/${section}`;
}
</script>

<template>
    <section class="zfy-admin-page">
        <div class="zfy-page-heading">
            <div>
                <p class="zfy-page-kicker">zfy-blog admin</p>
                <h1><BitsGradientText :text="title" /></h1>
                <span>{{ description }} · {{ payload.today }}</span>
            </div>
            <el-space wrap>
                <el-button @click="goAdmin('dashboard')">仪表盘</el-button>
                <el-button type="primary" @click="goAdmin('editor')">写文章</el-button>
            </el-space>
        </div>

        <template v-if="pageKind === 'dashboard'">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <BitsMetricCard label="文章总数" :value="stats.contents || 0" trend="内容资产" tone="blue" />
                <BitsMetricCard label="订单总数" :value="stats.orders || 0" trend="商城交易" tone="green" />
                <BitsMetricCard label="商品总数" :value="stats.products || 0" trend="核心商城" tone="amber" />
                <BitsMetricCard label="链接总数" :value="stats.links || 0" trend="增强友链" tone="rose" />
            </div>

            <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
                <BitsSpotlightCard class="xl:col-span-2">
                    <div class="zfy-card-title">
                        <div>
                            <h2>运营概览</h2>
                            <p>清爽后台视图，独立于前台主题。</p>
                        </div>
                        <el-tag type="success">运行中</el-tag>
                    </div>
                    <div class="zfy-flow-line" />
                </BitsSpotlightCard>

                <BitsSpotlightCard tone="green">
                    <div class="zfy-card-title">
                        <div>
                            <h2>快捷操作</h2>
                            <p>常用后台入口</p>
                        </div>
                    </div>
                    <div class="zfy-quick-grid">
                        <el-button @click="goAdmin('editor')">写文章</el-button>
                        <el-button @click="goAdmin('media')">媒体库</el-button>
                        <el-button @click="goAdmin('themes')">主题</el-button>
                        <el-button @click="goAdmin('products')">商品</el-button>
                    </div>
                </BitsSpotlightCard>
            </div>

            <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                <el-card shadow="never">
                    <template #header>最新文章</template>
                    <AdminDataTable :rows="contents" />
                </el-card>
                <el-card shadow="never">
                    <template #header>最新订单</template>
                    <AdminDataTable :rows="orders" />
                </el-card>
            </div>
        </template>

        <template v-else-if="pageKind === 'table'">
            <el-card shadow="never">
                <template #header>
                    <div class="zfy-card-title">
                        <div>
                            <h2>{{ title }}</h2>
                            <p>{{ description }}</p>
                        </div>
                        <el-space wrap>
                            <el-button>批量操作</el-button>
                            <el-button>导出</el-button>
                            <el-button type="primary">新建</el-button>
                        </el-space>
                    </div>
                </template>
                <AdminDataTable :rows="tableRows" />
            </el-card>
        </template>

        <template v-else-if="pageKind === 'editor'">
            <div class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1fr)_320px]">
                <el-card shadow="never">
                    <template #header>写文章</template>
                    <el-input model-value="《幻境战纪》1.2版本更新解析" size="large" />
                    <div class="zfy-editor-toolbar">
                        <el-button>段落</el-button>
                        <el-button>B</el-button>
                        <el-button>I</el-button>
                        <el-button>链接</el-button>
                        <el-button>图片</el-button>
                    </div>
                    <el-input
                        type="textarea"
                        :rows="18"
                        model-value="这里是文章编辑器示例。后续可以接入 Markdown、富文本或块编辑器。"
                    />
                </el-card>
                <el-card shadow="never">
                    <template #header>发布设置</template>
                    <el-form label-position="top">
                        <el-form-item label="状态"><el-select model-value="draft"><el-option label="草稿" value="draft" /><el-option label="公开" value="published" /></el-select></el-form-item>
                        <el-form-item label="分类"><el-input model-value="游戏攻略" /></el-form-item>
                        <el-form-item label="标签"><el-input model-value="更新, 攻略" /></el-form-item>
                        <el-button class="w-full" type="primary">保存草稿</el-button>
                    </el-form>
                </el-card>
            </div>
        </template>

        <template v-else-if="pageKind === 'themes'">
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2 xl:grid-cols-3">
                <el-card v-for="theme in themes" :key="theme.slug" shadow="never">
                    <div class="zfy-theme-preview">
                        <span>{{ theme.name }}</span>
                    </div>
                    <div class="zfy-card-title mt-4">
                        <div>
                            <h2>{{ theme.name }}</h2>
                            <p>{{ theme.slug }} · v{{ theme.version }}</p>
                        </div>
                        <el-tag :type="theme.is_active ? 'success' : 'info'">{{ theme.is_active ? '当前启用' : '可切换' }}</el-tag>
                    </div>
                    <el-button
                        class="mt-4 w-full"
                        :disabled="theme.is_active"
                        type="primary"
                        @click="submitPost(payload.routes.theme_activate, { slug: theme.slug })"
                    >
                        {{ theme.is_active ? '已启用' : '启用此主题' }}
                    </el-button>
                </el-card>
            </div>
        </template>

        <template v-else-if="pageKind === 'plugins'">
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2 xl:grid-cols-3">
                <el-card v-for="plugin in plugins" :key="plugin.slug" shadow="never">
                    <div class="zfy-card-title">
                        <div>
                            <h2>{{ plugin.name }}</h2>
                            <p>{{ plugin.slug }} · v{{ plugin.version }}</p>
                        </div>
                        <el-switch :model-value="plugin.enabled" @change="submitPost(plugin.toggle_url)" />
                    </div>
                    <el-divider />
                    <el-tag v-for="permission in plugin.permissions" :key="permission" class="mr-2 mb-2">{{ permission }}</el-tag>
                </el-card>
            </div>
        </template>

        <template v-else-if="pageKind === 'builder'">
            <div class="grid grid-cols-1 gap-5 xl:grid-cols-[220px_minmax(0,1fr)]">
                <BitsSpotlightCard tone="slate">
                    <h2 class="mb-3 text-base font-semibold">组件库</h2>
                    <div class="zfy-builder-parts">
                        <el-button>内容流</el-button>
                        <el-button>轮播</el-button>
                        <el-button>榜单</el-button>
                        <el-button>VIP</el-button>
                        <el-button>自定义 HTML</el-button>
                    </div>
                </BitsSpotlightCard>
                <el-card shadow="never">
                    <template #header>页面布局 JSON</template>
                    <el-collapse>
                        <el-collapse-item v-for="layout in layouts" :key="layout.id" :title="layout.title" :name="layout.id">
                            <el-input type="textarea" :rows="12" :model-value="layout.schema" />
                            <el-button class="mt-3" type="primary">保存布局</el-button>
                        </el-collapse-item>
                    </el-collapse>
                </el-card>
            </div>
        </template>

        <template v-else-if="pageKind === 'settings'">
            <SettingsForm :schema="settingsSchema" />
        </template>

        <template v-else>
            <BitsSpotlightCard class="zfy-placeholder-card" tone="blue">
                <el-tag effect="plain">{{ currentPage ? '菜单入口' : '后台' }}</el-tag>
                <h2><BitsGradientText :text="title" subtle /></h2>
                <p>{{ description }}。这个功能页面还没有接入实际业务，当前先保留入口和说明，方便后台菜单结构先完整起来。</p>
                <el-space wrap>
                    <el-button type="primary" @click="goAdmin('dashboard')">返回仪表盘</el-button>
                    <el-button @click="copyPlaceholder">记录占位</el-button>
                </el-space>
            </BitsSpotlightCard>
        </template>
    </section>
</template>
