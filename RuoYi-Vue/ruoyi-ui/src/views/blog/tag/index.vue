<template>
  <div class="app-container">
    <el-form :model="queryParams" ref="queryForm" size="small" :inline="true" v-show="showSearch" label-width="68px">
      <el-form-item label="标签名称" prop="tagName">
        <el-input
          v-model="queryParams.tagName"
          placeholder="请输入标签名称"
          clearable
          @keyup.enter.native="handleQuery"
        />
      </el-form-item>
      <el-form-item>
        <el-button type="primary" icon="el-icon-search" size="mini" @click="handleQuery">搜索</el-button>
        <el-button icon="el-icon-refresh" size="mini" @click="resetQuery">重置</el-button>
      </el-form-item>
    </el-form>

    <el-row :gutter="10" class="mb8">
      <el-col :span="1.5">
        <el-button
          type="primary"
          plain
          icon="el-icon-plus"
          size="mini"
          @click="handleAdd"
          v-hasPermi="['blog:tag:add']"
        >新增</el-button>
      </el-col>
      <right-toolbar :showSearch.sync="showSearch" @queryTable="getList"></right-toolbar>
    </el-row>

    <el-table v-loading="loading" :data="tagList">
      <el-table-column label="标签ID" align="center" prop="tagId" width="80" />
      <el-table-column label="标签名称" align="center" prop="tagName" />
      <el-table-column label="描述" align="center" prop="tagDesc" :show-overflow-tooltip="true" />
      <el-table-column label="文章数量" align="center" prop="articleCount" width="100" />
      <el-table-column label="状态" align="center" prop="status" width="100">
        <template slot-scope="scope">
          <el-tag v-if="scope.row.status === '0'" type="success">正常</el-tag>
          <el-tag v-if="scope.row.status === '1'" type="danger">停用</el-tag>
        </template>
      </el-table-column>
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
            v-hasPermi="['blog:tag:edit']"
          >修改</el-button>
          <el-button
            size="mini"
            type="text"
            icon="el-icon-delete"
            @click="handleDelete(scope.row)"
            v-hasPermi="['blog:tag:remove']"
          >删除</el-button>
        </template>
      </el-table-column>
    </el-table>
  </div>
</template>

<script>
import { listTag, delTag, addTag, updateTag } from '@/api/blog'

export default {
  name: 'BlogTagList',
  data() {
    return {
      loading: true,
      showSearch: true,
      tagList: [],
      queryParams: {
        tagName: null
      }
    }
  },
  created() {
    this.getList()
  },
  methods: {
    getList() {
      this.loading = true
      listTag(this.queryParams).then(response => {
        this.tagList = response.rows
        this.loading = false
      })
    },
    handleQuery() {
      this.getList()
    },
    resetQuery() {
      this.resetForm('queryForm')
      this.handleQuery()
    },
    handleAdd() {
      this.$modal.prompt('请输入标签名称', '新增标签', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        inputPattern: /\S+/,
        inputErrorMessage: '标签名称不能为空'
      }).then(({ value }) => {
        addTag({ tagName: value }).then(() => {
          this.$modal.msgSuccess('新增成功')
          this.getList()
        })
      }).catch(() => {})
    },
    handleUpdate(row) {
      this.$modal.prompt('请输入新的标签名称', '修改标签', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        inputValue: row.tagName,
        inputPattern: /\S+/,
        inputErrorMessage: '标签名称不能为空'
      }).then(({ value }) => {
        updateTag({ tagId: row.tagId, tagName: value }).then(() => {
          this.$modal.msgSuccess('修改成功')
          this.getList()
        })
      }).catch(() => {})
    },
    handleDelete(row) {
      this.$modal.confirm('是否确认删除标签"' + row.tagName + '"？').then(function() {
        return delTag(row.tagId)
      }).then(() => {
        this.getList()
        this.$modal.msgSuccess('删除成功')
      }).catch(() => {})
    }
  }
}
</script>
