<template>
  <div class="app-container">
    <el-alert title="举报处理页面已可用，后续可直接接入举报工单 API。" type="warning" :closable="false" style="margin-bottom:14px;" />

    <el-table :data="reportList">
      <el-table-column prop="reportId" label="编号" width="90" />
      <el-table-column prop="reportType" label="举报类型" width="140" />
      <el-table-column prop="targetTitle" label="目标内容" min-width="220" :show-overflow-tooltip="true" />
      <el-table-column prop="reportUser" label="举报人" width="140" />
      <el-table-column prop="createTime" label="举报时间" width="180" />
      <el-table-column prop="status" label="状态" width="120">
        <template slot-scope="scope">
          <el-tag size="mini" :type="scope.row.status === 'pending' ? 'warning' : 'success'">
            {{ scope.row.status === 'pending' ? '待处理' : '已处理' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column label="操作" width="140">
        <template slot-scope="scope">
          <el-button size="mini" type="text" v-if="scope.row.status === 'pending'" @click="handle(scope.row)">处理</el-button>
        </template>
      </el-table-column>
    </el-table>
  </div>
</template>

<script>
export default {
  name: 'BlogReportList',
  data() {
    return {
      reportList: [
        {
          reportId: 1,
          reportType: '文章违规',
          targetTitle: '示例文章：这是一个测试内容',
          reportUser: 'report_user',
          createTime: '2026-03-30 09:10:00',
          status: 'pending'
        }
      ]
    }
  },
  methods: {
    handle(row) {
      row.status = 'done'
      this.$modal.msgSuccess('已标记为已处理')
    }
  }
}
</script>
