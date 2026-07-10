<script setup lang="ts">
import { Delete, Plus } from '@element-plus/icons-vue';
import { computed } from 'vue';
import type { EditorPromptOption } from './types';

interface CloudResourceItem {
    id: string;
    type: string;
    title: string;
    url: string;
    password: string;
    attributes: CloudResourceAttribute[];
}

interface CloudResourceAttribute {
    id: string;
    name: string;
    value: string;
}

type CloudResourceFieldKey = 'type' | 'title' | 'url' | 'password';

const props = withDefaults(defineProps<{
    options: EditorPromptOption[];
    maxItems?: number;
    maxAttributesPerItem?: number;
}>(), {
    maxItems: 20,
    maxAttributesPerItem: 30,
});

const model = defineModel<string>({ required: true });
let nextItemId = 1;
let nextAttributeId = 1;

const items = computed<CloudResourceItem[]>(() => parseItems(model.value));
const canAdd = computed(() => items.value.length < props.maxItems);

function createItem(overrides: Partial<CloudResourceItem> = {}): CloudResourceItem {
    return defaultItem(`cloud-resource-${Date.now()}-${nextItemId++}`, overrides);
}

function defaultItem(id = 'cloud-resource-1', overrides: Partial<CloudResourceItem> = {}): CloudResourceItem {
    return {
        id,
        type: 'default',
        title: '下载资源',
        url: '',
        password: '',
        attributes: [],
        ...overrides,
    };
}

function parseItems(value: string): CloudResourceItem[] {
    try {
        const parsed: unknown = JSON.parse(value);
        if (!Array.isArray(parsed)) {
            return [defaultItem()];
        }

        const normalized = parsed
            .slice(0, props.maxItems)
            .filter((item): item is Record<string, unknown> => Boolean(item) && typeof item === 'object')
            .map((item, index) => defaultItem(
                typeof item.id === 'string' && item.id ? item.id : `cloud-resource-${index + 1}`,
                {
                    type: typeof item.type === 'string' ? item.type : 'default',
                    title: typeof item.title === 'string' ? item.title : '下载资源',
                    url: typeof item.url === 'string' ? item.url : '',
                    password: typeof item.password === 'string' ? item.password : '',
                    attributes: normalizeAttributes(item.attributes, index),
                },
            ));

        return normalized.length ? normalized : [defaultItem()];
    } catch {
        return [defaultItem()];
    }
}

function normalizeAttributes(value: unknown, itemIndex: number): CloudResourceAttribute[] {
    if (!Array.isArray(value)) {
        return [];
    }

    return value
        .slice(0, props.maxAttributesPerItem)
        .filter((attribute): attribute is Record<string, unknown> => Boolean(attribute) && typeof attribute === 'object')
        .map((attribute, attributeIndex) => ({
            id: typeof attribute.id === 'string' && attribute.id
                ? attribute.id
                : `cloud-resource-${itemIndex + 1}-attribute-${attributeIndex + 1}`,
            name: typeof attribute.name === 'string' ? attribute.name : '',
            value: typeof attribute.value === 'string' ? attribute.value : '',
        }));
}

function updateItem(index: number, key: CloudResourceFieldKey, value: unknown): void {
    const next = items.value.map((item, itemIndex) => itemIndex === index
        ? { ...item, [key]: String(value ?? '') }
        : item);

    model.value = JSON.stringify(next);
}

function addAttribute(itemIndex: number): void {
    const item = items.value[itemIndex];
    if (!item || item.attributes.length >= props.maxAttributesPerItem) {
        return;
    }

    const attribute: CloudResourceAttribute = {
        id: `cloud-attribute-${Date.now()}-${nextAttributeId++}`,
        name: '',
        value: '',
    };
    const next = items.value.map((current, index) => index === itemIndex
        ? { ...current, attributes: [...current.attributes, attribute] }
        : current);

    model.value = JSON.stringify(next);
}

function updateAttribute(itemIndex: number, attributeIndex: number, key: 'name' | 'value', value: unknown): void {
    const next = items.value.map((item, index) => index === itemIndex
        ? {
            ...item,
            attributes: item.attributes.map((attribute, currentAttributeIndex) => currentAttributeIndex === attributeIndex
                ? { ...attribute, [key]: String(value ?? '') }
                : attribute),
        }
        : item);

    model.value = JSON.stringify(next);
}

function removeAttribute(itemIndex: number, attributeIndex: number): void {
    const next = items.value.map((item, index) => index === itemIndex
        ? { ...item, attributes: item.attributes.filter((_, currentIndex) => currentIndex !== attributeIndex) }
        : item);

    model.value = JSON.stringify(next);
}

function addItem(): void {
    if (!canAdd.value) {
        return;
    }

    model.value = JSON.stringify([...items.value, createItem()]);
}

function removeItem(index: number): void {
    if (items.value.length <= 1) {
        return;
    }

    model.value = JSON.stringify(items.value.filter((_, itemIndex) => itemIndex !== index));
}
</script>

<template>
    <div class="zfy-cloud-resource-list">
        <section v-for="(item, index) in items" :key="item.id" class="zfy-cloud-resource-item">
            <header class="zfy-cloud-resource-item__header">
                <strong>下载项 {{ index + 1 }}</strong>
                <el-tooltip :content="items.length <= 1 ? '至少保留一项' : '删除下载项'">
                    <el-button
                        :aria-label="`删除下载项 ${index + 1}`"
                        circle
                        :disabled="items.length <= 1"
                        :icon="Delete"
                        plain
                        type="danger"
                        @click="removeItem(index)"
                    />
                </el-tooltip>
            </header>

            <div class="zfy-cloud-resource-item__fields">
                <label class="zfy-cloud-resource-field">
                    <span>网盘类型</span>
                    <el-select
                        :model-value="item.type"
                        @update:model-value="updateItem(index, 'type', $event)"
                    >
                        <el-option
                            v-for="option in options"
                            :key="option.value"
                            :label="option.label"
                            :value="option.value"
                        />
                    </el-select>
                </label>

                <label class="zfy-cloud-resource-field">
                    <span>显示标题</span>
                    <el-input
                        :model-value="item.title"
                        placeholder="请输入资源标题"
                        @update:model-value="updateItem(index, 'title', $event)"
                    />
                </label>

                <label class="zfy-cloud-resource-field">
                    <span>提取码</span>
                    <el-input
                        :model-value="item.password"
                        clearable
                        placeholder="没有可留空"
                        @update:model-value="updateItem(index, 'password', $event)"
                    />
                </label>

                <label class="zfy-cloud-resource-field is-wide">
                    <span>下载地址</span>
                    <el-input
                        :model-value="item.url"
                        placeholder="请输入网盘或下载地址"
                        @update:model-value="updateItem(index, 'url', $event)"
                    />
                </label>
            </div>

            <div class="zfy-cloud-custom-attributes">
                <div class="zfy-cloud-custom-attributes__header">
                    <strong>自定义属性</strong>
                    <el-button
                        :disabled="item.attributes.length >= maxAttributesPerItem"
                        :icon="Plus"
                        plain
                        size="small"
                        @click="addAttribute(index)"
                    >
                        添加属性
                    </el-button>
                </div>

                <div v-if="item.attributes.length" class="zfy-cloud-custom-attributes__list">
                    <div
                        v-for="(attribute, attributeIndex) in item.attributes"
                        :key="attribute.id"
                        class="zfy-cloud-custom-attribute"
                    >
                        <el-input
                            :aria-label="`下载项 ${index + 1} 属性名 ${attributeIndex + 1}`"
                            maxlength="80"
                            :model-value="attribute.name"
                            placeholder="属性名"
                            @update:model-value="updateAttribute(index, attributeIndex, 'name', $event)"
                        />
                        <el-input
                            :aria-label="`下载项 ${index + 1} 属性值 ${attributeIndex + 1}`"
                            maxlength="300"
                            :model-value="attribute.value"
                            placeholder="属性值"
                            @update:model-value="updateAttribute(index, attributeIndex, 'value', $event)"
                        />
                        <el-tooltip content="删除属性">
                            <el-button
                                :aria-label="`删除下载项 ${index + 1} 的属性 ${attributeIndex + 1}`"
                                circle
                                :icon="Delete"
                                text
                                type="danger"
                                @click="removeAttribute(index, attributeIndex)"
                            />
                        </el-tooltip>
                    </div>
                </div>
            </div>
        </section>

        <el-button :disabled="!canAdd" :icon="Plus" plain type="primary" @click="addItem">
            添加下载项
        </el-button>
    </div>
</template>
