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
        <!-- 归档页面 -->
        <div class="archives-page">
          <el-card>
            <h2 class="page-title">文章归档</h2>
            <div class="archives-list">
              <div v-for="(group, year) in archives" :key="year" class="archive-year">
                <h3 class="year-title">
                  <i class="el-icon-date"></i> {{ year }}年
                  <span class="year-count">{{ group.length }} 篇</span>
                </h3>
                <div class="month-list">
                  <div v-for="(item, index) in group" :key="index" class="archive-item">
                    <div class="article-date">
                      {{ formatDate(item.publishTime, '{m}-{d}') }}
                    </div>
                    <div class="article-info">
                      <h4 class="article-title">
                        <router-link :to="'/blog/article/' + item.articleId">
                          {{ item.articleTitle }}
                        </router-link>
                      </h4>
                      <div class="article-meta">
                        <el-tag :type="getStatusType(item.articleStatus)" size="mini" class="article-status">
                          {{ getStatusText(item.articleStatus) }}
                        </el-tag>
                        <span><i class="el-icon-view"></i> {{ item.viewCount }}</span>
                        <span><i class="el-icon-chat-dot-round"></i> {{ item.commentCount }}</span>
                        <span><i class="el-icon-folder"></i> {{ item.categoryName }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <el-empty v-if="Object.keys(archives).length === 0" description="暂无归档"></el-empty>
          </el-card>
        </div>
      </div>

      <!-- 右侧边栏 -->
      <aside class="blog-sidebar">
        <!-- 归档统计 -->
        <div class="sidebar-widget">
          <h3 class="widget-title">归档统计</h3>
          <div class="stats-info">
            <p>总文章数：<strong>{{ totalArticles }}</strong></p>
            <p>年份跨度：<strong>{{ getYearSpan() }}</strong></p>
          </div>
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
import { listArticle } from '@/api/blog'
import { getToken } from '@/utils/auth'
import { mapGetters } from 'vuex'

export default {
  name: 'BlogArchives',
  data() {
    return {
      blogTitle: 'zfy-blog',
      blogSubtitle: '基于 RuoYi-Vue 构建的博客系统',
      icpCode: '',
      activeNav: '/blog/archives',
      articles: [],
      archives: {},
      totalArticles: 0,
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
    this.loadArticles()
    if (this.token) {
      this.loadUserInfo()
    }
  },
  methods: {
    // 加载文章列表
    loadArticles() {
      listArticle({
        pageNum: 1,
        pageSize: 1000,
        articleStatus: '1'
      }).then(response => {
        this.articles = response.rows
        this.totalArticles = response.total
        this.groupByYear()
      })
    },
    // 按年份分组
    groupByYear() {
      this.archives = {}
      this.articles.forEach(article => {
        if (article.publishTime) {
          const year = new Date(article.publishTime).getFullYear()
          if (!this.archives[year]) {
            this.archives[year] = []
          }
          this.archives[year].push(article)
        }
      })
      // 按年份降序排序
      const years = Object.keys(this.archives).sort((a, b) => b - a)
      const sortedArchives = {}
      years.forEach(year => {
        sortedArchives[year] = this.archives[year].sort((a, b) => {
          return new Date(b.publishTime) - new Date(a.publishTime)
        })
      })
      this.archives = sortedArchives
    },
    // 获取年份跨度
    getYearSpan() {
      const years = Object.keys(this.archives).sort((a, b) => a - b)
      if (years.length === 0) return '无'
      if (years.length === 1) return years[0] + '年'
      return years[0] + ' - ' + years[years.length - 1]
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
    // 格式化日期
    formatDate(date, format) {
      if (!date) return ''
      return this.parseTime(date, format)
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
    }
  }
}
</script>

<style scoped>
/* 复用博客首页样式 */
.blog-container {
  min-height: 100vh;
  background: #f8f9fa;
}

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

/* 归档页面 */
.archives-page {
  background: #fff;
  border-radius: 8px;
  padding: 30px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.page-title {
  margin: 0 0 30px;
  font-size: 28px;
  color: #333;
  border-bottom: 2px solid #409eff;
  padding-bottom: 10px;
}

.archive-year {
  margin-bottom: 30px;
}

.year-title {
  margin: 0 0 20px;
  font-size: 22px;
  color: #333;
  padding-bottom: 10px;
  border-bottom: 2px solid #f0f0f0;
  display: flex;
  align-items: center;
}

.year-title i {
  margin-right: 10px;
  color: #409eff;
}

.year-count {
  margin-left: 10px;
  font-size: 14px;
  color: #999;
  font-weight: normal;
}

.month-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.archive-item {
  display: flex;
  align-items: flex-start;
  padding: 15px;
  border-radius: 6px;
  transition: all 0.3s;
}

.archive-item:hover {
  background: #f9f9f9;
}

.article-date {
  width: 80px;
  flex-shrink: 0;
  color: #409eff;
  font-size: 14px;
  padding-top: 3px;
}

.article-info {
  flex: 1;
}

.article-title {
  margin: 0 0 8px;
  font-size: 16px;
}

.article-title a {
  color: #333;
  text-decoration: none;
  transition: color 0.3s;
}

.article-title a:hover {
  color: #409eff;
}

.article-meta {
  display: flex;
  align-items: center;
  gap: 15px;
  font-size: 13px;
  color: #999;
}

.article-meta .article-status {
  margin-right: 10px;
}

.article-meta i {
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

.stats-info p {
  margin: 10px 0;
  color: #666;
}

.stats-info strong {
  color: #409eff;
  font-size: 18px;
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

  .archive-item {
    flex-direction: column;
  }

  .article-date {
    margin-bottom: 10px;
  }
}
</style>
