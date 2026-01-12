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
        <!-- 关于页面 -->
        <div class="about-page">
          <el-card>
            <h2 class="page-title">关于本站</h2>
            <div class="about-content" v-html="aboutContent">
            </div>
          </el-card>
        </div>
      </div>

      <!-- 右侧边栏 -->
      <aside class="blog-sidebar">
        <!-- 博客信息 -->
        <div class="sidebar-widget">
          <h3 class="widget-title">博客信息</h3>
          <div class="blog-info">
            <p><strong>博客名称：</strong>{{ blogTitle }}</p>
            <p><strong>博客副标题：</strong>{{ blogSubtitle }}</p>
            <p><strong>建站时间：</strong>2026-01-12</p>
            <p><strong>技术栈：</strong>RuoYi-Vue + Vue.js</p>
          </div>
        </div>

        <!-- 联系方式 -->
        <div class="sidebar-widget">
          <h3 class="widget-title">联系方式</h3>
          <div class="contact-info">
            <p><i class="el-icon-message"></i> 邮箱：admin@example.com</p>
            <p><i class="el-icon-s-comment"></i> QQ：123456789</p>
            <p><i class="el-icon-location"></i> 地址：中国</p>
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
import { getConfig } from '@/api/blog'
import { getToken } from '@/utils/auth'
import { mapGetters } from 'vuex'

export default {
  name: 'BlogAbout',
  data() {
    return {
      blogTitle: 'zfy-blog',
      blogSubtitle: '基于 RuoYi-Vue 构建的博客系统',
      icpCode: '',
      activeNav: '/blog/about',
      aboutContent: '',
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
    this.loadAboutContent()
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
    // 加载关于页面内容
    loadAboutContent() {
      // 这里可以从数据库加载，暂时使用默认内容
      this.aboutContent = `
        <div class="about-section">
          <h3>关于博客</h3>
          <p>欢迎使用 zfy-blog！这是一个基于 RuoYi-Vue 框架构建的博客系统。</p>
          <p>本博客系统参考了 Typecho Spimes 主题的设计风格，采用简洁优雅的界面设计，提供良好的阅读体验。</p>
        </div>

        <div class="about-section">
          <h3>技术栈</h3>
          <p>后端：Java + Spring Boot + MyBatis + MySQL</p>
          <p>前端：Vue.js + Element UI + Axios</p>
        </div>

        <div class="about-section">
          <h3>功能特点</h3>
          <ul>
            <li>文章管理：支持发布、置顶、推荐等功能</li>
            <li>分类系统：多级分类，方便管理</li>
            <li>标签系统：标签云展示，方便检索</li>
            <li>评论系统：支持评论回复和点赞</li>
            <li>用户系统：登录注册、个人中心</li>
            <li>管理后台：完整的管理功能</li>
          </ul>
        </div>

        <div class="about-section">
          <h3>联系我</h3>
          <p>如果你有任何问题或建议，欢迎通过以下方式联系我：</p>
          <p>邮箱：admin@example.com</p>
        </div>
      `
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

/* 关于页面 */
.about-page {
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

.about-content {
  line-height: 1.8;
  color: #333;
}

.about-content h3 {
  margin: 30px 0 15px;
  font-size: 20px;
  color: #333;
  border-left: 4px solid #409eff;
  padding-left: 10px;
}

.about-content p {
  margin: 0 0 15px;
  color: #666;
}

.about-content ul {
  margin: 0 0 15px;
  padding-left: 20px;
  color: #666;
}

.about-content li {
  margin: 10px 0;
  line-height: 1.6;
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

.blog-info p,
.contact-info p {
  margin: 15px 0;
  color: #666;
}

.blog-info strong {
  color: #333;
}

.contact-info i {
  margin-right: 8px;
  color: #409eff;
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
