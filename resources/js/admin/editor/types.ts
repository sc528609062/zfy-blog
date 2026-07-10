export interface EditorTool {
    id: string;
    label: string;
    icon?: string;
    action: string;
    command?: string;
    prefix?: string;
    suffix?: string;
    placeholder?: string;
    snippet?: string;
    block?: boolean;
    status?: 'draft' | 'published';
    group?: string;
    primary?: boolean;
    requiresRawHtml?: boolean;
    children?: EditorTool[];
}

export type EditorPromptFieldType = 'text' | 'textarea' | 'select' | 'color' | 'number' | 'icon' | 'media' | 'cloud-list';

export interface EditorPromptOption {
    value: string;
    label: string;
}

export interface EditorPromptField {
    name: string;
    label: string;
    type: EditorPromptFieldType;
    placeholder?: string;
    defaultValue?: string;
    options?: EditorPromptOption[];
    filterable?: boolean;
    allowCreate?: boolean;
    clearable?: boolean;
    mediaType?: MediaLibraryType;
    min?: number;
    max?: number;
}

export interface EditorPromptSpec {
    id: string;
    title: string;
    width?: string;
    fields: EditorPromptField[];
}

export interface EditorOption {
    value: string;
    label: string;
}

export interface EditorCategory {
    id: number;
    name: string;
    type?: string;
}

export type MediaLibraryType = 'all' | 'image' | 'video' | 'audio' | 'archive' | 'file';
export type MediaItemType = Exclude<MediaLibraryType, 'all'>;

export interface MediaLibraryItem {
    id: number;
    name: string;
    path: string;
    url: string;
    thumbUrl: string;
    disk: string;
    type: MediaItemType;
    mime?: string | null;
    size: number;
    directory: string;
    storageDirectory?: string;
    createdAt?: string | null;
}

export interface MediaLibraryMeta {
    currentPage: number;
    lastPage: number;
    perPage: number;
    total: number;
}

export interface EditorMediaDirectory {
    label: string;
    value: string;
}

export interface EditorMediaConfig {
    disk?: string;
    storageRoot?: string;
    defaultDirectory?: string;
    libraryPerPage?: number;
    uploadMaxKb?: number;
    directories?: EditorMediaDirectory[];
}

export interface EditorForm {
    title: string;
    type: string;
    status: 'draft' | 'published';
    category_id: number | null;
    tags: string;
    cover_url: string;
    excerpt: string;
}

export interface SavedContent {
    id: number;
    title: string;
    slug: string;
    status: string;
    type: string;
    published_at?: string | null;
    show_url?: string;
}
