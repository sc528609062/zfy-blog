<template>
  <div class="app-container">
    <el-alert title="举报处理数据已改为后端持久化（blog_config）" type="warning" :closable="false" style="margin-bottom:14px;" />

    <el-table :data="reportList">
      <el-table-column prop="reportId" label="编号" width="90" />
      <el-table-column prop="reportType" label="举报类型" width="140" />
      <el-table-column prop="targetTitle" label="目标内容" min-width="220" :show-overflow-tooltip="true" />
      <el-table-column prop="reportUser" label="举报人" width="140" />
      <el-table-column prop="createTime" label="举报时间" width="180">
        <template slot-scope="scope">{{ parseTime(scope.row.createTime) }}</template>
      </el-table-column>
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
const STORE_KEY = 'zfy_report_records'
const DEFAULT_LIST = [
  {
    reportId: 1,
    reportType: '文章违规',
    targetTitle: '示例文章',
    reportUser: 'report_user',
    createTime: new Date(),
    status: 'pending'
  }
]

export default {
  name: 'BlogReportList',
  data() {
    return {
      reportList: []
    }
  },
  async created() {
    this.reportList = await this.$zfyConfigCenter.getJson(STORE_KEY, DEFAULT_LIST)
  },
  methods: {
    async persist() {
      await this.$zfyConfigCenter.save(STORE_KEY, this.reportList, {
        group: 'report',
        desc: '举报处理记录'
      })
    },
    async handle(row) {
      row.status = 'done'
      await this.persist()
      this.$modal.msgSuccess('已标记为已处理')
    }
  }
}
</script>
