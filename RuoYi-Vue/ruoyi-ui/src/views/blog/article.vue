<template>
  <div class="blog-container">
    <!-- 头部导航 -->
    <header class="blog-header">
      <div class="header-inner">
        <div class="blog-title">
          <h1>{{ blogTitle }}</h1>
          <p class="blog-subtitle">{{ blogSubtitle }}</p>
        </div>
        <nav class="blog-nav">
          <el-menu :default-active="activeNav" mode="horizontal" router>
            <el-menu-item index="/blog">首页</el-menu-item>
            <el-menu-item index="/blog/category">分类</el-menu-item>
            <el-menu-item index="/blog/tag">标签</el-menu-item>
            <el-menu-item index="/blog/archives">归档</el-menu-item>
            <el-menu-item index="/blog/about">关于</el-menu-item>
          </el-menu>
        </nav>
      </div>
    </header>

    <!-- 主体内容 -->
    <div class="blog-main">
      <div class="main-content">
        <!-- 文章详情 -->
        <div v-loading="loading" class="article-detail">
          <article v-if="article" class="article">
            <!-- 文章头部 -->
            <header class="article-header">
              <h1 class="article-title">{{ article.articleTitle }}</h1>
              <div class="article-meta">
                <span class="meta-author"><i class="el-icon-user"></i> {{ article.authorName }}</span>
                <span class="meta-date"><i class="el-icon-time"></i> {{ formatDate(article.publishTime) }}</span>
                <span class="meta-category"><i class="el-icon-folder"></i> {{ article.categoryName }}</span>
                <span class="meta-views"><i class="el-icon-view"></i> {{ article.viewCount }}</span>
                <span class="meta-comments"><i class="el-icon-chat-dot-round"></i> {{ article.commentCount }}</span>
              </div>
            </header>

            <!-- 文章封面 -->
            <div v-if="article.articleCover" class="article-cover">
              <img :src="processImageUrl(article.articleCover)" :alt="article.articleTitle">
            </div>

            <!-- 文章内容 -->
            <mavon-editor
              class="article-content"
              :value="article.articleContent"
              :toolbars="{}"
              :subfield="false"
              :boxShadow="false"
              :preview="true"
              defaultOpen="preview"
              :editable="false"
              :scrollStyle="true"
              :ishljs="true"
            />

            <!-- 文章底部 -->
            <footer class="article-footer">
              <div class="article-tags">
                <span>标签：</span>
                <el-tag
                  v-for="tag in (article.tags || [])"
                  :key="tag.tagId"
                  size="small"
                  @click="goToTag(tag.tagId)"
                  v-if="tag">
                  {{ tag.tagName }}
                </el-tag>
                <span v-if="!article.tags || article.tags.length === 0 || !article.tags.find(t => t)">暂无标签</span>
              </div>
              <div class="article-actions">
                <el-button :type="isLiked ? 'success' : 'primary'" :icon="isLiked ? 'el-icon-thumb' : 'el-icon-thumb'" @click="likeArticle">
                  {{ isLiked ? '已点赞' : '点赞' }} {{ article.likeCount || 0 }}
                </el-button>
                <el-button :type="isFavorited ? 'warning' : 'default'" :icon="isFavorited ? 'el-icon-star-on' : 'el-icon-star-off'" @click="favoriteArticle">
                  {{ isFavorited ? '已收藏' : '收藏' }} {{ article.favoriteCount || 0 }}
                </el-button>
                <el-button icon="el-icon-share" @click="shareArticle">分享</el-button>
              </div>
            </footer>

            <!-- 上一篇/下一篇 -->
            <div class="article-nav">
              <div v-if="prevArticle" class="nav-item prev">
                <router-link :to="'/blog/article/' + prevArticle.articleId">
                  <span>&lt; 上一篇</span>
                  {{ prevArticle.articleTitle }}
                </router-link>
              </div>
              <div v-if="nextArticle" class="nav-item next">
                <router-link :to="'/blog/article/' + nextArticle.articleId">
                  <span>下一篇 &gt;</span>
                  {{ nextArticle.articleTitle }}
                </router-link>
              </div>
            </div>

            <!-- 评论区 -->
            <div class="article-comments">
              <h3>评论 ({{ comments.length }})</h3>

              <!-- 评论表单 -->
              <div class="comment-form">
                <el-form :model="commentForm" ref="commentForm" :rules="commentRules">
                  <el-form-item prop="content">
                    <el-input
                      type="textarea"
                      v-model="commentForm.content"
                      :rows="4"
                      placeholder="发表你的评论...">
                    </el-input>
                  </el-form-item>
                  <el-form-item>
                    <el-button type="primary" @click="submitComment">提交评论</el-button>
                  </el-form-item>
                </el-form>
              </div>

              <!-- 评论列表 -->
              <div class="comment-list">
                <div v-for="comment in comments" :key="comment.commentId" class="comment-item">
                  <div class="comment-avatar">
                    <img :src="comment.userAvatar || defaultAvatar" :alt="comment.userName">
                  </div>
                  <div class="comment-content">
                    <div class="comment-header">
                      <span class="comment-author">{{ comment.userName }}</span>
                      <span class="comment-time">{{ formatDate(comment.createTime) }}</span>
                    </div>
                    <div class="comment-text">{{ comment.commentContent }}</div>
                    <div class="comment-actions">
                      <span @click="replyComment(comment)">回复</span>
                      <span @click="likeComment(comment)">
                        <i class="el-icon-thumb"></i> {{ comment.likeCount }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </article>
        </div>
      </div>

      <!-- 右侧边栏 -->
      <aside class="blog-sidebar">
        <!-- 相关文章 -->
        <div class="sidebar-widget">
          <h3 class="widget-title">相关文章</h3>
          <ul class="related-list">
            <li v-for="item in relatedArticles" :key="item.articleId">
              <router-link :to="'/blog/article/' + item.articleId">{{ item.articleTitle }}</router-link>
            </li>
          </ul>
        </div>

        <!-- 分类 -->
        <div class="sidebar-widget">
          <h3 class="widget-title">分类</h3>
          <ul class="category-list">
            <li v-for="category in categories" :key="category.categoryId">
              <router-link :to="'/blog/category/' + category.categoryId">
                {{ category.categoryName }} <span>{{ category.articleCount }}</span>
              </router-link>
            </li>
          </ul>
        </div>
      </aside>
    </div>

    <!-- 页脚 -->
    <footer class="blog-footer">
      <p>&copy; {{ new Date().getFullYear() }} {{ blogTitle }}. All rights reserved.</p>
      <p v-if="icpCode">备案号：{{ icpCode }}</p>
    </footer>
  </div>
</template>

<script>
import { getArticle, listArticle, listCategory, incrementView, likeArticle, unlikeArticle, checkLiked, favoriteArticle, unfavoriteArticle, checkFavorited } from '@/api/blog'
import { mavonEditor } from 'mavon-editor'
import 'mavon-editor/dist/css/index.css'

export default {
  components: {
    mavonEditor
  },
  name: 'BlogArticle',
  data() {
    return {
      blogTitle: 'zfy-blog',
      blogSubtitle: '基于 RuoYi-Vue 构建的博客系统',
      icpCode: '',
      activeNav: '/blog',
      loading: true,
      article: null,
      prevArticle: null,
      nextArticle: null,
      relatedArticles: [],
      categories: [],
      comments: [],
      defaultAvatar: 'https://cube.elemecdn.com/0/88/03b0d39583f48206768a7534e55bcpng.png',
      commentForm: {
        content: ''
      },
      commentRules: {
        content: [
          { required: true, message: '请输入评论内容', trigger: 'blur' },
          { min: 5, message: '评论内容至少5个字符', trigger: 'blur' }
        ]
      },
      isLiked: false,
      isFavorited: false
    }
  },
  created() {
    this.loadArticle()
    this.loadCategories()
  },
  watch: {
    '$route': 'loadArticle'
  },
  methods: {
    // 加载文章详情
    loadArticle() {
      const articleId = this.$route.params.id
      this.loading = true
      getArticle(articleId).then(response => {
        this.article = response.data
        // 处理文章内容中的图片路径，添加 /dev-api 前缀
        if (this.article.articleContent) {
          this.article.articleContent = this.processImageUrls(this.article.articleContent)
        }
        this.loading = false
        // 增加浏览量
        incrementView(articleId)
        // 检查点赞和收藏状态
        this.checkLikeAndFavorite(articleId)
        // 加载相关文章
        this.loadRelatedArticles()
      }).catch(() => {
        this.loading = false
      })
    },
    // 加载相关文章
    loadRelatedArticles() {
      const query = {
        pageNum: 1,
        pageSize: 5,
        articleStatus: '1',
        categoryId: this.article.categoryId,
        excludeId: this.article.articleId
      }
      listArticle(query).then(response => {
        this.relatedArticles = response.rows
      })
    },
    // 加载分类列表
    loadCategories() {
      listCategory({ status: '0' }).then(response => {
        this.categories = response.rows
      })
    },
    // 点赞文章
    likeArticle() {
      const articleId = this.article.articleId
      if (this.isLiked) {
        unlikeArticle(articleId).then(() => {
          this.isLiked = false
          this.article.likeCount--
          this.$message.success('取消点赞成功！')
        }).catch(() => {
          this.$message.error('操作失败')
        })
      } else {
        likeArticle(articleId).then(() => {
          this.isLiked = true
          this.article.likeCount++
          this.$message.success('点赞成功！')
        }).catch(() => {
          this.$message.error('操作失败')
        })
      }
    },
    // 收藏文章
    favoriteArticle() {
      const articleId = this.article.articleId
      if (this.isFavorited) {
        unfavoriteArticle(articleId).then(() => {
          this.isFavorited = false
          this.article.favoriteCount--
          this.$message.success('取消收藏成功！')
        }).catch(() => {
          this.$message.error('操作失败')
        })
      } else {
        favoriteArticle(articleId).then(() => {
          this.isFavorited = true
          this.article.favoriteCount++
          this.$message.success('收藏成功！')
        }).catch(() => {
          this.$message.error('操作失败')
        })
      }
    },
    // 分享文章
    shareArticle() {
      if (navigator.share) {
        navigator.share({
          title: this.article.articleTitle,
          url: window.location.href
        })
      } else {
        this.$message.info('请手动复制链接分享')
      }
    },
    // 提交评论
    submitComment() {
      this.$refs.commentForm.validate(valid => {
        if (valid) {
          // TODO: 调用评论接口
          this.$message.success('评论提交成功，等待审核')
          this.commentForm.content = ''
        }
      })
    },
    // 回复评论
    replyComment(comment) {
      this.commentForm.content = `@${comment.userName} `
      this.$refs.commentForm.focus()
    },
    // 点赞评论
    likeComment(comment) {
      comment.likeCount++
      this.$message.success('点赞成功！')
    },
    // 检查点赞和收藏状态
    checkLikeAndFavorite(articleId) {
      checkLiked(articleId).then(response => {
        this.isLiked = response.data.liked
      }).catch(() => {
        this.isLiked = false
      })
      checkFavorited(articleId).then(response => {
        this.isFavorited = response.data.favorited
      }).catch(() => {
        this.isFavorited = false
      })
    },
    // 跳转到标签
    goToTag(tagId) {
      this.$router.push('/blog/tag/' + tagId)
    },
    // 格式化日期
    formatDate(date) {
      if (!date) return ''
      return this.parseTime(date, '{y}-{m}-{d} {h}:{i}')
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
    },
    // 处理图片URL，添加 /dev-api 前缀
    processImageUrl(url) {
      if (!url) return url
      // 如果路径以 /profile/ 开头，添加 /dev-api 前缀
      if (url.startsWith('/profile/')) {
        return '/dev-api' + url
      }
      return url
    }
  }
}
</script>

<style scoped>
/* 复用首页样式 */
.blog-container {
  min-height: 100vh;
  background: #f8f9fa;
}

.blog-header {
  background: #fff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  position: sticky;
  top: 0;
  z-index: 1000;
}

.header-inner {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.blog-title {
  padding: 20px 0;
}

.blog-title h1 {
  margin: 0;
  font-size: 28px;
  color: #333;
}

.blog-subtitle {
  margin: 5px 0 0;
  font-size: 14px;
  color: #999;
}

.blog-nav {
  flex: 1;
  margin-left: 60px;
}

.blog-main {
  max-width: 1200px;
  margin: 30px auto;
  padding: 0 20px;
  display: flex;
  gap: 30px;
}

.main-content {
  flex: 1;
}

/* 文章详情 */
.article-detail {
  background: #fff;
  border-radius: 8px;
  padding: 30px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.article-header {
  text-align: center;
  margin-bottom: 30px;
  padding-bottom: 20px;
  border-bottom: 1px solid #eee;
}

.article-title {
  margin: 0 0 20px;
  font-size: 32px;
  color: #333;
}

.article-meta {
  color: #999;
  font-size: 14px;
}

.article-meta span {
  margin: 0 15px;
}

.article-meta i {
  margin-right: 5px;
}

.article-cover {
  margin: 30px 0;
}

.article-cover img {
  width: 100%;
  max-height: 500px;
  object-fit: cover;
  border-radius: 8px;
}

.article-content {
  line-height: 1.8;
  font-size: 16px;
  color: #333;
  margin: 30px 0;
  border: none;
  box-shadow: none;
  position: relative;
  z-index: 1;
}

.article-content >>> .v-note-wrapper {
  box-shadow: none !important;
  border: none !important;
  position: relative !important;
  z-index: 1 !important;
}

.article-content >>> .v-note-edit {
  display: none;
}

.article-content >>> .v-note-show {
  width: 100%;
  background: transparent;
  padding: 0;
  position: relative;
}

.article-content >>> .v-note-show .v-show-content {
  padding: 0 !important;
  background: transparent !important;
}

/* 确保编辑器内部没有固定定位的元素 */
.article-content >>> * {
  position: relative !important;
}

.article-content >>> img {
  max-width: 100%;
  border-radius: 4px;
}

.article-content >>> pre {
  background: #f5f5f5;
  padding: 15px;
  border-radius: 4px;
  overflow-x: auto;
}

.article-footer {
  margin: 30px 0;
  padding: 20px 0;
  border-top: 1px solid #eee;
  border-bottom: 1px solid #eee;
}

.article-tags {
  margin-bottom: 20px;
}

.article-tags span {
  color: #999;
  margin-right: 10px;
}

.article-tags .el-tag {
  margin-right: 10px;
  cursor: pointer;
}

.article-actions {
  display: flex;
  gap: 10px;
}

/* 上一篇/下一篇 */
.article-nav {
  margin: 30px 0;
  display: flex;
  justify-content: space-between;
}

.nav-item {
  flex: 1;
  padding: 15px;
  background: #f9f9f9;
  border-radius: 6px;
}

.nav-item span {
  display: block;
  color: #999;
  font-size: 13px;
  margin-bottom: 5px;
}

.nav-item a {
  color: #333;
  text-decoration: none;
  transition: color 0.3s;
}

.nav-item a:hover {
  color: #409eff;
}

/* 评论区 */
.article-comments {
  margin-top: 40px;
}

.article-comments h3 {
  margin: 0 0 20px;
  font-size: 24px;
  color: #333;
}

.comment-form {
  margin-bottom: 30px;
  padding: 20px;
  background: #f9f9f9;
  border-radius: 8px;
}

.comment-list {
  margin-top: 20px;
}

.comment-item {
  display: flex;
  gap: 15px;
  padding: 20px 0;
  border-bottom: 1px solid #eee;
}

.comment-avatar img {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  object-fit: cover;
}

.comment-content {
  flex: 1;
}

.comment-header {
  margin-bottom: 10px;
}

.comment-author {
  font-weight: bold;
  color: #333;
  margin-right: 15px;
}

.comment-time {
  color: #999;
  font-size: 13px;
}

.comment-text {
  line-height: 1.6;
  color: #666;
  margin-bottom: 10px;
}

.comment-actions {
  color: #999;
  font-size: 13px;
}

.comment-actions span {
  margin-right: 20px;
  cursor: pointer;
  transition: color 0.3s;
}

.comment-actions span:hover {
  color: #409eff;
}

/* 侧边栏 */
.blog-sidebar {
  width: 300px;
  flex-shrink: 0;
}

.sidebar-widget {
  background: #fff;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.widget-title {
  margin: 0 0 15px;
  padding-bottom: 10px;
  border-bottom: 2px solid #409eff;
  font-size: 18px;
  color: #333;
}

.related-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.related-list li {
  margin-bottom: 10px;
  padding-left: 15px;
  position: relative;
}

.related-list li:before {
  content: '';
  position: absolute;
  left: 0;
  top: 8px;
  width: 6px;
  height: 6px;
  background: #409eff;
  border-radius: 50%;
}

.related-list a {
  color: #666;
  text-decoration: none;
  transition: color 0.3s;
  display: block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.related-list a:hover {
  color: #409eff;
}

.category-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.category-list li {
  margin-bottom: 10px;
}

.category-list a {
  display: flex;
  justify-content: space-between;
  color: #666;
  text-decoration: none;
  transition: color 0.3s;
}

.category-list a:hover {
  color: #409eff;
}

.category-list span {
  background: #f0f0f0;
  padding: 2px 8px;
  border-radius: 10px;
  font-size: 12px;
}

.blog-footer {
  background: #333;
  color: #fff;
  text-align: center;
  padding: 30px 20px;
  margin-top: 50px;
}

.blog-footer p {
  margin: 5px 0;
  font-size: 14px;
}

/* 响应式 */
@media (max-width: 768px) {
  .blog-main {
    flex-direction: column;
  }

  .blog-sidebar {
    width: 100%;
  }

  .article-detail {
    padding: 20px;
  }

  .article-title {
    font-size: 24px;
  }

  .article-nav {
    flex-direction: column;
    gap: 15px;
  }
}
</style>
