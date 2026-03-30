<template>
  <div class="app-container article-edit">
    <div class="edit-layout">
      <!-- 左侧：编辑区域 -->
      <div class="edit-main">
        <el-input
          v-model="form.articleTitle"
          placeholder="请输入文章标题"
          class="article-title-input"
          maxlength="200"
        />

        <!-- Markdown 编辑器 -->
        <div v-if="editorType === 'markdown'" class="editor-container">
          <mavon-editor
            ref="mdEditor"
            v-model="form.articleContent"
            :toolbars="markdownToolbars"
            :subfield="true"
            :preview="true"
            :scrollStyle="true"
            :ishljs="true"
            @imgAdd="handleImgAdd"
            class="markdown-editor"
          />
        </div>

        <!-- 富文本编辑器 -->
        <div v-else class="editor-container">
          <quill-editor
            ref="quillEditor"
            v-model="form.articleContent"
            :options="quillOptions"
            @ready="onQuillReady"
            class="rich-editor"
          />
        </div>
      </div>

      <!-- 右侧：设置面板 -->
      <div class="edit-sidebar">
        <div class="sidebar-section">
          <div class="section-title">发布</div>
          <div class="action-buttons">
            <el-button type="primary" size="medium" @click="submitForm" class="full-width" v-hasPermi="['blog:article:add', 'blog:article:edit']">
              {{ form.articleId ? '保存修改' : '立即发布' }}
            </el-button>
            <el-button type="info" size="medium" @click="saveDraft" class="full-width" v-hasPermi="['blog:article:add']" v-if="!form.articleId">
              保存草稿
            </el-button>
            <el-button size="medium" @click="preview" class="full-width" v-if="form.articleContent">
              预览
            </el-button>
            <el-button size="medium" @click="cancel" class="full-width">
              取消
            </el-button>
          </div>
        </div>

        <div class="sidebar-section">
          <div class="section-title">编辑器类型</div>
          <el-radio-group v-model="editorType" @change="handleEditorChange">
            <el-radio label="markdown">Markdown</el-radio>
            <el-radio label="rich">富文本</el-radio>
          </el-radio-group>
        </div>

        <div class="sidebar-section">
          <div class="section-title">文章状态</div>
          <el-radio-group v-model="form.articleStatus">
            <el-radio label="0">草稿</el-radio>
            <el-radio label="1">发布</el-radio>
            <el-radio label="2">下架</el-radio>
          </el-radio-group>
        </div>

        <div class="sidebar-section">
          <div class="section-title">显示设置</div>
          <div class="switch-item">
            <span>置顶</span>
            <el-switch v-model="form.isTop" active-value="1" inactive-value="0"></el-switch>
          </div>
          <div class="switch-item">
            <span>推荐</span>
            <el-switch v-model="form.isRecommend" active-value="1" inactive-value="0"></el-switch>
          </div>
        </div>

        <div class="sidebar-section">
          <div class="section-title">封面图片</div>
          <image-upload v-model="form.articleCover" :limit="1" />
        </div>

        <div class="sidebar-section">
          <div class="section-title">文章分类</div>
          <el-select v-model="form.categoryId" placeholder="请选择分类" clearable class="full-width">
            <el-option
              v-for="item in categoryList"
              :key="item.categoryId"
              :label="item.categoryName"
              :value="item.categoryId"
            />
          </el-select>
        </div>

        <div class="sidebar-section">
          <div class="section-title">文章标签</div>
          <el-select v-model="form.tagIds" multiple placeholder="请选择标签" clearable class="full-width">
            <el-option
              v-for="item in tagList"
              :key="item.tagId"
              :label="item.tagName"
              :value="item.tagId"
            />
          </el-select>
        </div>

        <div class="sidebar-section">
          <div class="section-title">所属专题</div>
          <el-select v-model="form.topicId" placeholder="请选择专题(可选)" clearable class="full-width">
            <el-option
              v-for="item in topicList"
              :key="item.topicId"
              :label="item.topicTitle"
              :value="item.topicId"
            />
          </el-select>
        </div>

        <div class="sidebar-section">
          <div class="section-title">文章摘要</div>
          <el-input
            v-model="form.articleSummary"
            type="textarea"
            :rows="4"
            placeholder="请输入文章摘要"
            maxlength="500"
            show-word-limit
          />
        </div>

        <div class="sidebar-section">
          <div class="section-title">是否原创</div>
          <el-radio-group v-model="form.isOriginal">
            <el-radio label="1">原创</el-radio>
            <el-radio label="0">转载</el-radio>
          </el-radio-group>
        </div>

        <div class="sidebar-section" v-if="form.isOriginal === '0'">
          <div class="section-title">转载来源</div>
          <el-input v-model="form.sourceUrl" placeholder="请输入转载来源URL" />
        </div>
      </div>
    </div>

    <!-- 预览对话框 -->
    <el-dialog title="文章预览" :visible.sync="previewVisible" width="80%" top="5vh">
      <div v-if="editorType === 'markdown'" class="preview-container" v-html="renderedHtml"></div>
      <div v-else class="preview-container" v-html="form.articleContent"></div>
    </el-dialog>
  </div>
</template>

<script>
import { getArticle, addArticle, updateArticle, upload, listCategory, listTag, listTopic } from '@/api/blog'
import { mavonEditor } from 'mavon-editor'
import 'mavon-editor/dist/css/index.css'
import { quillEditor } from 'vue-quill-editor'
import 'quill/dist/quill.core.css'
import 'quill/dist/quill.snow.css'

export default {
  name: 'BlogArticleEdit',
  components: {
    mavonEditor,
    quillEditor
  },
  data() {
    return {
      loading: false,
      editorType: 'markdown', // 默认使用 Markdown 编辑器
      previewVisible: false,
      renderedHtml: '',
      form: {
        articleId: null,
        articleTitle: null,
        categoryId: null,
        topicId: null,
        tagIds: [],
        articleContent: null,
        articleSummary: null,
        isTop: '0',
        isRecommend: '0',
        isOriginal: '1',
        sourceUrl: null,
        articleStatus: '1',
        articleCover: null,
        editorType: 'markdown', // 保存编辑器类型
        authorId: null, // 作者ID
        authorName: null // 作者名称
      },
      categoryList: [],
      tagList: [],
      topicList: [],
      // Markdown 编辑器工具栏配置
      markdownToolbars: {
        bold: true, // 粗体
        italic: true, // 斜体
        header: true, // 标题
        underline: true, // 下划线
        strikethrough: true, // 中划线
        mark: true, // 标记
        superscript: true, // 上角标
        subscript: true, // 下角标
        quote: true, // 引用
        ol: true, // 有序列表
        ul: true, // 无序列表
        link: true, // 链接
        imagelink: true, // 图片链接
        image: true, // 图片上传
        code: true, // code
        table: true, // 表格
        fullscreen: true, // 全屏编辑
        readmodel: true, // 沉浸式阅读
        htmlcode: true, // 展示html源码
        help: true, // 帮助
        /* 1.3.5 */
        undo: true, // 上一步
        redo: true, // 下一步
        trash: true, // 清空
        save: true, // 保存（触发events中的save事件）
        /* 1.4.2 */
        navigation: true, // 导航目录
        /* 2.1.8 */
        alignleft: true, // 左对齐
        aligncenter: true, // 居中
        alignright: true, // 右对齐
        /* 2.2.1 */
        subfield: true, // 单双栏模式
        preview: true // 预览
      },
      // 富文本编辑器配置
      quillOptions: {
        placeholder: '请输入文章内容...',
        theme: 'snow',
        modules: {
          toolbar: [
            ['bold', 'italic', 'underline', 'strike'],
            ['blockquote', 'code-block'],
            [{ 'header': 1 }, { 'header': 2 }],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            [{ 'script': 'sub'}, { 'script': 'super' }],
            [{ 'indent': '-1'}, { 'indent': '+1' }],
            [{ 'direction': 'rtl' }],
            [{ 'size': ['small', false, 'large', 'huge'] }],
            [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
            [{ 'color': [] }, { 'background': [] }],
            [{ 'font': [] }],
            [{ 'align': [] }],
            ['clean'],
            ['link', 'image']
          ]
        }
      }
    }
  },
  data() {
    return {
      loading: false,
      editorType: 'markdown', // 默认使用 Markdown 编辑器
      previewVisible: false,
      renderedHtml: '',
      form: {
        articleId: null,
        articleTitle: null,
        categoryId: null,
        topicId: null,
        tagIds: [],
        articleContent: null,
        articleSummary: null,
        isTop: '0',
        isRecommend: '0',
        isOriginal: '1',
        sourceUrl: null,
        articleStatus: '1',
        articleCover: null,
        editorType: 'markdown', // 保存编辑器类型
        authorId: null, // 作者ID
        authorName: null // 作者名称
      },
      categoryList: [],
      tagList: [],
      topicList: [],
      // Markdown 编辑器工具栏配置
      markdownToolbars: {
        bold: true, // 粗体
        italic: true, // 斜体
        header: true, // 标题
        underline: true, // 下划线
        strikethrough: true, // 中划线
        mark: true, // 标记
        superscript: true, // 上角标
        subscript: true, // 下角标
        quote: true, // 引用
        ol: true, // 有序列表
        ul: true, // 无序列表
        link: true, // 链接
        imagelink: true, // 图片链接
        image: true, // 图片上传
        code: true, // code
        table: true, // 表格
        fullscreen: true, // 全屏编辑
        readmodel: true, // 沉浸式阅读
        htmlcode: true, // 展示html源码
        help: true, // 帮助
        /* 1.3.5 */
        undo: true, // 上一步
        redo: true, // 下一步
        trash: true, // 清空
        save: true, // 保存（触发events中的save事件）
        /* 1.4.2 */
        navigation: true, // 导航目录
        /* 2.1.8 */
        alignleft: true, // 左对齐
        aligncenter: true, // 居中
        alignright: true, // 右对齐
        /* 2.2.1 */
        subfield: true, // 单双栏模式
        preview: true // 预览
      },
      // 富文本编辑器配置
      quillOptions: {
        placeholder: '请输入文章内容...',
        theme: 'snow',
        modules: {
          toolbar: [
            ['bold', 'italic', 'underline', 'strike'],
            ['blockquote', 'code-block'],
            [{ 'header': 1 }, { 'header': 2 }],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            [{ 'script': 'sub'}, { 'script': 'super' }],
            [{ 'indent': '-1'}, { 'indent': '+1' }],
            [{ 'direction': 'rtl' }],
            [{ 'size': ['small', false, 'large', 'huge'] }],
            [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
            [{ 'color': [] }, { 'background': [] }],
            [{ 'font': [] }],
            [{ 'align': [] }],
            ['clean'],
            ['link', 'image', 'video']
          ]
        }
      }
    }
  },
  created() {
    this.getCategoryList()
    this.getTagList()
    this.getTopicList()
    const articleId = this.$route.query.id
    if (articleId) {
      this.getArticleDetail(articleId)
    }
  },
  methods: {
    getArticleDetail(articleId) {
      getArticle(articleId).then(response => {
        this.form = response.data
        // 处理标签数据
        if (response.data.tagIds && response.data.tagIds.length > 0) {
          this.form.tagIds = response.data.tagIds
        }
        // 根据保存的编辑器类型设置当前编辑器
        this.editorType = response.data.editorType || 'markdown'
      })
    },
    getCategoryList() {
      listCategory({ status: '0' }).then(response => {
        this.categoryList = response.rows
      })
    },
    getTagList() {
      listTag({ status: '0' }).then(response => {
        this.tagList = response.rows
      })
    },
    getTopicList() {
      listTopic({ status: '0' }).then(response => {
        this.topicList = response.rows
      })
    },
    // Markdown 编辑器图片上传
    handleImgAdd(pos, $file) {
      // 第一步：将图片上传到服务器
      this.$modal.loading('正在上传图片，请稍候...')
      upload($file).then(response => {
        // 第二步：将返回的url替换到文本原位置
        if (response.code === 200) {
          const fileName = response.fileName
          const url = response.url
          // 后端返回的是完整 URL，但前端需要使用 /dev-api 前缀的路径
          // 提取路径部分并添加 /dev-api 前缀
          const pathUrl = url.replace(/.*\/profile/, '/dev-api/profile')
          console.log('上传成功, fileName:', fileName, ', url:', url, ', pathUrl:', pathUrl, ', pos:', pos)
          // 使用 $img2Url 替换占位符为图片 URL
          // pos 参数是编辑器生成的临时索引（如：0, 1, 2...）
          this.$refs.mdEditor.$img2Url(pos, pathUrl)
          this.$modal.closeLoading()
          this.$message.success('图片上传成功')
        } else {
          this.$modal.closeLoading()
          this.$message.error('图片上传失败：' + response.msg)
        }
      }).catch(error => {
        this.$modal.closeLoading()
        this.$message.error('图片上传失败：' + error.message)
      })
    },
    // 编辑器切换
    handleEditorChange(type) {
      this.form.editorType = type
      // 可以在这里添加切换确认提示
      if (this.form.articleContent && type === 'rich') {
        this.$confirm('切换到富文本编辑器将丢失 Markdown 格式，是否继续？', '提示', {
          confirmButtonText: '确定',
          cancelButtonText: '取消',
          type: 'warning'
        }).then(() => {
          // 继续切换
        }).catch(() => {
          // 取消切换，切回 Markdown
          this.editorType = 'markdown'
        })
      }
    },
    // Quill 编辑器准备就绪
    onQuillReady() {
      console.log('Quill editor ready')
    },
    // 预览文章
    preview() {
      if (this.editorType === 'markdown') {
        // 渲染 Markdown 为 HTML
        let html = this.$refs.mdEditor.render(this.form.articleContent)
        // 处理图片路径
        html = this.processImageUrls(html)
        this.renderedHtml = html
      } else {
        // 富文本直接显示，处理图片路径
        let html = this.form.articleContent
        html = this.processImageUrls(html)
        this.renderedHtml = html
      }
      this.previewVisible = true
    },
    submitForm() {
      // 手动验证必填字段
      if (!this.form.articleTitle || this.form.articleTitle.trim() === '') {
        this.$message.error('文章标题不能为空')
        return
      }
      if (!this.form.articleContent || this.form.articleContent.trim() === '') {
        this.$message.error('文章内容不能为空')
        return
      }

      this.form.editorType = this.editorType
      // 设置作者信息
      const userName = this.$store.getters.name
      const userId = this.$store.getters.id
      this.form.authorName = userName
      this.form.authorId = userId

      if (this.form.articleId) {
        updateArticle(this.form).then(response => {
          this.$modal.msgSuccess('修改成功')
          this.cancel()
        })
      } else {
        addArticle(this.form).then(response => {
          this.$modal.msgSuccess('发布成功')
          this.cancel()
        })
      }
    },
    saveDraft() {
      this.form.articleStatus = '0'
      this.submitForm()
    },
    cancel() {
      this.$router.back()
    },
    // 处理文章内容中的图片路径，添加 /dev-api 前缀
    processImageUrls(content) {
      if (!content) return content
      // 匹配 Markdown 中的图片语法
![alt](/profile/)
      let processed = content.replace(/!\[([^\]]*)\]\(\/profile\//g, '![$1](/dev-api/profile/')
      // 匹配 HTML 中的图片标签
      processed = processed.replace(/src="\/profile\//g, 'src="/dev-api/profile/')
      return processed
    }
  }
}
</script>

<style scoped>
.article-edit {
  padding: 20px;
  height: calc(100vh - 84px);
  overflow: hidden;
}

.edit-layout {
  display: flex;
  height: 100%;
  gap: 20px;
}

.edit-main {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.article-title-input {
  font-size: 24px;
  font-weight: 500;
  padding: 12px 16px;
  margin-bottom: 20px;
  border: 2px solid transparent;
  border-radius: 8px;
  transition: all 0.3s;
}

.article-title-input:hover {
  border-color: #e4e7ed;
}

.article-title-input:focus {
  border-color: #409eff;
}

.article-title-input >>> .el-input__inner {
  font-size: 24px;
  font-weight: 500;
  border: none;
  padding: 0;
  height: auto;
  line-height: 1.4;
}

.editor-container {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-height: 0;
  border: 1px solid #DCDFE6;
  border-radius: 8px;
  overflow: hidden;
}

.markdown-editor {
  height: 100%;
}

.rich-editor {
  height: 100%;
}

/* 富文本编辑器样式 */
.rich-editor >>> .ql-container {
  height: calc(100% - 42px);
  font-size: 14px;
}

.rich-editor >>> .ql-toolbar {
  border: 1px solid #DCDFE6;
  border-bottom: none;
  border-radius: 8px 8px 0 0;
}

.rich-editor >>> .ql-container {
  border: 1px solid #DCDFE6;
  border-radius: 0 0 8px 8px;
}

.rich-editor >>> .ql-editor {
  min-height: 400px;
  padding: 12px 16px;
}

.edit-sidebar {
  width: 320px;
  overflow-y: auto;
  padding-right: 5px;
}

.sidebar-section {
  background: #fff;
  padding: 16px;
  border-radius: 8px;
  margin-bottom: 16px;
  border: 1px solid #e4e7ed;
}

.section-title {
  font-size: 14px;
  font-weight: 600;
  color: #303133;
  margin-bottom: 12px;
}

.full-width {
  width: 100%;
  margin-bottom: 8px;
}

.full-width:last-child {
  margin-bottom: 0;
}

.switch-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
}

.switch-item span {
  font-size: 14px;
  color: #606266;
}

.action-buttons .el-button {
  margin: 0 0 8px 0;
}

.action-buttons .el-button:last-child {
  margin-bottom: 0;
}

.preview-container {
  padding: 20px;
  max-height: 70vh;
  overflow-y: auto;
  line-height: 1.8;
}

/* 自定义 Markdown 编辑器样式 */
.v-note-wrapper {
  height: 100%;
  z-index: 1;
}

.v-note-wrapper >>> .v-note-edit {
  flex: 1;
}

.v-note-wrapper >>> .v-note-show {
  flex: 1;
}

/* 滚动条样式 */
.edit-sidebar::-webkit-scrollbar {
  width: 6px;
}

.edit-sidebar::-webkit-scrollbar-thumb {
  background: #dcdfe6;
  border-radius: 3px;
}

.edit-sidebar::-webkit-scrollbar-thumb:hover {
  background: #c0c4cc;
}

/* Element UI 组件样式调整 */
.sidebar-section >>> .el-select {
  width: 100%;
}

.sidebar-section >>> .el-textarea__inner {
  resize: none;
}

.sidebar-section >>> .el-radio-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.sidebar-section >>> .el-radio {
  margin-right: 0;
}
</style>
