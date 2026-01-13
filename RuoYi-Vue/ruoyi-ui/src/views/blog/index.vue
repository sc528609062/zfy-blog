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
            <el-menu-item index="/">首页</el-menu-item>
            <el-menu-item index="/blog/category">分类</el-menu-item>
            <el-menu-item index="/blog/tag">标签</el-menu-item>
            <el-menu-item index="/blog/archives">归档</el-menu-item>
            <el-menu-item index="/blog/about">关于</el-menu-item>
          </el-menu>

          <!-- 用户操作区 -->
          <div class="user-actions">
            <!-- 未登录状态 -->
            <div v-if="!token" class="auth-buttons">
              <el-button type="primary" size="small" @click="goToLogin">登录</el-button>
              <el-button size="small" @click="goToRegister">注册</el-button>
            </div>
            <!-- 已登录状态 -->
            <div v-else class="user-info">
              <el-dropdown @command="handleCommand">
                <span class="el-dropdown-link">
                  <el-avatar :size="32" :src="userAvatar">
                    {{ userName.charAt(0) }}
                  </el-avatar>
                  <span class="username">{{ userName }}</span>
                  <i class="el-icon-arrow-down el-icon--right"></i>
                </span>
                <el-dropdown-menu slot="dropdown">
                  <el-dropdown-item command="userCenter">
                    <i class="el-icon-user"></i> 个人中心
                  </el-dropdown-item>
                  <el-dropdown-item v-if="isAdmin" command="adminPanel" divided>
                    <i class="el-icon-s-tools"></i> 进入后台
                  </el-dropdown-item>
                  <el-dropdown-item command="logout" divided>
                    <i class="el-icon-switch-button"></i> 退出登录
                  </el-dropdown-item>
                </el-dropdown-menu>
              </el-dropdown>
            </div>
          </div>
        </nav>
      </div>
    </header>

    <!-- 主体内容 -->
    <div class="blog-main">
      <div class="main-content">
        <!-- 左侧文章列表 -->
        <div class="article-list">
          <!-- 置顶文章 -->
          <div v-for="article in topArticles" :key="article.articleId" class="article-item top-article">
            <div class="article-cover" v-if="article.articleCover">
              <img :src="processImageUrl(article.articleCover)" :alt="article.articleTitle">
            </div>
            <div class="article-info">
              <div class="article-meta">
                <span class="meta-date">{{ formatDate(article.publishTime) }}</span>
                <span class="meta-category">{{ article.categoryName }}</span>
              </div>
              <h2 class="article-title">
                <router-link :to="'/blog/article/' + article.articleId">
                  <i class="el-icon-star-on"></i> {{ article.articleTitle }}
                </router-link>
              </h2>
              <p class="article-summary">{{ article.articleSummary }}</p>
              <div class="article-footer">
                <el-tag :type="getStatusType(article.articleStatus)" size="mini" class="article-status">
                  {{ getStatusText(article.articleStatus) }}
                </el-tag>
                <span class="meta-views"><i class="el-icon-view"></i> {{ article.viewCount }}</span>
                <span class="meta-comments"><i class="el-icon-chat-dot-round"></i> {{ article.commentCount }}</span>
                <span class="meta-likes"><i class="el-icon-thumb"></i> {{ article.likeCount }}</span>
              </div>
            </div>
          </div>

          <!-- 普通文章 -->
          <div v-for="article in articles" :key="article.articleId" class="article-item">
            <div class="article-cover" v-if="article.articleCover">
              <img :src="processImageUrl(article.articleCover)" :alt="article.articleTitle">
            </div>
            <div class="article-info">
              <div class="article-meta">
                <span class="meta-date">{{ formatDate(article.publishTime) }}</span>
                <span class="meta-category">{{ article.categoryName }}</span>
              </div>
              <h2 class="article-title">
                <router-link :to="'/blog/article/' + article.articleId">{{ article.articleTitle }}</router-link>
              </h2>
              <p class="article-summary">{{ article.articleSummary }}</p>
              <div class="article-footer">
                <el-tag :type="getStatusType(article.articleStatus)" size="mini" class="article-status">
                  {{ getStatusText(article.articleStatus) }}
                </el-tag>
                <span class="meta-views"><i class="el-icon-view"></i> {{ article.viewCount }}</span>
                <span class="meta-comments"><i class="el-icon-chat-dot-round"></i> {{ article.commentCount }}</span>
                <span class="meta-likes"><i class="el-icon-thumb"></i> {{ article.likeCount }}</span>
              </div>
            </div>
          </div>

          <!-- 分页 -->
          <el-pagination
            v-if="total > 0"
            background
            layout="prev, pager, next"
            :total="total"
            :page-size="pageSize"
            :current-page="pageNum"
            @current-change="handlePageChange">
          </el-pagination>
        </div>
      </div>

      <!-- 右侧边栏 -->
      <aside class="blog-sidebar">
        <!-- 个人信息 -->
        <div class="sidebar-widget widget-profile">
          <div class="profile-avatar">
            <img src="https://cube.elemecdn.com/0/88/03b0d39583f48206768a7534e55bcpng.png" alt="avatar">
          </div>
          <h3 class="profile-name">{{ authorName }}</h3>
          <p class="profile-desc">热爱技术，分享生活</p>
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

        <!-- 标签云 -->
        <div class="sidebar-widget">
          <h3 class="widget-title">标签云</h3>
          <div class="tag-cloud">
            <el-tag
              v-for="tag in (tags || [])"
              :key="tag.tagId"
              size="small"
              @click="goToTag(tag.tagId)"
              v-if="tag">
              {{ tag.tagName }}
            </el-tag>
          </div>
        </div>

        <!-- 友情链接 -->
        <div class="sidebar-widget">
          <h3 class="widget-title">友情链接</h3>
          <ul class="link-list">
            <li v-for="link in links" :key="link.linkId">
              <a :href="link.linkUrl" target="_blank">{{ link.linkName }}</a>
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
import { listArticle, listCategory, listTag, getConfig } from '@/api/blog'
import { getToken, removeToken } from '@/utils/auth'
import { mapGetters } from 'vuex'

export default {
  name: 'BlogIndex',
  data() {
    return {
      blogTitle: 'zfy-blog',
      blogSubtitle: '基于 RuoYi-Vue 构建的博客系统',
      icpCode: '',
      authorName: '博主',
      activeNav: '/',
      articles: [],
      topArticles: [],
      categories: [],
      tags: [],
      links: [],
      pageNum: 1,
      pageSize: 10,
      total: 0,
      token: getToken(),
      userName: '',
      userAvatar: ''
    }
  },
  computed: {
    ...mapGetters(['roles']),
    isAdmin() {
      return this.roles && this.roles.includes('admin')
    }
  },
  created() {
    this.loadBlogConfig()
    this.loadArticles()
    this.loadCategories()
    this.loadTags()
    if (this.token) {
      this.loadUserInfo()
    }
  },
  methods: {
    // 加载博客配置
    loadBlogConfig() {
      const configs = [
        { key: 'site_title', field: 'blogTitle' },
        { key: 'site_subtitle', field: 'blogSubtitle' },
        { key: 'icp_code', field: 'icpCode' }
      ]
      configs.forEach(config => {
        getConfig(config.key).then(response => {
          if (response.data && response.data.configValue) {
            this[config.field] = response.data.configValue
          }
        })
      })
    },
    // 加载用户信息
    loadUserInfo() {
      this.$store.dispatch('GetInfo').then(() => {
        this.userName = this.$store.state.user.name
        this.userAvatar = this.$store.state.user.avatar
      })
    },
    // 跳转登录
    goToLogin() {
      this.$router.push('/login?redirect=' + encodeURIComponent(this.$route.fullPath))
    },
    // 跳转注册
    goToRegister() {
      this.$router.push('/register')
    },
    // 处理下拉菜单命令
    handleCommand(command) {
      switch (command) {
        case 'userCenter':
          this.$router.push('/user')
          break
        case 'adminPanel':
          this.$router.push('/index')
          break
        case 'logout':
          this.$confirm('确定要退出登录吗？', '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
          }).then(() => {
            this.$store.dispatch('LogOut').then(() => {
              this.token = ''
              this.userName = ''
              this.userAvatar = ''
              this.$message.success('退出登录成功')
              this.$router.push('/')
            })
          }).catch(() => {})
          break
      }
    },
    // 加载文章列表
    loadArticles() {
      const query = {
        pageNum: this.pageNum,
        pageSize: this.pageSize,
        articleStatus: '1'
      }
      listArticle(query).then(response => {
        const allArticles = response.rows
        this.topArticles = allArticles.filter(item => item.isTop === '1')
        this.articles = allArticles.filter(item => item.isTop !== '1')
        this.total = response.total
      })
    },
    // 加载分类列表
    loadCategories() {
      listCategory({ status: '0' }).then(response => {
        this.categories = response.rows
      })
    },
    // 加载标签列表
    loadTags() {
      listTag({ status: '0' }).then(response => {
        this.tags = response.rows
      })
    },
    // 页码改变
    handlePageChange(pageNum) {
      this.pageNum = pageNum
      this.loadArticles()
    },
    // 跳转到标签
    goToTag(tagId) {
      this.$router.push('/blog/tag/' + tagId)
    },
    // 格式化日期
    formatDate(date) {
      if (!date) return ''
      return this.parseTime(date, '{y}-{m}-{d}')
    },
    // 获取状态文本
    getStatusText(status) {
      const statusMap = {
        '0': '草稿',
        '1': '已发布',
        '2': '已下架'
      }
      return statusMap[status] || '未知'
    },
    // 获取状态标签类型
    getStatusType(status) {
      const typeMap = {
        '0': 'info',
        '1': 'success',
        '2': 'danger'
      }
      return typeMap[status] || 'info'
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
/* Spimes主题风格 - 简洁优雅 */
.blog-container {
  min-height: 100vh;
  background: #f8f9fa;
}

/* 头部导航 */
.blog-header {
  background: #fff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  position: sticky;
  top: 0;
  z-index: 100;
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
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.blog-nav .el-menu {
  flex: 1;
}

/* 用户操作区 */
.user-actions {
  margin-left: 20px;
}

.auth-buttons {
  display: flex;
  gap: 10px;
}

.user-info {
  display: flex;
  align-items: center;
}

.el-dropdown-link {
  display: flex;
  align-items: center;
  cursor: pointer;
  color: #303133;
  transition: color 0.3s;
}

.el-dropdown-link:hover {
  color: #409eff;
}

.username {
  margin: 0 8px;
  font-size: 14px;
}

/* 主体内容 */
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

/* 文章列表 */
.article-item {
  background: #fff;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  display: flex;
  gap: 20px;
  transition: all 0.3s;
}

.article-item:hover {
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.article-item.top-article {
  border-left: 4px solid #409eff;
}

.article-cover {
  width: 200px;
  flex-shrink: 0;
}

.article-cover img {
  width: 100%;
  height: 150px;
  object-fit: cover;
  border-radius: 6px;
}

.article-info {
  flex: 1;
}

.article-meta {
  margin-bottom: 10px;
  color: #999;
  font-size: 13px;
}

.article-meta span {
  margin-right: 15px;
}

.article-title {
  margin: 10px 0;
  font-size: 20px;
}

.article-title a {
  color: #333;
  text-decoration: none;
  transition: color 0.3s;
}

.article-title a:hover {
  color: #409eff;
}

.article-summary {
  color: #666;
  font-size: 14px;
  line-height: 1.6;
  margin: 10px 0;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.article-footer {
  color: #999;
  font-size: 13px;
}

.article-footer .article-status {
  margin-right: 15px;
}

.article-footer span {
  margin-right: 20px;
}

.article-footer i {
  margin-right: 5px;
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

/* 个人信息 */
.widget-profile {
  text-align: center;
}

.profile-avatar img {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  object-fit: cover;
  margin-bottom: 15px;
}

.profile-name {
  margin: 0 0 5px;
  font-size: 18px;
  color: #333;
}

.profile-desc {
  margin: 0;
  font-size: 14px;
  color: #999;
}

/* 分类列表 */
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

/* 标签云 */
.tag-cloud {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.tag-cloud .el-tag {
  cursor: pointer;
  transition: all 0.3s;
}

.tag-cloud .el-tag:hover {
  transform: scale(1.05);
}

/* 友情链接 */
.link-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.link-list li {
  margin-bottom: 10px;
}

.link-list a {
  color: #666;
  text-decoration: none;
  transition: color 0.3s;
}

.link-list a:hover {
  color: #409eff;
}

/* 分页 */
.el-pagination {
  text-align: center;
  margin-top: 30px;
}

/* 页脚 */
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

  .article-item {
    flex-direction: column;
  }

  .article-cover {
    width: 100%;
  }

  .article-cover img {
    height: 200px;
  }
}
</style>
