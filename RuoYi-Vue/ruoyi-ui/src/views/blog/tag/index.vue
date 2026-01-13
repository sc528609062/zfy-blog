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
          v-hasPermi="['blog:tag:add']"
        >新增</el-button>
      </el-col>
      <el-col :span="1.5">
        <el-button
          type="danger"
          plain
          icon="el-icon-delete"
          size="mini"
          :disabled="multiple"
          :loading="deleteLoading"
          @click="handleDelete"
          v-hasPermi="['blog:tag:remove']"
        >批量删除</el-button>
      </el-col>
      <el-col :span="1.5">
        <el-button
          type="warning"
          plain
          icon="el-icon-refresh"
          size="mini"
          @click="handleSyncCount"
          v-hasPermi="['blog:tag:edit']"
        >同步数量</el-button>
      </el-col>
      <right-toolbar :showSearch.sync="showSearch" @queryTable="getList"></right-toolbar>
    </el-row>

    <el-table v-loading="loading" :data="tagList" @selection-change="handleSelectionChange">
      <el-table-column type="selection" width="55" align="center" />
      <el-table-column label="标签ID" align="center" prop="tagId" width="80" />
      <el-table-column label="标签名称" align="center" prop="tagName" />
      <el-table-column label="描述" align="center" prop="tagDesc" :show-overflow-tooltip="true" min-width="120" />
      <el-table-column label="文章数量" align="center" prop="articleCount" width="100">
        <template slot-scope="scope">
          <el-tag size="mini" type="primary">{{ scope.row.articleCount || 0 }}</el-tag>
        </template>
      </el-table-column>
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

    <pagination
      v-show="total>0"
      :total="total"
      :page.sync="queryParams.pageNum"
      :limit.sync="queryParams.pageSize"
      @pagination="getList"
    />

    <!-- 新增/修改对话框 -->
    <el-dialog :title="title" :visible.sync="open" width="500px" append-to-body>
      <el-form ref="form" :model="form" :rules="rules" label-width="80px">
        <el-form-item label="标签名称" prop="tagName">
          <el-input v-model="form.tagName" placeholder="请输入标签名称" maxlength="50" show-word-limit />
        </el-form-item>
        <el-form-item label="标签描述" prop="tagDesc">
          <el-input
            v-model="form.tagDesc"
            type="textarea"
            placeholder="请输入标签描述"
            :rows="3"
            maxlength="200"
            show-word-limit
          />
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-radio-group v-model="form.status">
            <el-radio :label="'0'">正常</el-radio>
            <el-radio :label="'1'">停用</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button type="primary" @click="submitForm" :loading="submitLoading">确 定</el-button>
        <el-button @click="cancel">取 消</el-button>
      </div>
    </el-dialog>
  </div>
</template>

<script>
import { listTag, delTag, addTag, updateTag, syncTagCount } from '@/api/blog'

export default {
  name: 'BlogTagList',
  data() {
    return {
      loading: true,
      deleteLoading: false,
      submitLoading: false,
      showSearch: true,
      total: 0,
      tagList: [],
      ids: [],
      single: true,
      multiple: true,
      title: '',
      open: false,
      form: {},
      rules: {
        tagName: [
          { required: true, message: '标签名称不能为空', trigger: 'blur' },
          { min: 2, max: 50, message: '标签名称长度在 2 到 50 个字符', trigger: 'blur' }
        ],
        tagDesc: [
          { max: 200, message: '描述长度不能超过 200 个字符', trigger: 'blur' }
        ]
      },
      queryParams: {
        pageNum: 1,
        pageSize: 10,
        tagName: null,
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
      listTag(this.queryParams).then(response => {
        this.tagList = response.rows
        this.total = response.total
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
    handleSelectionChange(selection) {
      this.ids = selection.map(item => item.tagId)
      this.single = selection.length !== 1
      this.multiple = !selection.length
    },
    handleAdd() {
      this.reset()
      this.open = true
      this.title = '添加标签'
    },
    handleUpdate(row) {
      this.reset()
      this.form = {
        tagId: row.tagId,
        tagName: row.tagName,
        tagDesc: row.tagDesc,
        status: row.status
      }
      this.open = true
      this.title = '修改标签'
    },
    submitForm() {
      this.$refs.form.validate(valid => {
        if (valid) {
          this.submitLoading = true
          if (this.form.tagId != null) {
            updateTag(this.form).then(() => {
              this.$modal.msgSuccess('修改成功')
              this.open = false
              this.getList()
            }).finally(() => {
              this.submitLoading = false
            })
          } else {
            addTag(this.form).then(() => {
              this.$modal.msgSuccess('新增成功')
              this.open = false
              this.getList()
            }).finally(() => {
              this.submitLoading = false
            })
          }
        }
      })
    },
    cancel() {
      this.open = false
      this.reset()
    },
    reset() {
      this.form = {
        tagId: null,
        tagName: null,
        tagDesc: null,
        status: '0'
      }
      if (this.$refs.form) {
        this.$refs.form.resetFields()
      }
    },
    handleDelete(row) {
      const tagIds = row.tagId || this.ids
      const tagName = row.tagName || '选中的标签'
      this.$modal.confirm('是否确认删除标签"' + tagName + '"？').then(() => {
        this.deleteLoading = true
        return delTag(tagIds)
      }).then(() => {
        this.getList()
        this.$modal.msgSuccess('删除成功')
      }).catch(() => {
        if (this.deleteLoading) {
          this.$modal.msgError('删除失败')
        }
      }).finally(() => {
        this.deleteLoading = false
      })
    },
    handleSyncCount() {
      this.$modal.confirm('确定要同步所有标签的文章数量吗？').then(() => {
        return syncTagCount()
      }).then(() => {
        this.getList()
        this.$modal.msgSuccess('同步成功')
      }).catch(() => {})
    }
  }
}
</script>
