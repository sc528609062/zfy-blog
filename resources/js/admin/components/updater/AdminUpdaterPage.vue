<script setup lang="ts">
import { onMounted } from 'vue';
import UpdaterLogPanel from './UpdaterLogPanel.vue';
import UpdaterPreflightPanel from './UpdaterPreflightPanel.vue';
import UpdaterVersionPanel from './UpdaterVersionPanel.vue';
import { useUpdater } from './useUpdater';

const props = defineProps<{
    payload: Record<string, any>;
}>();

const updater = useUpdater({
    routes: props.payload.routes || {},
    csrf: props.payload.csrf || '',
});

onMounted(() => {
    updater.loadStatus();
});
</script>

<template>
    <div v-loading="updater.loading" class="updater-page">
        <UpdaterVersionPanel
            v-if="updater.status"
            v-model:version="updater.selectedVersion"
            :can-start="updater.canStart"
            :current-version="updater.status.current_version"
            :latest-version="updater.latestVersion"
            :repository="updater.status.repository"
            :running="updater.isRunning"
            :starting="updater.starting"
            :tags="updater.status.tags"
            @refresh="updater.loadStatus"
            @start="updater.startUpdate"
        />

        <div v-if="updater.status" class="updater-grid">
            <UpdaterPreflightPanel
                :checks="updater.status.preflight.checks"
                :ok="updater.status.preflight.ok"
            />
            <el-card shadow="never">
                <template #header>
                    <div class="zfy-card-title">
                        <div>
                            <h2>版本发布步骤</h2>
                            <p>先发布 Git tag，后台才能按版本更新</p>
                        </div>
                    </div>
                </template>
                <ol class="updater-guide">
                    <li>修改 <code>config/zfy.php</code> 的 <code>version</code>，例如 <code>1.0.1</code>。</li>
                    <li>提交代码后创建 tag，例如 <code>git tag v1.0.1</code>。</li>
                    <li>推送代码和 tag 到 Gitee，例如 <code>git push origin master --tags</code>。</li>
                    <li>回到本页面刷新状态，选择目标 tag 后点击在线更新。</li>
                </ol>
            </el-card>
        </div>

        <UpdaterLogPanel
            :active-log="updater.activeLog"
            :recent-logs="updater.status?.recent_logs || []"
            :running="updater.isRunning"
        />
    </div>
</template>

<style scoped>
.updater-page,
.updater-grid {
    display: grid;
    gap: 18px;
}

.updater-grid {
    grid-template-columns: minmax(0, 1fr) minmax(320px, .8fr);
    align-items: start;
}

.updater-guide {
    margin: 0;
    padding-left: 20px;
    color: #344054;
    line-height: 1.9;
}

.updater-guide code {
    border-radius: 4px;
    padding: 2px 5px;
    background: #f2f4f7;
}

@media (max-width: 1024px) {
    .updater-grid {
        grid-template-columns: 1fr;
    }
}
</style>
