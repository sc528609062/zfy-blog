<template>
  <div class="app-container">
    <el-row :gutter="10" class="mb8">
      <el-col :span="6">
        <el-input v-model="query.keyword" size="small" placeholder="分类名称关键字" clearable />
      </el-col>
      <el-col :span="18" style="text-align:right;">
        <el-button type="primary" size="small" icon="el-icon-plus" @click="openEdit()" v-hasPermi="['blog:linkCategory:add']">
          新建分类
        </el-button>
      </el-col>
    </el-row>

    <el-table v-loading="loading" :data="filteredList">
      <el-table-column prop="categoryName" label="分类名称" min-width="220" />
      <el-table-column prop="sortOrder" label="排序" width="120" />
      <el-table-column prop="status" label="状态" width="120">
        <template slot-scope="scope">
          <el-tag :type="scope.row.status === '0' ? 'success' : 'danger'" size="mini">
            {{ scope.row.status === '0' ? '正常' : '停用' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="remark" label="备注" min-width="220" :show-overflow-tooltip="true" />
      <el-table-column label="操作" width="220">
        <template slot-scope="scope">
          <el-button size="mini" type="text" @click="openEdit(scope.row)" v-hasPermi="['blog:linkCategory:edit']">编辑</el-button>
          <el-button size="mini" type="text" style="color:#f56c6c;" @click="remove(scope.row)" v-hasPermi="['blog:linkCategory:remove']">删除</el-button>
        </template>
      </el-table-column>
    </el-table>

    <el-dialog :title="form.categoryId ? '编辑分类' : '新建分类'" :visible.sync="open" width="520px" append-to-body>
      <el-form ref="formRef" :model="form" label-width="90px">
        <el-form-item label="分类名称">
          <el-input v-model="form.categoryName" />
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="form.sortOrder" :min="0" :max="999" />
        </el-form-item>
        <el-form-item label="状态">
          <el-radio-group v-model="form.status">
            <el-radio label="0">正常</el-radio>
            <el-radio label="1">停用</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="备注">
          <el-input type="textarea" :rows="3" v-model="form.remark" />
        </el-form-item>
      </el-form>
      <div slot="footer">
        <el-button @click="open = false">取消</el-button>
        <el-button type="primary" @click="submit">保存</el-button>
      </div>
    </el-dialog>
  </div>
</template>

<script>
const CACHE_KEY = 'zfy_admin_link_category_records'

export default {
  name: 'BlogLinkCategoryList',
  data() {
    return {
      loading: false,
      open: false,
      query: {
        keyword: ''
      },
      list: [],
      form: {
        categoryId: null,
        categoryName: '',
        sortOrder: 0,
        status: '0',
        remark: ''
      }
    }
  },
  computed: {
    filteredList() {
      const keyword = this.query.keyword.trim().toLowerCase()
      return this.list.filter(item => !keyword || item.categoryName.toLowerCase().includes(keyword))
    }
  },
  created() {
    this.load()
  },
  methods: {
    load() {
      this.loading = true
      const records = this.$cache.local.getJSON(CACHE_KEY)
      if (records && records.length) {
        this.list = records
      } else {
        this.list = [
          { categoryId: 1, categoryName: '技术社区', sortOrder: 1, status: '0', remark: '默认分类' }
        ]
        this.persist()
      }
      this.loading = false
    },
    persist() {
      this.$cache.local.setJSON(CACHE_KEY, this.list)
    },
    openEdit(row) {
      if (row) {
        this.form = { ...row }
      } else {
        this.form = { categoryId: null, categoryName: '', sortOrder: 0, status: '0', remark: '' }
      }
      this.open = true
    },
    submit() {
      if (!this.form.categoryName) {
        this.$modal.msgWarning('分类名称不能为空')
        return
      }
      if (this.form.categoryId) {
        this.list = this.list.map(item => item.categoryId === this.form.categoryId ? { ...this.form } : item)
      } else {
        const maxId = this.list.reduce((max, item) => Math.max(max, Number(item.categoryId || 0)), 0)
        this.list.unshift({ ...this.form, categoryId: maxId + 1 })
      }
      this.persist()
      this.open = false
      this.$modal.msgSuccess('保存成功')
    },
    remove(row) {
      this.$modal.confirm(`确认删除分类 "${row.categoryName}" 吗？`).then(() => {
        this.list = this.list.filter(item => item.categoryId !== row.categoryId)
        this.persist()
        this.$modal.msgSuccess('删除成功')
      }).catch(() => {})
    }
  }
}
</script>
