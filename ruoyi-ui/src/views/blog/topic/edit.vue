<template>
  <div class="app-container topic-edit">
    <el-form ref="form" :model="form" :rules="rules" label-width="100px">
      <el-row>
        <el-col :span="24">
          <el-form-item label="专题标题" prop="topicTitle">
            <el-input v-model="form.topicTitle" placeholder="请输入专题标题" maxlength="100" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row>
        <el-col :span="24">
          <el-form-item label="专题封面" prop="topicCover">
            <image-upload v-model="form.topicCover" :limit="1" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row>
        <el-col :span="24">
          <el-form-item label="专题简介" prop="topicSummary">
            <el-input
              v-model="form.topicSummary"
              type="textarea"
              :rows="3"
              placeholder="请输入专题简介"
              maxlength="200"
              show-word-limit
            />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row>
        <el-col :span="24">
          <el-form-item label="专题描述" prop="topicDesc">
            <el-input
              v-model="form.topicDesc"
              type="textarea"
              :rows="5"
              placeholder="请输入专题详细描述"
              maxlength="1000"
              show-word-limit
            />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row>
        <el-col :span="12">
          <el-form-item label="显示顺序" prop="sortOrder">
            <el-input-number v-model="form.sortOrder" :min="0" :max="999" controls-position="right" />
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <el-form-item label="状态" prop="status">
            <el-radio-group v-model="form.status">
              <el-radio label="0">正常</el-radio>
              <el-radio label="1">停用</el-radio>
            </el-radio-group>
          </el-form-item>
        </el-col>
      </el-row>
    </el-form>

    <div class="form-footer">
      <el-button type="primary" @click="submitForm" v-hasPermi="['blog:topic:add', 'blog:topic:edit']">
        {{ form.topicId ? '保存修改' : '立即创建' }}
      </el-button>
      <el-button @click="cancel">取消</el-button>
    </div>
  </div>
</template>

<script>
import { getTopic, addTopic, updateTopic } from '@/api/blog'

export default {
  name: 'BlogTopicEdit',
  data() {
    return {
      loading: false,
      form: {
        topicId: null,
        topicTitle: null,
        topicCover: null,
        topicSummary: null,
        topicDesc: null,
        sortOrder: 0,
        status: '0'
      },
      rules: {
        topicTitle: [
          { required: true, message: '专题标题不能为空', trigger: 'blur' },
          { min: 2, max: 100, message: '专题标题长度在 2 到 100 个字符', trigger: 'blur' }
        ],
        topicSummary: [
          { max: 200, message: '专题简介不能超过 200 个字符', trigger: 'blur' }
        ],
        topicDesc: [
          { max: 1000, message: '专题描述不能超过 1000 个字符', trigger: 'blur' }
        ],
        sortOrder: [
          { type: 'number', message: '显示顺序必须为数字', trigger: 'blur' }
        ],
        status: [
          { required: true, message: '状态不能为空', trigger: 'change' }
        ]
      }
    }
  },
  created() {
    const topicId = this.$route.query.id
    if (topicId) {
      this.getTopicDetail(topicId)
    }
  },
  methods: {
    getTopicDetail(topicId) {
      this.loading = true
      getTopic(topicId).then(response => {
        this.form = response.data
        this.loading = false
      }).catch(() => {
        this.loading = false
      })
    },
    submitForm() {
      this.$refs.form.validate(valid => {
        if (valid) {
          if (this.form.topicId) {
            updateTopic(this.form).then(() => {
              this.$modal.msgSuccess('修改成功')
              this.cancel()
            })
          } else {
            addTopic(this.form).then(() => {
              this.$modal.msgSuccess('创建成功')
              this.cancel()
            })
          }
        }
      })
    },
    cancel() {
      this.$router.back()
    }
  }
}
</script>

<style scoped>
.topic-edit {
  padding: 20px;
}

.form-footer {
  margin-top: 30px;
  text-align: center;
}

.form-footer .el-button {
  margin: 0 10px;
}
</style>
