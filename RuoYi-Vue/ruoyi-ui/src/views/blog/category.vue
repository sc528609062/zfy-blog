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
        <!-- 分类页面 -->
        <div class="category-page">
          <el-card>
            <h2 class="page-title">文章分类</h2>
            <div class="category-list">
              <div v-for="category in categories" :key="category.categoryId" class="category-item">
                <div class="category-icon">
                  <i class="el-icon-folder"></i>
                </div>
                <div class="category-info">
                  <h3 class="category-name">
                    <router-link :to="'/blog/category/' + category.categoryId">
                      {{ category.categoryName }}
                    </router-link>
                  </h3>
                  <p class="category-desc">{{ category.categoryDesc || '暂无描述' }}</p>
                  <span class="category-count">{{ category.articleCount }} 篇文章</span>
                </div>
              </div>
            </div>
            <el-empty v-if="categories.length === 0" description="暂无分类"></el-empty>
          </el-card>
        </div>
      </div>

      <!-- 右侧边栏 -->
      <aside class="blog-sidebar">
        <!-- 分类统计 -->
        <div class="sidebar-widget">
          <h3 class="widget-title">分类统计</h3>
          <div class="stats-info">
            <p>总分类数：<strong>{{ categories.length }}</strong></p>
            <p>总文章数：<strong>{{ totalArticles }}</strong></p>
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
import { listCategory, listArticle } from '@/api/blog'
import { getToken } from '@/utils/auth'
import { mapGetters } from 'vuex'

export default {
  name: 'BlogCategory',
  data() {
    return {
      blogTitle: 'zfy-blog',
      blogSubtitle: '基于 RuoYi-Vue 构建的博客系统',
      icpCode: '',
      activeNav: '/blog/category',
      categories: [],
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
    this.loadCategories()
    if (this.token) {
      this.loadUserInfo()
    }
  },
  methods: {
    // 加载分类列表
    loadCategories() {
      listCategory({ status: '0' }).then(response => {
        this.categories = response.rows
        this.totalArticles = this.categories.reduce((sum, cat) => sum + (cat.articleCount || 0), 0)
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

/* 分类页面 */
.category-page {
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

.category-list {
  display: grid;
  gap: 20px;
}

.category-item {
  display: flex;
  align-items: center;
  padding: 20px;
  border: 1px solid #eee;
  border-radius: 8px;
  transition: all 0.3s;
}

.category-item:hover {
  border-color: #409eff;
  box-shadow: 0 2px 8px rgba(64, 158, 255, 0.2);
  transform: translateY(-2px);
}

.category-icon {
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f0f7ff;
  border-radius: 50%;
  flex-shrink: 0;
}

.category-icon i {
  font-size: 28px;
  color: #409eff;
}

.category-info {
  flex: 1;
  margin-left: 20px;
}

.category-name {
  margin: 0 0 10px;
  font-size: 20px;
}

.category-name a {
  color: #333;
  text-decoration: none;
  transition: color 0.3s;
}

.category-name a:hover {
  color: #409eff;
}

.category-desc {
  margin: 0 0 10px;
  color: #999;
  font-size: 14px;
}

.category-count {
  display: inline-block;
  padding: 4px 12px;
  background: #f0f0f0;
  border-radius: 12px;
  font-size: 12px;
  color: #666;
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
}
</style>
