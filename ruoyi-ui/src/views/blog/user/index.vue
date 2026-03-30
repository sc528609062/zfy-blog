<template>
  <div class="app-container">
    <el-form :model="queryParams" ref="queryForm" size="small" :inline="true" label-width="68px">
      <el-form-item label="用户名称" prop="userName">
        <el-input v-model="queryParams.userName" placeholder="请输入用户名称" clearable @keyup.enter.native="getList" />
      </el-form-item>
      <el-form-item label="手机号" prop="phonenumber">
        <el-input v-model="queryParams.phonenumber" placeholder="请输入手机号" clearable @keyup.enter.native="getList" />
      </el-form-item>
      <el-form-item label="状态" prop="status">
        <el-select v-model="queryParams.status" placeholder="用户状态" clearable>
          <el-option label="正常" value="0" />
          <el-option label="停用" value="1" />
        </el-select>
      </el-form-item>
      <el-form-item>
        <el-button type="primary" icon="el-icon-search" size="mini" @click="getList">搜索</el-button>
        <el-button icon="el-icon-refresh" size="mini" @click="resetQuery">重置</el-button>
      </el-form-item>
    </el-form>

    <el-table v-loading="loading" :data="userList">
      <el-table-column prop="userId" label="用户编号" width="90" />
      <el-table-column prop="userName" label="用户名称" min-width="140" />
      <el-table-column prop="nickName" label="昵称" min-width="140" />
      <el-table-column prop="phonenumber" label="手机" min-width="140" />
      <el-table-column prop="email" label="邮箱" min-width="180" :show-overflow-tooltip="true" />
      <el-table-column prop="status" label="状态" width="100">
        <template slot-scope="scope">
          <el-tag :type="scope.row.status === '0' ? 'success' : 'danger'" size="mini">
            {{ scope.row.status === '0' ? '正常' : '停用' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="createTime" label="创建时间" width="180">
        <template slot-scope="scope">{{ parseTime(scope.row.createTime) }}</template>
      </el-table-column>
    </el-table>

    <pagination
      v-show="total > 0"
      :total="total"
      :page.sync="queryParams.pageNum"
      :limit.sync="queryParams.pageSize"
      @pagination="getList"
    />
  </div>
</template>

<script>
import { listUser } from '@/api/system/user'

export default {
  name: 'BlogUserList',
  data() {
    return {
      loading: false,
      total: 0,
      userList: [],
      queryParams: {
        pageNum: 1,
        pageSize: 10,
        userName: '',
        phonenumber: '',
        status: ''
      }
    }
  },
  created() {
    this.getList()
  },
  methods: {
    getList() {
      this.loading = true
      listUser(this.queryParams).then(response => {
        this.userList = response.rows || []
        this.total = response.total || 0
      }).finally(() => {
        this.loading = false
      })
    },
    resetQuery() {
      this.queryParams = {
        pageNum: 1,
        pageSize: 10,
        userName: '',
        phonenumber: '',
        status: ''
      }
      this.getList()
    }
  }
}
</script>
