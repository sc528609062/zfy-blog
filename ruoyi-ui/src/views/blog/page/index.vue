<template>
  <div class="app-container">
    <el-form ref="queryForm" :model="queryParams" size="small" :inline="true" v-show="showSearch" label-width="68px">
      <el-form-item label="标题" prop="pageTitle">
        <el-input
          v-model="queryParams.pageTitle"
          placeholder="请输入页面标题"
          clearable
          @keyup.enter.native="handleQuery"
        />
      </el-form-item>
      <el-form-item label="状态" prop="status">
        <el-select v-model="queryParams.status" placeholder="请选择状态" clearable>
          <el-option label="草稿" value="0" />
          <el-option label="发布" value="1" />
        </el-select>
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
          v-hasPermi="['blog:page:add']"
        >新增</el-button>
      </el-col>
      <right-toolbar :showSearch.sync="showSearch" @queryTable="getList" />
    </el-row>

    <el-table v-loading="loading" :data="pageList">
      <el-table-column label="ID" align="center" prop="pageId" width="90" />
      <el-table-column label="标题" align="center" prop="pageTitle" min-width="200" />
      <el-table-column label="别名" align="center" prop="pageSlug" width="180" />
      <el-table-column label="菜单显示" align="center" prop="showInMenu" width="120">
        <template slot-scope="scope">
          <el-tag size="mini" :type="scope.row.showInMenu === '1' ? 'success' : 'info'">
            {{ scope.row.showInMenu === '1' ? '显示' : '隐藏' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column label="状态" align="center" prop="status" width="100">
        <template slot-scope="scope">
          <el-tag size="mini" :type="scope.row.status === '1' ? 'success' : 'warning'">
            {{ scope.row.status === '1' ? '发布' : '草稿' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column label="更新时间" align="center" prop="updateTime" width="180">
        <template slot-scope="scope">
          <span>{{ parseTime(scope.row.updateTime || scope.row.createTime) }}</span>
        </template>
      </el-table-column>
      <el-table-column label="操作" align="center" class-name="small-padding fixed-width" width="220">
        <template slot-scope="scope">
          <el-button
            size="mini"
            type="text"
            icon="el-icon-edit"
            @click="handleUpdate(scope.row)"
            v-hasPermi="['blog:page:edit']"
          >修改</el-button>
          <el-button
            size="mini"
            type="text"
            icon="el-icon-delete"
            @click="handleDelete(scope.row)"
            v-hasPermi="['blog:page:remove']"
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

    <el-dialog :title="title" :visible.sync="open" width="760px" append-to-body>
      <el-form ref="form" :model="form" :rules="rules" label-width="90px">
        <el-form-item label="页面标题" prop="pageTitle">
          <el-input v-model="form.pageTitle" maxlength="120" show-word-limit />
        </el-form-item>
        <el-form-item label="路由别名" prop="pageSlug">
          <el-input v-model="form.pageSlug" placeholder="例如：about-us" />
        </el-form-item>
        <el-form-item label="页面内容" prop="pageContent">
          <el-input type="textarea" :rows="8" v-model="form.pageContent" />
        </el-form-item>
        <el-form-item label="排序" prop="sortOrder">
          <el-input-number v-model="form.sortOrder" :min="0" :max="999" />
        </el-form-item>
        <el-form-item label="菜单显示" prop="showInMenu">
          <el-switch
            v-model="form.showInMenu"
            active-value="1"
            inactive-value="0"
            active-text="显示"
            inactive-text="隐藏"
          />
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-radio-group v-model="form.status">
            <el-radio label="0">草稿</el-radio>
            <el-radio label="1">发布</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button type="primary" @click="submitForm">确 定</el-button>
        <el-button @click="cancel">取 消</el-button>
      </div>
    </el-dialog>
  </div>
</template>

<script>
import { listPage, getPage, addPage, updatePage, delPage } from '@/api/blog'

export default {
  name: 'BlogPageList',
  data() {
    return {
      loading: false,
      showSearch: true,
      total: 0,
      pageList: [],
      open: false,
      title: '',
      queryParams: {
        pageNum: 1,
        pageSize: 10,
        pageTitle: null,
        status: null
      },
      form: {},
      rules: {
        pageTitle: [{ required: true, message: '页面标题不能为空', trigger: 'blur' }],
        pageSlug: [{ required: true, message: '路由别名不能为空', trigger: 'blur' }]
      }
    }
  },
  created() {
    this.getList()
  },
  methods: {
    getList() {
      this.loading = true
      listPage(this.queryParams).then(response => {
        this.pageList = response.rows || []
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
    resetFormData() {
      this.form = {
        pageId: null,
        pageTitle: '',
        pageSlug: '',
        pageContent: '',
        pageCover: '',
        sortOrder: 0,
        showInMenu: '1',
        status: '0'
      }
      if (this.$refs.form) {
        this.$refs.form.resetFields()
      }
    },
    handleAdd() {
      this.resetFormData()
      this.open = true
      this.title = '新增页面'
    },
    handleUpdate(row) {
      this.resetFormData()
      getPage(row.pageId).then(response => {
        this.form = response.data
        this.open = true
        this.title = '修改页面'
      })
    },
    submitForm() {
      this.$refs.form.validate(valid => {
        if (!valid) {
          return
        }
        const request = this.form.pageId ? updatePage(this.form) : addPage(this.form)
        request.then(() => {
          this.$modal.msgSuccess(this.form.pageId ? '修改成功' : '新增成功')
          this.open = false
          this.getList()
        })
      })
    },
    cancel() {
      this.open = false
      this.resetFormData()
    },
    handleDelete(row) {
      this.$modal.confirm(`确认删除页面 "${row.pageTitle}" 吗？`).then(() => {
        return delPage(row.pageId)
      }).then(() => {
        this.$modal.msgSuccess('删除成功')
        this.getList()
      }).catch(() => {})
    }
  }
}
</script>
