<script setup lang="ts">
import { computed, reactive, ref, watch, nextTick } from 'vue';
import { Delete, Edit, Plus, Refresh, Search, ArrowUp, ArrowDown } from '@element-plus/icons-vue';
import { ElMessage, ElMessageBox, ElTable } from 'element-plus';
import { adminRequest } from './adminRequest';
import AdminPagination from './AdminPagination.vue';

const props = defineProps<{ resource: string; csrf: string; create?: boolean }>();
const rows = ref<Record<string, any>[]>([]);
const table = ref<InstanceType<typeof ElTable>>();
const fields = ref<Record<string, any>[]>([]);
const schema = ref<Record<string, any>>({});
const query = ref('');
const page = ref(1);
const pageSize = ref(20);
const total = ref(0);
const loading = ref(false);
const saving = ref(false);
const visible = ref(false);
const editingId = ref<number | null>(null);
const form = reactive<Record<string, any> & { items?: Record<string, any>[] }>({});
const draggedIndex = ref<number | null>(null);
let sequence = 0;
const columns = computed(() => fields.value.filter((field) => !['password', 'menu', 'textarea', 'variants', 'array'].includes(field.type)).slice(0, 5));
const endpoint = computed(() => `/admin/resources/${props.resource}`);

async function load() {
    const current = ++sequence;
    loading.value = true;
    try {
        const result = await adminRequest(`${endpoint.value}?${new URLSearchParams({ q: query.value, page: String(page.value), per_page: String(pageSize.value) })}`, props.csrf);
        if (current !== sequence) return;
        rows.value = result.data.data;
        total.value = result.data.total;
        page.value = result.data.current_page;
        fields.value = result.schema.fields;
        schema.value = result.schema;
        await nextTick();
        if (current === sequence) table.value?.setScrollTop(0);
    } catch (error) {
        if (current === sequence) ElMessage.error((error as Error).message);
    } finally {
        if (current === sequence) loading.value = false;
    }
}

function paginate(nextPage: number, size: number) { page.value = nextPage; pageSize.value = size; void load(); }

function edit(row?: Record<string, any>) {
    editingId.value = row?.id ?? null;
    Object.keys(form).forEach((key) => delete form[key]);
    fields.value.forEach((field) => {
        form[field.key] = JSON.parse(JSON.stringify(row?.[field.key] ?? field.default ?? (field.nullable ? null : field.type === 'boolean' ? false : field.type === 'number' ? 0 : '')));
    });
    visible.value = true;
}

async function save() {
    saving.value = true;
    try {
        await adminRequest(endpoint.value + (editingId.value ? `/${editingId.value}` : ''), props.csrf, editingId.value ? 'PATCH' : 'POST', form);
        visible.value = false;
        ElMessage.success('已保存');
        await load();
    } catch (error) {
        ElMessage.error((error as Error).message);
    } finally {
        saving.value = false;
    }
}

async function remove(row: Record<string, any>) {
    try {
        await ElMessageBox.confirm('确定删除这条记录？', '删除记录', { type: 'warning' });
        await adminRequest(`${endpoint.value}/${row.id}`, props.csrf, 'DELETE');
        if (rows.value.length === 1 && page.value > 1) page.value--;
        await load();
        ElMessage.success('已删除');
    } catch (error) {
        if (error instanceof Error) ElMessage.error(error.message);
    }
}

function moveItem(items: any[], rawIndex: number | string, offset: number) {
    const index = Number(rawIndex);
    const target = index + offset;
    if (target >= 0 && target < items.length) [items[index], items[target]] = [items[target], items[index]];
}

function dropMenu(items: any[], rawIndex: number | string) {
    if (draggedIndex.value === null) return;
    const [item] = items.splice(draggedIndex.value, 1);
    items.splice(Number(rawIndex), 0, item);
    draggedIndex.value = null;
}

function display(row: Record<string, any>, field: Record<string, any>) {
    const value = row[field.key];
    if (field.type === 'boolean') return value ? '是' : '否';
    return field.options?.[value] ?? value ?? '';
}

watch(() => [props.resource, props.create], async () => {
    visible.value = false;
    page.value = 1;
    query.value = '';
    await load();
    if (props.create && fields.value.length) edit();
}, { immediate: true });
</script>

<template>
    <section class="resource-manager admin-table-workspace" v-loading="loading">
        <div class="resource-toolbar admin-list-toolbar">
            <el-input v-model="query" clearable placeholder="搜索记录" maxlength="120" aria-label="搜索记录" @keyup.enter="page = 1; load()" @clear="page = 1; load()"><template #prefix><el-icon><Search /></el-icon></template></el-input>
            <el-button :icon="Search" @click="page = 1; load()">搜索</el-button>
            <el-tooltip content="刷新"><el-button :icon="Refresh" aria-label="刷新" @click="load" /></el-tooltip>
            <el-button v-if="schema.create !== false" class="admin-toolbar-primary" type="primary" :icon="Plus" @click="edit()">新建</el-button>
        </div>
        <div class="admin-table-region"><el-table ref="table" :data="rows" height="100%" empty-text="暂无记录" row-key="id">
            <el-table-column prop="id" label="ID" width="75" />
            <el-table-column v-for="field in columns" :key="field.key" :label="field.label" min-width="140" show-overflow-tooltip>
                <template #default="{ row }">{{ display(row, field) }}</template>
            </el-table-column>
            <el-table-column v-if="resource === 'comments'" prop="body" label="评论内容" min-width="260" show-overflow-tooltip />
            <el-table-column v-if="resource === 'shipments'" prop="address" label="收货地址" min-width="260" />
            <el-table-column v-if="resource === 'cards'" prop="status" label="状态" min-width="100" />
            <el-table-column v-if="['verification', 'ban-appeals'].includes(resource)" prop="user_id" label="用户 ID" width="100" />
            <el-table-column v-if="['verification', 'ban-appeals'].includes(resource)" prop="body" label="申请内容" min-width="300" />
            <el-table-column label="操作" width="110" fixed="right">
                <template #default="{ row }">
                    <div class="zfy-art-row-actions">
                    <el-tooltip content="编辑"><el-button :icon="Edit" aria-label="编辑" text @click="edit(row)" /></el-tooltip>
                    <el-tooltip v-if="schema.delete !== false" content="删除"><el-button :icon="Delete" aria-label="删除" type="danger" text @click="remove(row)" /></el-tooltip>
                    </div>
                </template>
            </el-table-column>
        </el-table></div>
        <AdminPagination :page="page" :page-size="pageSize" :total="total" :disabled="loading" @change="paginate" />
        <el-dialog v-model="visible" class="admin-record-dialog" :title="editingId ? '编辑记录' : '新建记录'" width="min(720px, 94vw)" :close-on-click-modal="!saving">
            <el-form label-position="top" @submit.prevent="save">
                <el-form-item v-for="field in fields" :key="field.key" :label="field.label">
                    <el-switch v-if="field.type === 'boolean'" v-model="form[field.key]" />
                    <el-input-number v-else-if="field.type === 'number'" v-model="form[field.key]" :disabled="field.readonly" :min="field.min ?? 0" />
                    <el-date-picker v-else-if="field.type === 'datetime'" v-model="form[field.key]" type="datetime" value-format="YYYY-MM-DD HH:mm:ss" />
                    <el-select v-else-if="field.type === 'multiselect'" v-model="form[field.key]" multiple filterable style="width:100%"><el-option v-for="(label, value) in field.options" :key="value" :label="String(label)" :value="field.string_values ? String(value) : Number(value)" /></el-select>
                    <el-select v-else-if="field.type === 'select'" v-model="form[field.key]" :clearable="field.nullable" style="width:100%">
                        <el-option v-for="(label, value) in field.options" :key="value" :label="String(label)" :value="field.nullable ? Number(value) : value" :disabled="field.key === 'parent_id' && Number(value) === editingId" />
                    </el-select>
                    <el-color-picker v-else-if="field.type === 'color'" v-model="form[field.key]" />
                    <div v-else-if="field.type === 'array' && field.key === 'regions'" class="variant-items">
                        <div v-for="(region, index) in form[field.key]" :key="index" class="variant-item">
                            <el-input v-model="region.prefix" placeholder="地区名称前缀" aria-label="地区名称前缀" />
                            <el-switch v-model="region.excluded" active-text="不配送" />
                            <label>首件运费<el-input-number v-model="region.base_fee" :min="0" :precision="2" /></label>
                            <label>首件数量<el-input-number v-model="region.base_quantity" :min="1" :precision="0" /></label>
                            <label>续件运费<el-input-number v-model="region.additional_fee" :min="0" :precision="2" /></label>
                            <label>包邮金额<el-input-number v-model="region.free_threshold" :min="0" :precision="2" /></label>
                            <el-button :icon="Delete" aria-label="删除区域规则" @click="form[field.key].splice(index, 1)" />
                        </div>
                        <el-button :icon="Plus" @click="form[field.key].push({ prefix: '', excluded: false, base_fee: 0, base_quantity: 1, additional_fee: 0, free_threshold: null })">添加区域规则</el-button>
                    </div>
                    <div v-else-if="field.type === 'variants'" class="variant-items">
                        <div v-for="(variant, index) in form[field.key]" :key="variant.id ?? index" class="variant-item">
                            <el-input v-model="variant.sku" placeholder="SKU" aria-label="SKU" />
                            <el-input v-model="variant.title" placeholder="规格名称" aria-label="规格名称" />
                            <label>售价<el-input-number v-model="variant.price" :min="0" :precision="2" /></label>
                            <label>库存<el-input-number v-model="variant.stock" :min="variant.reserved || 0" :precision="0" /></label>
                            <el-switch v-model="variant.status" active-value="active" inactive-value="inactive" active-text="上架" />
                        </div>
                        <el-button :icon="Plus" @click="form[field.key].push({ sku: '', title: '', price: 0, stock: 0, status: 'active' })">添加规格</el-button>
                    </div>
                    <div v-else-if="field.type === 'menu'" class="menu-items">
                        <div v-for="(item, index) in form[field.key]" :key="index" class="menu-item" draggable="true" @dragstart="draggedIndex = Number(index)" @dragover.prevent @drop.prevent="dropMenu(form[field.key], index)" @dragend="draggedIndex = null">
                            <el-input v-model="item.title" aria-label="菜单标题" placeholder="标题" />
                            <el-input v-model="item.url" aria-label="菜单地址" placeholder="网址" />
                            <el-select v-model="item.depth" aria-label="菜单层级" placeholder="层级" style="width:110px"><el-option v-for="depth in [0,1,2,3]" :key="depth" :value="depth" :label="depth === 0 ? '顶层' : String(depth + 1) + ' 级'" /></el-select>
                            <el-select v-model="item.visibility" aria-label="菜单可见范围" placeholder="可见范围" style="width:130px"><el-option v-for="(label, value) in {public:'所有人',guest:'访客',member:'已登录',USER:'普通用户',EDITOR:'编辑',ADMIN:'管理员',SUPER_ADMIN:'超级管理员'}" :key="value" :value="value" :label="label" /></el-select>
                            <el-select v-model="item.target" aria-label="打开方式" placeholder="打开方式" style="width:120px"><el-option value="_self" label="当前窗口" /><el-option value="_blank" label="新窗口" /></el-select>
                            <el-button :icon="ArrowUp" aria-label="上移" title="上移" :disabled="index === 0" @click="moveItem(form[field.key], index, -1)" />
                            <el-button :icon="ArrowDown" aria-label="下移" title="下移" :disabled="index === form[field.key].length - 1" @click="moveItem(form[field.key], index, 1)" />
                            <el-button :icon="Delete" aria-label="删除菜单项" title="删除" @click="form[field.key].splice(index, 1)" />
                        </div>
                        <el-button :icon="Plus" @click="form[field.key].push({ title: '', url: '/', depth: 0, visibility: 'public', target: '_self' })">添加菜单项</el-button>
                    </div>
                    <el-input v-else v-model="form[field.key]" :readonly="field.readonly" :type="field.type === 'textarea' ? 'textarea' : field.type === 'password' ? 'password' : 'text'" :rows="4" :show-password="field.type === 'password'" :autocomplete="field.type === 'password' ? 'new-password' : 'off'" />
                </el-form-item>
            </el-form>
            <template #footer><el-button :disabled="saving" @click="visible = false">取消</el-button><el-button type="primary" :loading="saving" @click="save">保存</el-button></template>
        </el-dialog>
    </section>
</template>

<style scoped>
.resource-manager { min-width: 0; }
.resource-toolbar { display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
.resource-toolbar > .el-input { width: 260px; max-width: 100%; }
.el-pagination { margin-top: 20px; overflow: auto; }
.menu-items { width: 100%; }
.menu-item { display: flex; gap: 6px; margin-bottom: 8px; flex-wrap: wrap; }
.menu-item > .el-input { flex: 1; min-width: 160px; }
.menu-item > .el-button { margin-left: 0; }
.variant-items { width: 100%; }
.variant-item { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; padding: 14px 0; border-bottom: 1px solid var(--el-border-color); }
.variant-item label { display: grid; gap: 4px; }
.variant-item .el-input-number { max-width: 100%; }
</style>
