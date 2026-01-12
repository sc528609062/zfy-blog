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
        <!-- 标签云 -->
        <div class="tag-page">
          <el-card>
            <h2 class="page-title">文章标签</h2>
            <div class="tag-cloud">
              <el-tag
                v-for="tag in tags"
                :key="tag.tagId"
                :size="getTagSize(tag.articleCount)"
                :type="getTagType(tag.articleCount)"
                @click="goToArticles(tag.tagId)"
                class="tag-item">
                {{ tag.tagName }}
                <span class="tag-count">({{ tag.articleCount }})</span>
              </el-tag>
            </div>
            <el-empty v-if="tags.length === 0" description="暂无标签"></el-empty>
          </el-card>
        </div>
      </div>

      <!-- 右侧边栏 -->
      <aside class="blog-sidebar">
        <!-- 标签统计 -->
        <div class="sidebar-widget">
          <h3 class="widget-title">标签统计</h3>
          <div class="stats-info">
            <p>总标签数：<strong>{{ tags.length }}</strong></p>
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
import { listTag } from '@/api/blog'
import { getToken } from '@/utils/auth'
import { mapGetters } from 'vuex'

export default {
  name: 'BlogTag',
  data() {
    return {
      blogTitle: 'zfy-blog',
      blogSubtitle: '基于 RuoYi-Vue 构建的博客系统',
      icpCode: '',
      activeNav: '/blog/tag',
      tags: [],
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
    this.loadTags()
    if (this.token) {
      this.loadUserInfo()
    }
  },
  methods: {
    // 加载标签列表
    loadTags() {
      listTag({ status: '0' }).then(response => {
        this.tags = response.rows
        this.totalArticles = this.tags.reduce((sum, tag) => sum + (tag.articleCount || 0), 0)
      })
    },
    // 加载用户信息
    loadUserInfo() {
      this.$store.dispatch('GetInfo').then(() => {
        this.userName = this.$store.state.user.name
        this.userAvatar = this.$store.state.user.avatar
      })
    },
    // 根据文章数获取标签大小
    getTagSize(count) {
      if (count >= 10) return 'large'
      if (count >= 5) return 'medium'
      return 'small'
    },
    // 根据文章数获取标签颜色
    getTagType(count) {
      if (count >= 10) return 'danger'
      if (count >= 5) return 'warning'
      if (count >= 3) return 'success'
      return 'info'
    },
    // 跳转到该标签的文章列表
    goToArticles(tagId) {
      this.$router.push('/blog/tag/' + tagId)
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

/* 标签页面 */
.tag-page {
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

.tag-cloud {
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
}

.tag-item {
  cursor: pointer;
  transition: all 0.3s;
}

.tag-item:hover {
  transform: scale(1.1);
}

.tag-count {
  margin-left: 4px;
  opacity: 0.7;
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
