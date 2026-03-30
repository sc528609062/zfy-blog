<template>
  <div class="app-container">
    <el-row :gutter="10" class="mb8">
      <el-col :span="5">
        <el-input v-model="query.pageTitle" size="small" placeholder="按页面标题筛选" clearable />
      </el-col>
      <el-col :span="5">
        <el-select v-model="query.status" size="small" placeholder="状态" clearable>
          <el-option label="草稿" value="0" />
          <el-option label="发布" value="1" />
        </el-select>
      </el-col>
      <el-col :span="14" style="text-align:right;">
        <el-button type="primary" size="small" icon="el-icon-plus" @click="openEdit()" v-hasPermi="['blog:page:add']">新建页面</el-button>
      </el-col>
    </el-row>

    <el-table :data="filteredPages" v-loading="loading">
      <el-table-column prop="pageTitle" label="页面标题" min-width="220" />
      <el-table-column prop="pageSlug" label="路由别名" width="180" />
      <el-table-column prop="showInMenu" label="导航显示" width="120">
        <template slot-scope="scope">
          <el-tag size="mini" :type="scope.row.showInMenu === '1' ? 'success' : 'info'">
            {{ scope.row.showInMenu === '1' ? '显示' : '隐藏' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="status" label="状态" width="120">
        <template slot-scope="scope">
          <el-tag size="mini" :type="scope.row.status === '1' ? 'success' : 'warning'">
            {{ scope.row.status === '1' ? '发布' : '草稿' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="updateTime" label="更新时间" width="180">
        <template slot-scope="scope">{{ parseTime(scope.row.updateTime || scope.row.createTime) }}</template>
      </el-table-column>
      <el-table-column label="操作" width="220" fixed="right">
        <template slot-scope="scope">
          <el-button size="mini" type="text" @click="openEdit(scope.row)" v-hasPermi="['blog:page:edit']">编辑</el-button>
          <el-button size="mini" type="text" style="color:#f56c6c;" @click="remove(scope.row)" v-hasPermi="['blog:page:remove']">删除</el-button>
        </template>
      </el-table-column>
    </el-table>

    <el-dialog :title="form.pageId ? '编辑页面' : '新建页面'" :visible.sync="open" width="760px" append-to-body>
      <el-form ref="formRef" :model="form" :rules="rules" label-width="90px">
        <el-form-item label="页面标题" prop="pageTitle">
          <el-input v-model="form.pageTitle" maxlength="120" show-word-limit />
        </el-form-item>
        <el-form-item label="路由别名" prop="pageSlug">
          <el-input v-model="form.pageSlug" placeholder="如：about-us" />
        </el-form-item>
        <el-form-item label="页面内容" prop="pageContent">
          <el-input type="textarea" :rows="8" v-model="form.pageContent" />
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-radio-group v-model="form.status">
            <el-radio label="0">草稿</el-radio>
            <el-radio label="1">发布</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="导航显示" prop="showInMenu">
          <el-switch
            v-model="form.showInMenu"
            active-value="1"
            inactive-value="0"
            active-text="显示"
            inactive-text="隐藏"
          />
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
const CACHE_KEY = 'zfy_admin_page_records'

export default {
  name: 'BlogPageList',
  data() {
    return {
      loading: false,
      open: false,
      query: {
        pageTitle: '',
        status: ''
      },
      pageList: [],
      form: {
        pageId: null,
        pageTitle: '',
        pageSlug: '',
        pageContent: '',
        showInMenu: '1',
        status: '0'
      },
      rules: {
        pageTitle: [{ required: true, message: '页面标题不能为空', trigger: 'blur' }],
        pageSlug: [{ required: true, message: '路由别名不能为空', trigger: 'blur' }]
      }
    }
  },
  computed: {
    filteredPages() {
      const title = this.query.pageTitle.trim().toLowerCase()
      const status = this.query.status
      return this.pageList.filter(item => {
        const matchTitle = !title || item.pageTitle.toLowerCase().includes(title)
        const matchStatus = !status || item.status === status
        return matchTitle && matchStatus
      })
    }
  },
  created() {
    this.load()
  },
  methods: {
    load() {
      this.loading = true
      const localList = this.$cache.local.getJSON(CACHE_KEY) || []
      if (!localList.length) {
        this.pageList = [
          {
            pageId: 1,
            pageTitle: '关于我们',
            pageSlug: 'about-us',
            pageContent: '这里是关于我们页面内容。',
            showInMenu: '1',
            status: '1',
            createTime: new Date(),
            updateTime: new Date()
          }
        ]
        this.persist()
      } else {
        this.pageList = localList
      }
      this.loading = false
    },
    persist() {
      this.$cache.local.setJSON(CACHE_KEY, this.pageList)
    },
    openEdit(row) {
      if (row) {
        this.form = { ...row }
      } else {
        this.form = {
          pageId: null,
          pageTitle: '',
          pageSlug: '',
          pageContent: '',
          showInMenu: '1',
          status: '0'
        }
      }
      this.open = true
    },
    submit() {
      this.$refs.formRef.validate(valid => {
        if (!valid) {
          return
        }
        if (this.form.pageId) {
          this.pageList = this.pageList.map(item => item.pageId === this.form.pageId ? { ...this.form, updateTime: new Date() } : item)
        } else {
          const maxId = this.pageList.reduce((max, item) => Math.max(max, Number(item.pageId || 0)), 0)
          this.pageList.unshift({
            ...this.form,
            pageId: maxId + 1,
            createTime: new Date(),
            updateTime: new Date()
          })
        }
        this.persist()
        this.open = false
        this.$modal.msgSuccess('保存成功')
      })
    },
    remove(row) {
      this.$modal.confirm(`确认删除页面 "${row.pageTitle}" 吗？`).then(() => {
        this.pageList = this.pageList.filter(item => item.pageId !== row.pageId)
        this.persist()
        this.$modal.msgSuccess('删除成功')
      }).catch(() => {})
    }
  }
}
</script>
