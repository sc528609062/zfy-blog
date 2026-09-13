<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue';

const props = withDefaults(defineProps<{ page: number; pageSize: number; total: number; disabled?: boolean; sizes?: number[] }>(), { sizes: () => [10, 20, 50, 100] });
const emit = defineEmits<{ change: [page: number, pageSize: number] }>();
const mobile = ref(false);
const query = window.matchMedia('(max-width: 600px)');
function update() { mobile.value = query.matches; }
let resizing = false;
function changePage(page: number) { if (!resizing && page !== props.page) emit('change', page, props.pageSize); }
async function changeSize(size: number) {
    if (size === props.pageSize) return;
    resizing = true;
    emit('change', 1, size);
    await nextTick();
    resizing = false;
}
onMounted(() => { update(); query.addEventListener('change', update); });
onBeforeUnmount(() => query.removeEventListener('change', update));
</script>

<template>
    <footer class="admin-pagination" aria-label="表格分页">
        <span class="admin-pagination-total">共 {{ total.toLocaleString() }} 条</span>
        <el-pagination
            :current-page="page" :page-size="pageSize" :total="total" :page-sizes="sizes"
            :disabled="disabled" :pager-count="5" :size="mobile ? 'small' : 'default'"
            :layout="mobile ? 'sizes, prev, next' : 'sizes, prev, pager, next, jumper'"
            @current-change="changePage" @size-change="changeSize"
        />
        <span v-if="mobile" class="admin-pagination-position">{{ page }} / {{ Math.max(1, Math.ceil(total / pageSize)) }}</span>
    </footer>
</template>
