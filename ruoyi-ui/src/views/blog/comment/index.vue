<template>
  <div class="app-container">
    <el-form ref="queryForm" :model="queryParams" size="small" :inline="true" v-show="showSearch" label-width="68px">
      <el-form-item label="用户" prop="userName">
        <el-input
          v-model="queryParams.userName"
          placeholder="请输入用户名"
          clearable
          @keyup.enter.native="handleQuery"
        />
      </el-form-item>
      <el-form-item label="文章" prop="articleTitle">
        <el-input
          v-model="queryParams.articleTitle"
          placeholder="请输入文章标题"
          clearable
          @keyup.enter.native="handleQuery"
        />
      </el-form-item>
      <el-form-item label="状态" prop="status">
        <el-select v-model="queryParams.status" placeholder="请选择状态" clearable>
          <el-option label="待审核" value="0" />
          <el-option label="已通过" value="1" />
          <el-option label="已拒绝" value="2" />
        </el-select>
      </el-form-item>
      <el-form-item>
        <el-button type="primary" icon="el-icon-search" size="mini" @click="handleQuery">搜索</el-button>
        <el-button icon="el-icon-refresh" size="mini" @click="resetQuery">重置</el-button>
      </el-form-item>
    </el-form>

    <el-row :gutter="10" class="mb8">
      <right-toolbar :showSearch.sync="showSearch" @queryTable="getList" />
    </el-row>

    <el-table v-loading="loading" :data="commentList">
      <el-table-column label="评论ID" align="center" prop="commentId" width="90" />
      <el-table-column label="文章标题" align="center" prop="articleTitle" min-width="220" :show-overflow-tooltip="true" />
      <el-table-column label="用户" align="center" prop="userName" width="140" />
      <el-table-column label="评论内容" align="center" prop="commentContent" min-width="260" :show-overflow-tooltip="true" />
      <el-table-column label="点赞" align="center" prop="likeCount" width="90" />
      <el-table-column label="状态" align="center" prop="status" width="100">
        <template slot-scope="scope">
          <el-tag v-if="scope.row.status === '0'" type="warning">待审核</el-tag>
          <el-tag v-else-if="scope.row.status === '1'" type="success">已通过</el-tag>
          <el-tag v-else type="danger">已拒绝</el-tag>
        </template>
      </el-table-column>
      <el-table-column label="评论时间" align="center" prop="createTime" width="180">
        <template slot-scope="scope">
          <span>{{ parseTime(scope.row.createTime) }}</span>
        </template>
      </el-table-column>
      <el-table-column label="操作" align="center" class-name="small-padding fixed-width" width="220">
        <template slot-scope="scope">
          <el-button
            size="mini"
            type="text"
            icon="el-icon-check"
            v-if="scope.row.status === '0'"
            @click="handleApprove(scope.row)"
            v-hasPermi="['blog:comment:edit']"
          >通过</el-button>
          <el-button
            size="mini"
            type="text"
            icon="el-icon-close"
            v-if="scope.row.status === '0'"
            @click="handleReject(scope.row)"
            v-hasPermi="['blog:comment:edit']"
          >拒绝</el-button>
          <el-button
            size="mini"
            type="text"
            icon="el-icon-delete"
            @click="handleDelete(scope.row)"
            v-hasPermi="['blog:comment:remove']"
          >删除</el-button>
        </template>
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
import { listComment, updateCommentStatus, delComment } from '@/api/blog'

export default {
  name: 'BlogCommentList',
  data() {
    return {
      loading: false,
      showSearch: true,
      total: 0,
      commentList: [],
      queryParams: {
        pageNum: 1,
        pageSize: 10,
        userName: null,
        articleTitle: null,
        status: null
      }
    }
  },
  created() {
    this.getList()
  },
  methods: {
    getList() {
      this.loading = true
      listComment(this.queryParams).then(response => {
        this.commentList = response.rows || []
        this.total = response.total || 0
      }).finally(() => {
        this.loading = false
      })
    },
    handleQuery() {
      this.queryParams.pageNum = 1
      this.getList()
    },
    resetQuery() {
      this.resetForm('queryForm')
      this.handleQuery()
    },
    handleApprove(row) {
      updateCommentStatus(row.commentId, '1').then(() => {
        this.$modal.msgSuccess('审核通过')
        this.getList()
      })
    },
    handleReject(row) {
      updateCommentStatus(row.commentId, '2').then(() => {
        this.$modal.msgSuccess('已拒绝')
        this.getList()
      })
    },
    handleDelete(row) {
      this.$modal.confirm('确认删除该评论吗？').then(() => {
        return delComment(row.commentId)
      }).then(() => {
        this.$modal.msgSuccess('删除成功')
        this.getList()
      }).catch(() => {})
    }
  }
}
</script>
