<template>
  <div class="app-container">
    <el-form ref="queryForm" :model="queryParams" size="small" :inline="true" v-show="showSearch" label-width="68px">
      <el-form-item label="名称" prop="linkName">
        <el-input
          v-model="queryParams.linkName"
          placeholder="请输入友链名称"
          clearable
          @keyup.enter.native="handleQuery"
        />
      </el-form-item>
      <el-form-item label="状态" prop="status">
        <el-select v-model="queryParams.status" placeholder="请选择状态" clearable>
          <el-option label="正常" value="0" />
          <el-option label="停用" value="1" />
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
          v-hasPermi="['blog:link:add']"
        >新增</el-button>
      </el-col>
      <right-toolbar :showSearch.sync="showSearch" @queryTable="getList" />
    </el-row>

    <el-table v-loading="loading" :data="linkList">
      <el-table-column label="ID" align="center" prop="linkId" width="80" />
      <el-table-column label="名称" align="center" prop="linkName" min-width="150" />
      <el-table-column label="地址" align="center" prop="linkUrl" min-width="240" :show-overflow-tooltip="true" />
      <el-table-column label="描述" align="center" prop="linkDesc" min-width="220" :show-overflow-tooltip="true" />
      <el-table-column label="排序" align="center" prop="sortOrder" width="90" />
      <el-table-column label="状态" align="center" prop="status" width="100">
        <template slot-scope="scope">
          <el-tag v-if="scope.row.status === '0'" type="success">正常</el-tag>
          <el-tag v-else type="danger">停用</el-tag>
        </template>
      </el-table-column>
      <el-table-column label="操作" align="center" class-name="small-padding fixed-width" width="220">
        <template slot-scope="scope">
          <el-button
            size="mini"
            type="text"
            icon="el-icon-edit"
            @click="handleUpdate(scope.row)"
            v-hasPermi="['blog:link:edit']"
          >修改</el-button>
          <el-button
            size="mini"
            type="text"
            icon="el-icon-delete"
            @click="handleDelete(scope.row)"
            v-hasPermi="['blog:link:remove']"
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

    <el-dialog :title="title" :visible.sync="open" width="560px" append-to-body>
      <el-form ref="form" :model="form" :rules="rules" label-width="90px">
        <el-form-item label="友链名称" prop="linkName">
          <el-input v-model="form.linkName" maxlength="50" show-word-limit />
        </el-form-item>
        <el-form-item label="友链地址" prop="linkUrl">
          <el-input v-model="form.linkUrl" placeholder="例如：https://example.com" />
        </el-form-item>
        <el-form-item label="Logo地址" prop="linkLogo">
          <el-input v-model="form.linkLogo" placeholder="可选" />
        </el-form-item>
        <el-form-item label="描述" prop="linkDesc">
          <el-input v-model="form.linkDesc" type="textarea" :rows="3" maxlength="200" show-word-limit />
        </el-form-item>
        <el-form-item label="排序" prop="sortOrder">
          <el-input-number v-model="form.sortOrder" :min="0" :max="999" />
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-radio-group v-model="form.status">
            <el-radio label="0">正常</el-radio>
            <el-radio label="1">停用</el-radio>
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
import { listLink, getLink, addLink, updateLink, delLink } from '@/api/blog'

export default {
  name: 'BlogLinkList',
  data() {
    return {
      loading: false,
      showSearch: true,
      total: 0,
      linkList: [],
      open: false,
      title: '',
      queryParams: {
        pageNum: 1,
        pageSize: 10,
        linkName: null,
        status: null
      },
      form: {},
      rules: {
        linkName: [{ required: true, message: '友链名称不能为空', trigger: 'blur' }],
        linkUrl: [{ required: true, message: '友链地址不能为空', trigger: 'blur' }]
      }
    }
  },
  created() {
    this.getList()
  },
  methods: {
    getList() {
      this.loading = true
      listLink(this.queryParams).then(response => {
        this.linkList = response.rows || []
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
        linkId: null,
        linkName: '',
        linkUrl: '',
        linkLogo: '',
        linkDesc: '',
        sortOrder: 0,
        status: '0'
      }
      if (this.$refs.form) {
        this.$refs.form.resetFields()
      }
    },
    handleAdd() {
      this.resetFormData()
      this.open = true
      this.title = '新增友链'
    },
    handleUpdate(row) {
      this.resetFormData()
      getLink(row.linkId).then(response => {
        this.form = response.data
        this.open = true
        this.title = '修改友链'
      })
    },
    submitForm() {
      this.$refs.form.validate(valid => {
        if (!valid) {
          return
        }
        const request = this.form.linkId ? updateLink(this.form) : addLink(this.form)
        request.then(() => {
          this.$modal.msgSuccess(this.form.linkId ? '修改成功' : '新增成功')
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
      this.$modal.confirm(`确认删除友链 "${row.linkName}" 吗？`).then(() => {
        return delLink(row.linkId)
      }).then(() => {
        this.$modal.msgSuccess('删除成功')
        this.getList()
      }).catch(() => {})
    }
  }
}
</script>
