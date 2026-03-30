<template>
  <div class="app-container">
    <el-row :gutter="10" class="mb8">
      <el-col :span="1.5">
        <el-button
          type="primary"
          plain
          icon="el-icon-plus"
          size="mini"
          @click="handleAdd"
          v-hasPermi="['blog:config:add']"
        >新增</el-button>
      </el-col>
      <el-col :span="1.5">
        <el-button
          type="success"
          plain
          icon="el-icon-refresh"
          size="mini"
          @click="handleRefreshCache"
        >刷新缓存</el-button>
      </el-col>
    </el-row>

    <el-table v-loading="loading" :data="configList">
      <el-table-column label="配置ID" align="center" prop="configId" width="80" />
      <el-table-column label="配置键" align="center" prop="configKey" />
      <el-table-column label="配置值" align="center" prop="configValue" :show-overflow-tooltip="true" />
      <el-table-column label="配置描述" align="center" prop="configDesc" :show-overflow-tooltip="true" />
      <el-table-column label="分组" align="center" prop="configGroup" width="100" />
      <el-table-column label="创建时间" align="center" prop="createTime" width="180">
        <template slot-scope="scope">
          <span>{{ parseTime(scope.row.createTime) }}</span>
        </template>
      </el-table-column>
      <el-table-column label="操作" align="center" class-name="small-padding fixed-width" width="200">
        <template slot-scope="scope">
          <el-button
            size="mini"
            type="text"
            icon="el-icon-edit"
            @click="handleUpdate(scope.row)"
            v-hasPermi="['blog:config:edit']"
          >修改</el-button>
          <el-button
            size="mini"
            type="text"
            icon="el-icon-delete"
            @click="handleDelete(scope.row)"
            v-hasPermi="['blog:config:remove']"
          >删除</el-button>
        </template>
      </el-table-column>
    </el-table>
  </div>
</template>

<script>
import { listConfig, delConfig } from '@/api/blog'

export default {
  name: 'BlogConfigList',
  data() {
    return {
      loading: true,
      configList: []
    }
  },
  created() {
    this.getList()
  },
  methods: {
    getList() {
      this.loading = true
      listConfig().then(response => {
        this.configList = response.rows
        this.loading = false
      })
    },
    handleAdd() {
      // TODO: 打开新增对话框
      this.$modal.msg('待实现')
    },
    handleUpdate(row) {
      // TODO: 打开修改对话框
      this.$modal.msg('待实现')
    },
    handleDelete(row) {
      this.$modal.confirm('是否确认删除该配置？').then(function() {
        return delConfig(row.configId)
      }).then(() => {
        this.getList()
        this.$modal.msgSuccess('删除成功')
      }).catch(() => {})
    },
    handleRefreshCache() {
      // TODO: 刷新配置缓存
      this.$modal.msgSuccess('刷新缓存成功')
    }
  }
}
</script>
