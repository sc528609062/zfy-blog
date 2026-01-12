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
            <div class="user-info">
              <el-dropdown @command="handleCommand">
                <span class="el-dropdown-link">
                  <el-avatar :size="32" :src="userAvatar">
                    {{ userName.charAt(0) }}
                  </el-avatar>
                  <span class="username">{{ userName }}</span>
                  <i class="el-icon-arrow-down el-icon--right"></i>
                </span>
                <el-dropdown-menu slot="dropdown">
                  <el-dropdown-item command="userCenter" disabled>
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
        <!-- 用户中心 -->
        <div class="user-center">
          <el-card class="user-card">
            <div slot="header" class="card-header">
              <span>个人中心</span>
              <el-button v-if="isAdmin" type="primary" size="small" @click="goToAdmin">
                进入后台管理
              </el-button>
            </div>

            <!-- 用户信息 -->
            <div class="user-info-section">
              <div class="avatar-section">
                <el-avatar :size="100" :src="userAvatar">
                  {{ userName.charAt(0) }}
                </el-avatar>
              </div>
              <div class="info-section">
                <h2 class="user-name">{{ userName }}</h2>
                <p class="user-role">
                  <el-tag size="small" v-for="role in roles" :key="role">
                    {{ role === 'admin' ? '管理员' : '普通用户' }}
                  </el-tag>
                </p>
              </div>
            </div>

            <!-- 个人资料编辑 -->
            <el-divider>个人资料</el-divider>
            <el-form :model="userForm" label-width="80px">
              <el-form-item label="用户名">
                <el-input v-model="userForm.userName" disabled></el-input>
              </el-form-item>
              <el-form-item label="昵称">
                <el-input v-model="userForm.nickName"></el-input>
              </el-form-item>
              <el-form-item label="邮箱">
                <el-input v-model="userForm.email"></el-input>
              </el-form-item>
              <el-form-item label="手机号">
                <el-input v-model="userForm.phonenumber"></el-input>
              </el-form-item>
              <el-form-item label="性别">
                <el-radio-group v-model="userForm.sex">
                  <el-radio label="0">男</el-radio>
                  <el-radio label="1">女</el-radio>
                  <el-radio label="2">保密</el-radio>
                </el-radio-group>
              </el-form-item>
              <el-form-item>
                <el-button type="primary" @click="updateProfile">保存修改</el-button>
              </el-form-item>
            </el-form>

            <!-- 我的文章 -->
            <el-divider>我的文章</el-divider>
            <div v-if="myArticles.length > 0" class="my-articles">
              <div v-for="article in myArticles" :key="article.articleId" class="article-item">
                <h3>
                  <router-link :to="'/blog/article/' + article.articleId">
                    {{ article.articleTitle }}
                  </router-link>
                </h3>
                <p>{{ article.articleSummary }}</p>
                <div class="article-meta">
                  <span>{{ formatDate(article.publishTime) }}</span>
                  <span>{{ article.viewCount }} 阅读</span>
                  <span>{{ article.commentCount }} 评论</span>
                  <el-tag size="mini" :type="article.articleStatus === '1' ? 'success' : 'info'">
                    {{ article.articleStatus === '1' ? '已发布' : '草稿' }}
                  </el-tag>
                </div>
              </div>
            </div>
            <el-empty v-else description="暂无文章"></el-empty>

            <!-- 我的评论 -->
            <el-divider>我的评论</el-divider>
            <div v-if="myComments.length > 0" class="my-comments">
              <div v-for="comment in myComments" :key="comment.commentId" class="comment-item">
                <div class="comment-content">
                  <p>{{ comment.commentContent }}</p>
                  <div class="comment-meta">
                    <span>{{ formatDate(comment.createTime) }}</span>
                    <router-link :to="'/blog/article/' + comment.articleId">
                      查看文章
                    </router-link>
                  </div>
                </div>
              </div>
            </div>
            <el-empty v-else description="暂无评论"></el-empty>
          </el-card>
        </div>
      </div>

      <!-- 右侧边栏 -->
      <aside class="blog-sidebar">
        <!-- 统计信息 -->
        <div class="sidebar-widget">
          <h3 class="widget-title">我的统计</h3>
          <div class="stats-list">
            <div class="stat-item">
              <span class="stat-label">文章数</span>
              <span class="stat-value">{{ userStats.articleCount }}</span>
            </div>
            <div class="stat-item">
              <span class="stat-label">评论数</span>
              <span class="stat-value">{{ userStats.commentCount }}</span>
            </div>
            <div class="stat-item">
              <span class="stat-label">获赞数</span>
              <span class="stat-value">{{ userStats.likeCount }}</span>
            </div>
          </div>
        </div>

        <!-- 快捷操作 -->
        <div class="sidebar-widget">
          <h3 class="widget-title">快捷操作</h3>
          <el-button type="primary" icon="el-icon-edit" style="width: 100%; margin-bottom: 10px;">
            写文章
          </el-button>
          <el-button icon="el-icon-picture" style="width: 100%; margin-bottom: 10px;">
            文章管理
          </el-button>
          <el-button icon="el-icon-chat-dot-round" style="width: 100%;">
            评论管理
          </el-button>
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
import { getUserProfile, updateUserProfile } from '@/api/system/user'
import { getToken } from '@/utils/auth'
import { mapGetters } from 'vuex'

export default {
  name: 'UserCenter',
  data() {
    return {
      blogTitle: 'zfy-blog',
      blogSubtitle: '基于 RuoYi-Vue 构建的博客系统',
      icpCode: '',
      activeNav: '/user',
      userAvatar: '',
      userName: '',
      userForm: {
        userName: '',
        nickName: '',
        email: '',
        phonenumber: '',
        sex: '2'
      },
      myArticles: [],
      myComments: [],
      userStats: {
        articleCount: 0,
        commentCount: 0,
        likeCount: 0
      }
    }
  },
  computed: {
    ...mapGetters(['roles']),
    isAdmin() {
      return this.roles && this.roles.includes('admin')
    }
  },
  created() {
    if (!getToken()) {
      this.$message.warning('请先登录')
      this.$router.push('/login?redirect=' + encodeURIComponent(this.$route.fullPath))
      return
    }
    this.loadUserInfo()
    this.loadMyArticles()
  },
  methods: {
    // 加载用户信息
    loadUserInfo() {
      getUserProfile().then(response => {
        const user = response.data
        this.userName = user.userName
        this.userAvatar = user.avatar || ''
        this.userForm = {
          userName: user.userName,
          nickName: user.nickName || '',
          email: user.email || '',
          phonenumber: user.phonenumber || '',
          sex: user.sex ? user.sex.toString() : '2'
        }
      })
    },
    // 加载我的文章
    loadMyArticles() {
      listArticle({
        pageNum: 1,
        pageSize: 10,
        articleStatus: '1'
      }).then(response => {
        this.myArticles = response.rows
        this.userStats.articleCount = response.total
      })
    },
    // 更新个人资料
    updateProfile() {
      updateUserProfile(this.userForm).then(() => {
        this.$message.success('保存成功')
        this.loadUserInfo()
      })
    },
    // 进入后台管理
    goToAdmin() {
      this.$router.push('/index')
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
              this.$message.success('退出登录成功')
              this.$router.push('/')
            })
          }).catch(() => {})
          break
      }
    },
    // 格式化日期
    formatDate(date) {
      if (!date) return ''
      return this.parseTime(date, '{y}-{m}-{d}')
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

/* 用户中心 */
.user-center {
  background: #fff;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.user-info-section {
  display: flex;
  align-items: center;
  margin-bottom: 30px;
}

.avatar-section {
  flex-shrink: 0;
}

.info-section {
  flex: 1;
  margin-left: 20px;
}

.user-name {
  margin: 0 0 10px;
  font-size: 24px;
  color: #333;
}

.user-role {
  margin: 0;
}

/* 我的文章 */
.my-articles {
  margin-top: 20px;
}

.article-item {
  padding: 20px;
  border-bottom: 1px solid #eee;
}

.article-item:last-child {
  border-bottom: none;
}

.article-item h3 {
  margin: 0 0 10px;
  font-size: 18px;
}

.article-item h3 a {
  color: #333;
  text-decoration: none;
  transition: color 0.3s;
}

.article-item h3 a:hover {
  color: #409eff;
}

.article-item p {
  margin: 0 0 10px;
  color: #666;
  font-size: 14px;
  line-height: 1.6;
}

.article-meta {
  display: flex;
  align-items: center;
  gap: 15px;
  font-size: 13px;
  color: #999;
}

/* 我的评论 */
.my-comments {
  margin-top: 20px;
}

.comment-item {
  padding: 20px;
  border-bottom: 1px solid #eee;
}

.comment-item:last-child {
  border-bottom: none;
}

.comment-content p {
  margin: 0 0 10px;
  color: #333;
  line-height: 1.6;
}

.comment-meta {
  font-size: 13px;
  color: #999;
  display: flex;
  align-items: center;
  gap: 15px;
}

.comment-meta a {
  color: #409eff;
  text-decoration: none;
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

/* 统计信息 */
.stats-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.stat-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 0;
  border-bottom: 1px solid #eee;
}

.stat-item:last-child {
  border-bottom: none;
}

.stat-label {
  color: #666;
  font-size: 14px;
}

.stat-value {
  font-size: 20px;
  font-weight: bold;
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

  .user-info-section {
    flex-direction: column;
    text-align: center;
  }

  .info-section {
    margin-left: 0;
    margin-top: 15px;
  }
}
</style>
