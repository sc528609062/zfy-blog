<template>
  <zfy-blog-frame
    active-nav="/user"
    :plugin-context="{ myArticles }"
  >
    <div class="zfy-card zfy-content-card">
      <h2 class="zfy-page-title">个人中心</h2>

      <div class="zfy-card zfy-widget-card">
        <div style="display:flex;align-items:center;gap:14px;">
          <el-avatar :size="72" :src="userAvatar">{{ userName.slice(0, 1) }}</el-avatar>
          <div>
            <h3 style="margin:0;color:var(--zfy-key-color);">{{ userName }}</h3>
            <p class="zfy-plugin-desc" style="margin:8px 0 0;">{{ userRoleText }}</p>
          </div>
          <div style="margin-left:auto;" v-if="isAdmin">
            <el-button type="primary" size="mini" @click="$router.push('/index')">进入后台</el-button>
          </div>
        </div>
      </div>

      <div class="zfy-card zfy-widget-card" style="margin-top:14px;">
        <h3 class="zfy-widget-title">资料编辑</h3>
        <el-form :model="userForm" label-width="80px">
          <el-form-item label="用户名">
            <el-input v-model="userForm.userName" disabled />
          </el-form-item>
          <el-form-item label="昵称">
            <el-input v-model="userForm.nickName" />
          </el-form-item>
          <el-form-item label="邮箱">
            <el-input v-model="userForm.email" />
          </el-form-item>
          <el-form-item label="手机">
            <el-input v-model="userForm.phonenumber" />
          </el-form-item>
          <el-form-item label="性别">
            <el-radio-group v-model="userForm.sex">
              <el-radio label="0">男</el-radio>
              <el-radio label="1">女</el-radio>
              <el-radio label="2">保密</el-radio>
            </el-radio-group>
          </el-form-item>
          <el-form-item>
            <el-button type="primary" @click="saveProfile">保存</el-button>
          </el-form-item>
        </el-form>
      </div>

      <div class="zfy-card zfy-widget-card" style="margin-top:14px;">
        <h3 class="zfy-widget-title">我的文章</h3>
        <ul class="zfy-simple-list" v-if="myArticles.length">
          <li v-for="item in myArticles" :key="item.articleId">
            <router-link :to="`/blog/article/${item.articleId}`">{{ item.articleTitle }}</router-link>
            <span>{{ item.viewCount || 0 }} 阅读</span>
          </li>
        </ul>
        <el-empty v-else description="暂无文章" />
      </div>
    </div>

    <template #sidebar>
      <div class="zfy-card zfy-widget-card">
        <h3 class="zfy-widget-title">我的统计</h3>
        <div class="zfy-stat-grid">
          <div class="zfy-stat-item">
            <span>文章</span>
            <strong>{{ userStats.articleCount }}</strong>
          </div>
          <div class="zfy-stat-item">
            <span>阅读</span>
            <strong>{{ userStats.viewCount }}</strong>
          </div>
          <div class="zfy-stat-item">
            <span>点赞</span>
            <strong>{{ userStats.likeCount }}</strong>
          </div>
        </div>
      </div>
    </template>
  </zfy-blog-frame>
</template>

<script>
import { mapGetters } from 'vuex'
import { listArticle } from '@/api/blog'
import { getUserProfile, updateUserProfile } from '@/api/system/user'
import { getToken } from '@/utils/auth'
import ZfyBlogFrame from './components/ZfyBlogFrame'

export default {
  name: 'UserCenter',
  components: {
    ZfyBlogFrame
  },
  data() {
    return {
      userName: '',
      userAvatar: '',
      userForm: {
        userName: '',
        nickName: '',
        email: '',
        phonenumber: '',
        sex: '2'
      },
      myArticles: [],
      userStats: {
        articleCount: 0,
        viewCount: 0,
        likeCount: 0
      }
    }
  },
  computed: {
    ...mapGetters(['roles']),
    isAdmin() {
      return this.roles && this.roles.includes('admin')
    },
    userRoleText() {
      if (!this.roles || !this.roles.length) {
        return '普通用户'
      }
      return this.roles.includes('admin') ? '管理员' : '普通用户'
    }
  },
  created() {
    if (!getToken()) {
      this.$router.push('/login?redirect=' + encodeURIComponent(this.$route.fullPath))
      return
    }
    this.loadProfile()
  },
  methods: {
    loadProfile() {
      getUserProfile().then(response => {
        const user = response.data || {}
        this.userName = user.userName || ''
        this.userAvatar = user.avatar || ''
        this.userForm = {
          userName: user.userName || '',
          nickName: user.nickName || '',
          email: user.email || '',
          phonenumber: user.phonenumber || '',
          sex: user.sex ? String(user.sex) : '2'
        }
        this.loadArticles()
      })
    },
    loadArticles() {
      listArticle({
        pageNum: 1,
        pageSize: 30,
        articleStatus: '1'
      }).then(response => {
        const rows = response.rows || []
        const result = rows.filter(item => item.authorName === this.userName || !this.userName).slice(0, 12)
        this.myArticles = result
        this.userStats.articleCount = result.length
        this.userStats.viewCount = result.reduce((sum, item) => sum + Number(item.viewCount || 0), 0)
        this.userStats.likeCount = result.reduce((sum, item) => sum + Number(item.likeCount || 0), 0)
      })
    },
    saveProfile() {
      updateUserProfile(this.userForm).then(() => {
        this.$message.success('资料更新成功')
        this.loadProfile()
      })
    }
  }
}
</script>
