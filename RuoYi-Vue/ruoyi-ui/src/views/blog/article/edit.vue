<template>
  <div class="app-container article-edit">
    <el-form ref="form" :model="form" :rules="rules" label-width="100px">
      <el-row>
        <el-col :span="24">
          <el-form-item label="文章标题" prop="articleTitle">
            <el-input v-model="form.articleTitle" placeholder="请输入文章标题" maxlength="200" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row>
        <el-col :span="12">
          <el-form-item label="文章分类" prop="categoryId">
            <el-select v-model="form.categoryId" placeholder="请选择分类" clearable style="width: 100%">
              <el-option
                v-for="item in categoryList"
                :key="item.categoryId"
                :label="item.categoryName"
                :value="item.categoryId"
              />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <el-form-item label="文章标签" prop="tagIds">
            <el-select v-model="form.tagIds" multiple placeholder="请选择标签" clearable style="width: 100%">
              <el-option
                v-for="item in tagList"
                :key="item.tagId"
                :label="item.tagName"
                :value="item.tagId"
              />
            </el-select>
          </el-form-item>
        </el-col>
      </el-row>

      <el-row>
        <el-col :span="12">
          <el-form-item label="是否置顶" prop="isTop">
            <el-switch v-model="form.isTop" active-value="1" inactive-value="0"></el-switch>
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <el-form-item label="是否推荐" prop="isRecommend">
            <el-switch v-model="form.isRecommend" active-value="1" inactive-value="0"></el-switch>
          </el-form-item>
        </el-col>
      </el-row>

      <el-row>
        <el-col :span="12">
          <el-form-item label="文章状态" prop="articleStatus">
            <el-radio-group v-model="form.articleStatus">
              <el-radio label="0">草稿</el-radio>
              <el-radio label="1">发布</el-radio>
              <el-radio label="2">下架</el-radio>
            </el-radio-group>
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <el-form-item label="是否原创" prop="isOriginal">
            <el-radio-group v-model="form.isOriginal">
              <el-radio label="1">原创</el-radio>
              <el-radio label="0">转载</el-radio>
            </el-radio-group>
          </el-form-item>
        </el-col>
      </el-row>

      <el-row v-if="form.isOriginal === '0'">
        <el-col :span="24">
          <el-form-item label="转载来源" prop="sourceUrl">
            <el-input v-model="form.sourceUrl" placeholder="请输入转载来源URL" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row>
        <el-col :span="24">
          <el-form-item label="文章摘要" prop="articleSummary">
            <el-input
              v-model="form.articleSummary"
              type="textarea"
              :rows="3"
              placeholder="请输入文章摘要"
              maxlength="500"
            />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row>
        <el-col :span="24">
          <el-form-item label="编辑器类型" prop="editorType">
            <el-radio-group v-model="editorType" @change="handleEditorChange">
              <el-radio label="markdown">
                <i class="el-icon-document"></i> Markdown 编辑器
              </el-radio>
              <el-radio label="rich">
                <i class="el-icon-edit"></i> 富文本编辑器
              </el-radio>
            </el-radio-group>
          </el-form-item>
        </el-col>
      </el-row>

      <el-row>
        <el-col :span="24">
          <el-form-item label="文章内容" prop="articleContent">
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
                class="rich-editor"
              />
            </div>
          </el-form-item>
        </el-col>
      </el-row>

      <el-row>
        <el-col :span="24">
          <el-form-item label="封面图片" prop="articleCover">
            <image-upload v-model="form.articleCover" :limit="1" />
          </el-form-item>
        </el-col>
      </el-row>
    </el-form>

    <div class="form-footer">
      <el-button type="primary" @click="submitForm" v-hasPermi="['blog:article:add', 'blog:article:edit']">
        {{ form.articleId ? '保存修改' : '立即发布' }}
      </el-button>
      <el-button type="info" @click="saveDraft" v-hasPermi="['blog:article:add']" v-if="!form.articleId">
        保存草稿
      </el-button>
      <el-button type="success" @click="preview" v-if="form.articleContent">
        预览
      </el-button>
      <el-button @click="cancel">取消</el-button>
    </div>

    <!-- 预览对话框 -->
    <el-dialog title="文章预览" :visible.sync="previewVisible" width="80%" top="5vh">
      <div v-if="editorType === 'markdown'" class="preview-container" v-html="renderedHtml"></div>
      <div v-else class="preview-container" v-html="form.articleContent"></div>
    </el-dialog>
  </div>
</template>

<script>
import { getArticle, addArticle, updateArticle } from '@/api/blog'
import { mavonEditor } from 'mavon-editor'
import 'mavon-editor/dist/css/index.css'

export default {
  name: 'BlogArticleEdit',
  components: {
    mavonEditor
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
      rules: {
        articleTitle: [
          { required: true, message: '文章标题不能为空', trigger: 'blur' }
        ],
        categoryId: [
          { required: true, message: '请选择文章分类', trigger: 'change' }
        ],
        articleContent: [
          { required: true, message: '文章内容不能为空', trigger: 'blur' }
        ]
      },
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
      // TODO: 调用分类列表接口
      // 示例数据
      this.categoryList = [
        { categoryId: 1, categoryName: '技术分享' },
        { categoryId: 2, categoryName: '生活随笔' },
        { categoryId: 3, categoryName: '学习笔记' }
      ]
    },
    getTagList() {
      // TODO: 调用标签列表接口
      // 示例数据
      this.tagList = [
        { tagId: 1, tagName: 'Java' },
        { tagId: 2, tagName: 'Vue' },
        { tagId: 3, tagName: 'Spring Boot' }
      ]
    },
    // Markdown 编辑器图片上传
    handleImgAdd(pos, $file) {
      // TODO: 实现图片上传
      // 第一步.将图片上传到服务器.
      const formdata = new FormData()
      formdata.append('file', $file)
      // 这里应该调用上传接口
      // uploadApi(formdata).then(url => {
      //   this.$refs.mdEditor.$img2Url(pos, url)
      // })
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
      this.$refs.form.validate(valid => {
        if (valid) {
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
        }
      })
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
      // 匹配 /profile/upload/ 开头的路径，替换为 /dev-api/profile/upload/
      return content.replace(/src="\/profile\/upload\//g, 'src="/dev-api/profile/upload/')
    }
  }
}
</script>

<style scoped>
.article-edit {
  padding: 20px;
}

.editor-container {
  border: 1px solid #DCDFE6;
  border-radius: 4px;
  overflow: hidden;
}

.markdown-editor {
  min-height: 600px;
}

.rich-editor {
  min-height: 600px;
}

.form-footer {
  margin-top: 30px;
  text-align: center;
}

.form-footer .el-button {
  margin: 0 10px;
}

.preview-container {
  padding: 20px;
  max-height: 70vh;
  overflow-y: auto;
  line-height: 1.8;
}

/* 自定义 Markdown 编辑器样式 */
.v-note-wrapper {
  z-index: 1;
}

/* 隐藏编辑器类型选择中的图标 */
.el-radio i {
  margin-right: 5px;
}
</style>
