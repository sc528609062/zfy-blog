<template>
  <div class="app-container">
    <el-form :model="queryParams" ref="queryForm" size="small" :inline="true" v-show="showSearch" label-width="68px">
      <el-form-item label="专题标题" prop="topicTitle">
        <el-input
          v-model="queryParams.topicTitle"
          placeholder="请输入专题标题"
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
      <el-form-item label="创建时间" prop="createTime">
        <el-date-picker
          v-model="queryParams.createTime"
          type="daterange"
          range-separator="-"
          start-placeholder="开始日期"
          end-placeholder="结束日期"
          value-format="yyyy-MM-dd"
          clearable
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
          v-hasPermi="['blog:topic:add']"
        >新增专题</el-button>
      </el-col>
      <el-col :span="1.5">
        <el-button
          type="success"
          plain
          icon="el-icon-refresh"
          size="mini"
          @click="handleSyncCount"
          v-hasPermi="['blog:topic:edit']"
        >同步文章数量</el-button>
      </el-col>
      <el-col :span="1.5">
        <el-button
          type="danger"
          plain
          icon="el-icon-delete"
          size="mini"
          :disabled="multiple"
          @click="handleDelete"
          v-hasPermi="['blog:topic:remove']"
        >批量删除</el-button>
      </el-col>
      <right-toolbar :showSearch.sync="showSearch" @queryTable="getList"></right-toolbar>
    </el-row>

    <el-table v-loading="loading" :data="topicList" @selection-change="handleSelectionChange">
      <el-table-column type="selection" width="55" align="center" />
      <el-table-column label="封面" align="center" width="100">
        <template slot-scope="scope">
          <el-image
            v-if="scope.row.topicCover"
            :src="processImageUrl(scope.row.topicCover)"
            :preview-src-list="[processImageUrl(scope.row.topicCover)]"
            fit="cover"
            style="width: 60px; height: 40px; border-radius: 4px;"
          >
          </el-image>
          <span v-else>-</span>
        </template>
      </el-table-column>
      <el-table-column label="专题标题" align="center" prop="topicTitle" min-width="200" :show-overflow-tooltip="true" />
      <el-table-column label="专题简介" align="center" prop="topicSummary" min-width="200" :show-overflow-tooltip="true" />
      <el-table-column label="文章数量" align="center" prop="articleCount" width="100">
        <template slot-scope="scope">
          <el-tag size="small" type="info">{{ scope.row.articleCount || 0 }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column label="排序" align="center" prop="sortOrder" width="100" />
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
            v-hasPermi="['blog:topic:edit']"
          >修改</el-button>
          <el-button
            size="mini"
            type="text"
            icon="el-icon-delete"
            @click="handleDelete(scope.row)"
            v-hasPermi="['blog:topic:remove']"
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

    <!-- 添加或修改专题对话框 -->
    <el-dialog :title="title" :visible.sync="open" width="600px" append-to-body :close-on-click-modal="false">
      <el-form ref="form" :model="form" :rules="rules" label-width="80px">
        <el-form-item label="专题标题" prop="topicTitle">
          <el-input v-model="form.topicTitle" placeholder="请输入专题标题" maxlength="100" show-word-limit />
        </el-form-item>
        <el-form-item label="封面图片" prop="topicCover">
          <image-upload v-model="form.topicCover" :limit="1"/>
        </el-form-item>
        <el-form-item label="专题简介" prop="topicSummary">
          <el-input v-model="form.topicSummary" type="textarea" placeholder="请输入专题简介" :rows="3" maxlength="200" show-word-limit />
        </el-form-item>
        <el-form-item label="专题描述" prop="topicDesc">
          <el-input v-model="form.topicDesc" type="textarea" placeholder="请输入专题描述" :rows="5" maxlength="500" show-word-limit />
        </el-form-item>
        <el-form-item label="排序" prop="sortOrder">
          <el-input-number v-model="form.sortOrder" :min="0" :max="9999" controls-position="right" />
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-radio-group v-model="form.status">
            <el-radio :label="'0'">正常</el-radio>
            <el-radio :label="'1'">停用</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="cancel">取 消</el-button>
        <el-button type="primary" @click="submitForm" :loading="submitLoading">确 定</el-button>
      </div>
    </el-dialog>
  </div>
</template>

<script>
import { listTopic, getTopic, delTopic, addTopic, updateTopic, syncTopicCount } from '@/api/blog'

export default {
  name: 'BlogTopicList',
  data() {
    return {
      loading: true,
      submitLoading: false,
      ids: [],
      single: true,
      multiple: true,
      showSearch: true,
      total: 0,
      topicList: [],
      title: '',
      open: false,
      queryParams: {
        pageNum: 1,
        pageSize: 10,
        topicTitle: null,
        status: null,
        createTime: null
      },
      form: {},
      rules: {
        topicTitle: [
          { required: true, message: '专题标题不能为空', trigger: 'blur' },
          { min: 2, max: 100, message: '专题标题长度在 2 到 100 个字符', trigger: 'blur' }
        ],
        topicSummary: [
          { max: 200, message: '专题简介长度不能超过 200 个字符', trigger: 'blur' }
        ],
        topicDesc: [
          { max: 500, message: '专题描述长度不能超过 500 个字符', trigger: 'blur' }
        ],
        sortOrder: [
          { type: 'number', message: '排序必须为数字', trigger: 'blur' }
        ]
      }
    }
  },
  created() {
    this.getList()
  },
  methods: {
    getList() {
      this.loading = true
      // 处理日期范围
      if (this.queryParams.createTime && this.queryParams.createTime.length === 2) {
        this.queryParams.params = {
          beginTime: this.queryParams.createTime[0],
          endTime: this.queryParams.createTime[1]
        }
      } else {
        this.queryParams.params = {}
      }
      listTopic(this.queryParams).then(response => {
        this.topicList = response.rows
        this.total = response.total
        this.loading = false
      })
    },
    handleQuery() {
      this.queryParams.pageNum = 1
      this.getList()
    },
    resetQuery() {
      this.queryParams.createTime = null
      this.queryParams.params = {}
      this.resetForm('queryForm')
      this.handleQuery()
    },
    handleSelectionChange(selection) {
      this.ids = selection.map(item => item.topicId)
      this.single = selection.length !== 1
      this.multiple = !selection.length
    },
    handleAdd() {
      this.reset()
      this.open = true
      this.title = '添加专题'
    },
    handleUpdate(row) {
      this.reset()
      const topicId = row.topicId || this.ids[0]
      getTopic(topicId).then(response => {
        this.form = response.data
        this.open = true
        this.title = '修改专题'
      })
    },
    submitForm() {
      this.$refs.form.validate(valid => {
        if (valid) {
          this.submitLoading = true
          if (this.form.topicId != null) {
            updateTopic(this.form).then(() => {
              this.$modal.msgSuccess('修改成功')
              this.open = false
              this.getList()
            }).finally(() => {
              this.submitLoading = false
            })
          } else {
            addTopic(this.form).then(() => {
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
    handleDelete(row) {
      const topicIds = row.topicId || this.ids
      const loading = this.$loading({
        lock: true,
        text: '删除中...',
        spinner: 'el-icon-loading',
        background: 'rgba(0, 0, 0, 0.7)'
      })
      this.$modal.confirm('是否确认删除专题编号为"' + topicIds + '"的数据项？').then(function() {
        return delTopic(topicIds)
      }).then(() => {
        loading.close()
        this.getList()
        this.$modal.msgSuccess('删除成功')
      }).catch(() => {
        loading.close()
      })
    },
    handleSyncCount() {
      const loading = this.$loading({
        lock: true,
        text: '同步中...',
        spinner: 'el-icon-loading',
        background: 'rgba(0, 0, 0, 0.7)'
      })
      syncTopicCount().then(() => {
        loading.close()
        this.getList()
        this.$modal.msgSuccess('同步成功')
      }).catch(() => {
        loading.close()
      })
    },
    reset() {
      this.form = {
        topicId: null,
        topicTitle: null,
        topicCover: null,
        topicSummary: null,
        topicDesc: null,
        articleCount: 0,
        sortOrder: 0,
        status: '0'
      }
      this.resetForm('form')
    },
    cancel() {
      this.open = false
      this.reset()
    },
    processImageUrl(url) {
      if (!url) return url
      if (url.startsWith('/profile/')) {
        return '/dev-api' + url
      }
      return url
    }
  }
}
</script>

<style scoped>
</style>
