import { computed, onBeforeUnmount, onMounted, shallowRef, toValue, watch } from 'vue';
import type { MaybeRefOrGetter } from 'vue';
import type { EditorRevision, EditorSaveStatus, EditorSnapshot } from './types';

interface AutosaveOptions {
    contentId: MaybeRefOrGetter<number | null>;
    baseUpdatedAt: MaybeRefOrGetter<string | null>;
    csrf: MaybeRefOrGetter<string>;
    routes: MaybeRefOrGetter<Record<string, string>>;
    getSnapshot: () => EditorSnapshot;
    applySnapshot: (snapshot: EditorSnapshot) => void;
}

interface LocalAutosave {
    updated_at: string;
    snapshot: EditorSnapshot;
}

export function useEditorAutosave(options: AutosaveOptions) {
    const draftKey = shallowRef(resolveDraftKey(toValue(options.contentId)));
    const status = shallowRef<EditorSaveStatus>('saved');
    const lastSavedAt = shallowRef<string | null>(toValue(options.baseUpdatedAt));
    const recoverableDraft = shallowRef<EditorRevision | null>(null);
    const initialized = shallowRef(false);
    let cloudRevisionId: number | null = null;
    let savedSignature = snapshotSignature(options.getSnapshot());
    let localTimer: number | undefined;
    let cloudTimer: number | undefined;

    const statusLabel = computed(() => ({
        saved: lastSavedAt.value ? `已保存 ${formatTime(lastSavedAt.value)}` : '已保存',
        unsaved: '有未保存更改',
        saving: '自动保存中',
        conflict: '检测到版本冲突',
        error: '自动保存失败',
    })[status.value]);

    const stopWatch = watch(
        () => snapshotSignature(options.getSnapshot()),
        (signature) => {
            if (!initialized.value) {
                return;
            }

            if (signature === savedSignature) {
                void clearAutosave();
                return;
            }

            status.value = 'unsaved';
            scheduleAutosave();
        },
        { flush: 'post' },
    );

    onMounted(async () => {
        await loadRecoverableDraft();
        initialized.value = true;
    });

    onBeforeUnmount(() => {
        stopWatch();
        window.clearTimeout(localTimer);
        window.clearTimeout(cloudTimer);

        if (snapshotSignature(options.getSnapshot()) !== savedSignature) {
            saveLocal();
        }
    });

    function scheduleAutosave(): void {
        window.clearTimeout(localTimer);
        window.clearTimeout(cloudTimer);
        localTimer = window.setTimeout(saveLocal, 500);
        cloudTimer = window.setTimeout(() => void saveCloud(), 5000);
    }

    function saveLocal(): void {
        const payload: LocalAutosave = {
            updated_at: new Date().toISOString(),
            snapshot: options.getSnapshot(),
        };

        try {
            window.localStorage.setItem(localStorageKey(), JSON.stringify(payload));
        } catch {
            // 云端自动保存仍会继续，存储空间不足不阻断编辑。
        }
    }

    async function saveCloud(): Promise<void> {
        const signature = snapshotSignature(options.getSnapshot());
        if (signature === savedSignature) {
            return;
        }

        status.value = 'saving';

        try {
            const json = await requestJson(String(toValue(options.routes).editor_autosave || '/admin/editor/autosave'), 'POST', {
                ...options.getSnapshot(),
                content_id: toValue(options.contentId),
                draft_key: draftKey.value,
                base_updated_at: toValue(options.baseUpdatedAt),
            });
            cloudRevisionId = Number(json.autosave?.id || 0) || null;
            lastSavedAt.value = json.autosave?.updated_at || new Date().toISOString();
            status.value = json.conflict ? 'conflict' : 'saved';
        } catch {
            status.value = 'error';
        }
    }

    async function loadRecoverableDraft(): Promise<void> {
        const local = readLocal();
        let cloud: EditorRevision | null = null;

        try {
            const endpoint = new URL(
                String(toValue(options.routes).editor_autosave_state || '/admin/editor/autosave'),
                window.location.origin,
            );
            const contentId = toValue(options.contentId);

            if (contentId) {
                endpoint.searchParams.set('content_id', String(contentId));
            } else {
                endpoint.searchParams.set('draft_key', draftKey.value);
            }

            const json = await requestJson(endpoint.toString(), 'GET');
            cloud = json.autosave || null;
        } catch {
            cloud = null;
        }

        const candidates: EditorRevision[] = [];
        if (cloud?.snapshot) {
            cloudRevisionId = cloud.id || null;
            candidates.push(cloud);
        }
        if (local?.snapshot) {
            candidates.push({
                id: 0,
                kind: 'autosave',
                draft_key: draftKey.value,
                title: String(local.snapshot.title || '本地自动保存'),
                summary: String(local.snapshot.markdown_cache || '').slice(0, 100),
                updated_at: local.updated_at,
                snapshot: local.snapshot,
            });
        }

        const currentSignature = snapshotSignature(options.getSnapshot());
        recoverableDraft.value = candidates
            .filter((item) => item.snapshot && snapshotSignature(item.snapshot) !== currentSignature)
            .sort((a, b) => timestamp(b.updated_at) - timestamp(a.updated_at))[0] || null;
    }

    function restoreRecoverableDraft(): void {
        const snapshot = recoverableDraft.value?.snapshot;
        if (!snapshot) {
            return;
        }

        options.applySnapshot(snapshot);
        recoverableDraft.value = null;
        status.value = 'unsaved';
        saveLocal();
        scheduleAutosave();
    }

    async function discardRecoverableDraft(): Promise<void> {
        const recovery = recoverableDraft.value;
        recoverableDraft.value = null;
        removeLocal();
        cloudRevisionId = null;

        if (!recovery?.id) {
            return;
        }

        const endpoint = String(toValue(options.routes).editor_autosave_discard || '/admin/editor/autosave/__REVISION__')
            .replace('__REVISION__', String(recovery.id));

        try {
            await requestJson(endpoint, 'DELETE');
        } catch {
            // 丢弃提示已经关闭，下一次加载仍可再次处理云端草稿。
        }
    }

    function markSaved(updatedAt?: string | null): void {
        window.clearTimeout(localTimer);
        window.clearTimeout(cloudTimer);
        savedSignature = snapshotSignature(options.getSnapshot());
        lastSavedAt.value = updatedAt || new Date().toISOString();
        status.value = 'saved';
        recoverableDraft.value = null;
        cloudRevisionId = null;
        removeLocal();
    }

    async function clearAutosave(): Promise<void> {
        window.clearTimeout(localTimer);
        window.clearTimeout(cloudTimer);
        status.value = 'saved';
        removeLocal();

        const revisionId = cloudRevisionId;
        cloudRevisionId = null;
        if (!revisionId) {
            return;
        }

        const endpoint = String(toValue(options.routes).editor_autosave_discard || '/admin/editor/autosave/__REVISION__')
            .replace('__REVISION__', String(revisionId));

        try {
            await requestJson(endpoint, 'DELETE');
        } catch {
            cloudRevisionId = revisionId;
        }
    }

    function readLocal(): LocalAutosave | null {
        try {
            const value = window.localStorage.getItem(localStorageKey());

            return value ? JSON.parse(value) as LocalAutosave : null;
        } catch {
            return null;
        }
    }

    function removeLocal(): void {
        window.localStorage.removeItem(localStorageKey());
    }

    function localStorageKey(): string {
        return `zfy.editor.autosave.${draftKey.value}`;
    }

    async function requestJson(url: string, method: string, body?: Record<string, unknown>): Promise<any> {
        const response = await fetch(url, {
            method,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': toValue(options.csrf),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body ? JSON.stringify(body) : undefined,
        });
        const json = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(json.message || `请求失败：${response.status}`);
        }

        return json;
    }

    return {
        draftKey,
        status,
        statusLabel,
        lastSavedAt,
        recoverableDraft,
        saveNow: saveCloud,
        markSaved,
        restoreRecoverableDraft,
        discardRecoverableDraft,
    };
}

function resolveDraftKey(contentId: number | null): string {
    const storageKey = `zfy.editor.draft-key.${contentId || 'new'}`;
    const current = window.sessionStorage.getItem(storageKey);

    if (current) {
        return current;
    }

    const key = typeof crypto.randomUUID === 'function'
        ? crypto.randomUUID()
        : `${Date.now().toString(16)}-0000-4000-8000-${Math.random().toString(16).slice(2).padEnd(12, '0').slice(0, 12)}`;
    window.sessionStorage.setItem(storageKey, key);

    return key;
}

function snapshotSignature(snapshot: EditorSnapshot): string {
    return JSON.stringify(snapshot);
}

function timestamp(value?: string | null): number {
    const result = value ? new Date(value).getTime() : 0;

    return Number.isFinite(result) ? result : 0;
}

function formatTime(value: string): string {
    const date = new Date(value);

    return Number.isNaN(date.getTime())
        ? ''
        : date.toLocaleTimeString('zh-CN', { hour: '2-digit', minute: '2-digit' });
}
