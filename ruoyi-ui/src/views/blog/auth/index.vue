<template>
  <div class="app-container">
    <el-alert title="认证审核数据已改为后端持久化（blog_config）" type="info" :closable="false" style="margin-bottom: 14px;" />

    <el-table :data="authList">
      <el-table-column prop="userName" label="用户" min-width="160" />
      <el-table-column prop="authType" label="认证类型" width="140" />
      <el-table-column prop="submitTime" label="提交时间" width="180">
        <template slot-scope="scope">{{ parseTime(scope.row.submitTime) }}</template>
      </el-table-column>
      <el-table-column prop="status" label="状态" width="120">
        <template slot-scope="scope">
          <el-tag size="mini" :type="scope.row.status === 'pending' ? 'warning' : (scope.row.status === 'approved' ? 'success' : 'danger')">
            {{ statusText(scope.row.status) }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="remark" label="备注" min-width="220" />
      <el-table-column label="操作" width="160">
        <template slot-scope="scope">
          <el-button size="mini" type="text" @click="approve(scope.row)" v-if="scope.row.status === 'pending'">通过</el-button>
          <el-button size="mini" type="text" style="color:#f56c6c;" @click="reject(scope.row)" v-if="scope.row.status === 'pending'">驳回</el-button>
        </template>
      </el-table-column>
    </el-table>
  </div>
</template>

<script>
const STORE_KEY = 'zfy_auth_records'
const DEFAULT_LIST = [
  { id: 1, userName: 'demo_user', authType: '实名认证', submitTime: new Date(), status: 'pending', remark: '待审核' }
]

export default {
  name: 'BlogAuthList',
  data() {
    return {
      authList: []
    }
  },
  async created() {
    this.authList = await this.$zfyConfigCenter.getJson(STORE_KEY, DEFAULT_LIST)
  },
  methods: {
    statusText(status) {
      if (status === 'pending') return '待审核'
      if (status === 'approved') return '已通过'
      return '已驳回'
    },
    async persist() {
      await this.$zfyConfigCenter.save(STORE_KEY, this.authList, {
        group: 'auth',
        desc: '认证审核记录'
      })
    },
    async approve(row) {
      row.status = 'approved'
      row.remark = '审核通过'
      await this.persist()
      this.$modal.msgSuccess('已通过认证')
    },
    async reject(row) {
      row.status = 'rejected'
      row.remark = '审核驳回'
      await this.persist()
      this.$modal.msgSuccess('已驳回认证')
    }
  }
}
</script>
