import { computed, onUnmounted, reactive, shallowRef } from 'vue';
import { ElMessage } from 'element-plus';

export interface UpdaterCheck {
    key: string;
    label: string;
    ok: boolean;
    message: string;
}

export interface UpgradeLogPayload {
    id: number;
    from_version: string | null;
    to_version: string;
    status: 'pending' | 'running' | 'success' | 'failed';
    log: string | null;
    created_at: string | null;
    updated_at: string | null;
}

export interface UpdaterStatus {
    current_version: string;
    repository: string;
    remote: string;
    tags: string[];
    latest_version: string | null;
    preflight: {
        ok: boolean;
        checks: UpdaterCheck[];
    };
    running_log?: UpgradeLogPayload | null;
    recent_logs: UpgradeLogPayload[];
}

interface UseUpdaterOptions {
    routes: Record<string, string>;
    csrf: string;
}

export function useUpdater(options: UseUpdaterOptions) {
    const status = shallowRef<UpdaterStatus | null>(null);
    const activeLog = shallowRef<UpgradeLogPayload | null>(null);
    const selectedVersion = shallowRef('');
    const loading = shallowRef(false);
    const starting = shallowRef(false);
    let poller: number | undefined;

    const canStart = computed(() =>
        Boolean(status.value?.preflight.ok && selectedVersion.value && !starting.value && !isRunning.value),
    );
    const isRunning = computed(() => ['pending', 'running'].includes(activeLog.value?.status || ''));
    const latestVersion = computed(() => status.value?.latest_version || '');

    async function request<T>(url: string, init: RequestInit = {}): Promise<T> {
        const response = await fetch(url, {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': options.csrf,
                'X-Requested-With': 'XMLHttpRequest',
                ...(init.headers || {}),
            },
            ...init,
        });

        const body = await response.json().catch(() => ({}));
        if (!response.ok) {
            throw new Error(body.message || '请求失败');
        }

        return body as T;
    }

    async function loadStatus() {
        loading.value = true;
        try {
            const data = await request<UpdaterStatus>(options.routes.updater_status);
            status.value = data;
            selectedVersion.value = selectedVersion.value || data.latest_version || '';
            activeLog.value = data.running_log || activeLog.value;
            if (activeLog.value && isRunning.value) {
                startPolling(activeLog.value.id);
            }
        } catch (error) {
            ElMessage.error(error instanceof Error ? error.message : '无法读取更新状态');
        } finally {
            loading.value = false;
        }
    }

    async function startUpdate() {
        if (!selectedVersion.value) {
            ElMessage.warning('请选择要更新的版本');
            return;
        }

        starting.value = true;
        try {
            const data = await request<{ message: string; log: UpgradeLogPayload }>(options.routes.updater_run, {
                method: 'POST',
                body: JSON.stringify({ version: selectedVersion.value }),
            });
            activeLog.value = data.log;
            ElMessage.success(data.message);
            startPolling(data.log.id);
        } catch (error) {
            ElMessage.error(error instanceof Error ? error.message : '无法启动更新');
        } finally {
            starting.value = false;
        }
    }

    async function loadLog(logId: number) {
        const url = options.routes.updater_log.replace('__LOG__', String(logId));
        const data = await request<{ log: UpgradeLogPayload }>(url);
        activeLog.value = data.log;

        if (!['pending', 'running'].includes(data.log.status)) {
            stopPolling();
            await loadStatus();
        }
    }

    function startPolling(logId: number) {
        stopPolling();
        poller = window.setInterval(() => {
            loadLog(logId).catch((error) => {
                stopPolling();
                ElMessage.error(error instanceof Error ? error.message : '无法读取更新日志');
            });
        }, 3000);
    }

    function stopPolling() {
        if (poller) {
            window.clearInterval(poller);
            poller = undefined;
        }
    }

    onUnmounted(stopPolling);

    return reactive({
        status,
        activeLog,
        selectedVersion,
        loading,
        starting,
        canStart,
        isRunning,
        latestVersion,
        loadStatus,
        startUpdate,
    });
}
