import { ref, shallowRef, toValue } from 'vue';
import type { MaybeRefOrGetter } from 'vue';
import type { MediaLibraryItem, MediaLibraryMeta } from './types';

interface MediaQueryOptions {
    query?: string;
    page?: number;
    perPage?: number;
    type?: 'all' | 'image';
}

interface MediaLibraryResponse {
    items?: unknown[];
    meta?: Record<string, unknown>;
    media?: unknown;
    data?: unknown;
}

interface UploadResponse {
    media?: unknown;
    data?: {
        media?: unknown;
    };
}

export function useMediaLibrary(libraryUrl: MaybeRefOrGetter<string>, uploadUrl: MaybeRefOrGetter<string>) {
    const items = ref<MediaLibraryItem[]>([]);
    const meta = shallowRef<MediaLibraryMeta>({
        currentPage: 1,
        lastPage: 1,
        perPage: 24,
        total: 0,
    });
    const loading = shallowRef(false);
    const uploading = shallowRef(false);

    async function loadMedia(options: MediaQueryOptions = {}): Promise<MediaLibraryItem[]> {
        const endpoint = resolveUrl(libraryUrl);
        const url = new URL(endpoint, window.location.origin);
        const query = (options.query || '').trim();
        const page = Math.max(1, options.page || 1);
        const perPage = Math.max(1, options.perPage || meta.value.perPage || 24);
        const type = options.type || 'image';

        if (query) {
            url.searchParams.set('q', query);
        }

        url.searchParams.set('page', String(page));
        url.searchParams.set('per_page', String(perPage));

        if (type) {
            url.searchParams.set('type', type);
        }

        loading.value = true;

        try {
            const json = await requestJson<MediaLibraryResponse>(url.toString());
            const list = normalizeItems(json.items || json.media || json.data);

            items.value = list;
            meta.value = {
                currentPage: Number(getMetaValue(json.meta, 'current_page', page)),
                lastPage: Number(getMetaValue(json.meta, 'last_page', 1)),
                perPage: Number(getMetaValue(json.meta, 'per_page', perPage)),
                total: Number(getMetaValue(json.meta, 'total', list.length)),
            };

            return list;
        } finally {
            loading.value = false;
        }
    }

    async function uploadMedia(file: File, directory: string): Promise<MediaLibraryItem> {
        const endpoint = resolveUrl(uploadUrl);
        const formData = new FormData();
        formData.append('file', file);
        formData.append('directory', directory || 'editor/images');

        uploading.value = true;

        try {
            const json = await requestJson<UploadResponse>(endpoint, {
                method: 'POST',
                body: formData,
            });
            const media = normalizeItem(json.media || json.data?.media);

            items.value = [media, ...items.value.filter((item) => item.id !== media.id)];
            meta.value = {
                ...meta.value,
                total: meta.value.total + 1,
            };

            return media;
        } finally {
            uploading.value = false;
        }
    }

    return {
        items,
        meta,
        loading,
        uploading,
        loadMedia,
        uploadMedia,
    };
}

export function formatMediaSize(size: number): string {
    if (!Number.isFinite(size) || size <= 0) {
        return '未知大小';
    }

    const units = ['B', 'KB', 'MB', 'GB'];
    let value = size;
    let unitIndex = 0;

    while (value >= 1024 && unitIndex < units.length - 1) {
        value /= 1024;
        unitIndex++;
    }

    return `${value >= 10 || unitIndex === 0 ? Math.round(value) : value.toFixed(1)} ${units[unitIndex]}`;
}

function resolveUrl(source: MaybeRefOrGetter<string>): string {
    const value = toValue(source).trim();

    if (!value) {
        throw new Error('媒体库接口未配置');
    }

    return value;
}

async function requestJson<T>(url: string, init: RequestInit = {}): Promise<T> {
    const response = await fetch(url, {
        ...init,
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
            'X-Requested-With': 'XMLHttpRequest',
            ...(init.body instanceof FormData ? {} : { 'Content-Type': 'application/json' }),
            ...(init.headers || {}),
        },
    });

    const json = await response.json().catch(() => ({}));

    if (!response.ok) {
        const firstError = Object.values(json.errors || {})[0];
        const message = Array.isArray(firstError) ? firstError[0] : json.message;
        throw new Error(message || `请求失败：${response.status}`);
    }

    return json as T;
}

function normalizeItems(value: unknown): MediaLibraryItem[] {
    if (!Array.isArray(value)) {
        return [];
    }

    return value.map(normalizeItem);
}

function normalizeItem(raw: unknown): MediaLibraryItem {
    const item = (raw || {}) as Record<string, any>;
    const url = String(item.url || item.thumb_url || item.path || '');
    const createdAt = String(item.created_at || item.createdAt || '');

    return {
        id: Number(item.id || 0),
        name: String(item.name || item.title || item.original_name || '未命名图片'),
        path: String(item.path || ''),
        url,
        thumbUrl: String(item.thumb_url || url),
        disk: String(item.disk || 'public'),
        type: String(item.type || 'image'),
        mime: item.mime ? String(item.mime) : null,
        size: Number(item.size || 0),
        directory: String(item.directory || item.metadata?.directory || ''),
        storageDirectory: item.storage_directory ? String(item.storage_directory) : undefined,
        createdAt: createdAt || null,
    };
}

function getMetaValue(meta: Record<string, unknown> | undefined, key: string, fallback: number): number {
    if (!meta) {
        return fallback;
    }

    const value = meta[key];
    return Number(value ?? fallback);
}
