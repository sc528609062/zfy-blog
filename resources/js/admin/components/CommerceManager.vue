<script setup lang="ts">
import { ref, watch } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { adminRequest } from './adminRequest';
const props = defineProps<{ section: string; csrf: string }>();
const rows = ref<Record<string, any>[]>([]);
const page = ref(1);
const total = ref(0);
const busy = ref(false);
const labels: Record<string, string> = { pending: '待处理', processing: '退款处理中', refund_pending: '待退款补偿', paid: '已支付', cancelled: '已取消', settled: '已结算', rejected: '已拒绝', refunded: '已退款', reversed: '已冲回', fulfilled: '已发放' };
async function load() {
    busy.value = true;
    try { const result = await adminRequest(`/admin/commerce/${props.section}?page=${page.value}`, props.csrf); rows.value = result.data.data; total.value = result.data.total; }
    catch (error) { ElMessage.error((error as Error).message); }
    finally { busy.value = false; }
}
async function action(row: Record<string, any>, status: string) {
    try {
        await ElMessageBox.confirm(status === 'paid' ? '确认已通过外部渠道完成打款？' : '确定执行此操作？', '确认处理', { type: 'warning' });
        let reference: string | undefined;
        if (status === 'returned') reference = (await ElMessageBox.prompt('退货运单号与验收凭证', '确认退货入库', { inputValidator: value => Boolean(value?.trim()) || '请填写凭证' })).value;
        if (status === 'paid') reference = (await ElMessageBox.prompt('渠道流水号或凭证地址', '打款凭证', { inputValidator: value => Boolean(value?.trim()) || '请填写凭证' })).value;
        if (status === 'refunded') {
            const { value } = await ElMessageBox.prompt('人工退款凭证（官方渠道自动退款可留空）', '退款凭证');
            reference = value;
        }
        await adminRequest(`/admin/commerce/${props.section}/${row.id}`, props.csrf, 'PATCH', { status, reference });
        await load(); ElMessage.success('已处理');
    } catch (error) { if (error instanceof Error) ElMessage.error(error.message); }
}
function account(value: any) { try { return typeof value === 'string' ? JSON.parse(value).account : value?.account; } catch { return ''; } }
watch(() => props.section, () => { page.value = 1; void load(); }, { immediate: true });
</script>
<template>
    <section v-loading="busy">
        <el-table :data="rows" empty-text="暂无记录">
            <el-table-column prop="id" label="ID" width="75" />
            <el-table-column label="订单 / 用户" min-width="180"><template #default="{ row }">{{ row.order_no || row.order_id || row.author_id || row.user_id }} {{ row.user?.name }}</template></el-table-column>
            <el-table-column label="金额 / 积分" width="140"><template #default="{ row }">{{ row.total_amount ?? row.amount ?? row.points_spent }}</template></el-table-column>
            <el-table-column label="状态" width="100"><template #default="{ row }">{{ labels[row.status] || row.status }}</template></el-table-column>
            <el-table-column v-if="section === 'withdrawals'" label="收款账户" min-width="220"><template #default="{ row }">{{ row.method }} {{ account(row.account_snapshot) }}</template></el-table-column>
            <el-table-column v-if="section === 'refunds'" prop="reason" label="原因" min-width="200" />
            <el-table-column v-if="section === 'refunds'" label="退货物流" min-width="180"><template #default="{ row }">{{ row.metadata?.return?.carrier }} {{ row.metadata?.return?.tracking_no }}</template></el-table-column>
            <el-table-column prop="created_at" label="申请时间" min-width="190" />
            <el-table-column v-if="section !== 'commissions'" label="操作" width="220" fixed="right"><template #default="{ row }">
                <el-button v-if="section === 'refunds' && ['pending', 'processing', 'refunded'].includes(row.status) && !row.metadata?.return?.received_at" link @click="action(row, 'returned')">退货验收</el-button>
                <template v-if="row.status === 'pending' || row.status === 'processing'">
                    <template v-if="section === 'withdrawals'"><el-button link type="primary" @click="action(row, 'paid')">已打款</el-button><el-button link type="danger" @click="action(row, 'rejected')">驳回</el-button></template>
                    <template v-else-if="section === 'refunds'"><el-button link type="primary" @click="action(row, 'refunded')">退款</el-button><el-button link type="danger" @click="action(row, 'rejected')">拒绝</el-button></template>
                    <el-button v-else-if="section === 'orders'" link @click="action(row, 'cancelled')">取消订单</el-button>
                    <el-button v-else-if="section === 'points-exchanges'" link type="primary" @click="action(row, 'fulfilled')">确认发放</el-button>
                </template>
            </template></el-table-column>
        </el-table>
        <el-pagination v-model:current-page="page" :total="total" :page-size="20" layout="prev, pager, next, total" @current-change="load" />
    </section>
</template>
