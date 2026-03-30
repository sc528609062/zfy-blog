<template>
  <div class="zfy-site">
    <header class="zfy-header">
      <div class="zfy-header-inner">
        <div class="zfy-brand">
          <h1 class="zfy-brand-title">{{ siteTitle }}</h1>
          <p class="zfy-brand-subtitle">{{ siteSubtitle }}</p>
        </div>

        <div class="zfy-nav-wrap">
          <el-menu
            class="zfy-nav-menu"
            :default-active="activeNav"
            mode="horizontal"
            router
          >
            <el-menu-item index="/">首页</el-menu-item>
            <el-menu-item index="/blog/category">分类</el-menu-item>
            <el-menu-item index="/blog/tag">标签</el-menu-item>
            <el-menu-item index="/blog/archives">归档</el-menu-item>
            <el-menu-item index="/blog/about">关于</el-menu-item>
          </el-menu>

          <div class="zfy-user-actions">
            <template v-if="!token">
              <el-button type="primary" size="mini" @click="goToLogin">登录</el-button>
              <el-button size="mini" @click="goToRegister">注册</el-button>
            </template>

            <template v-else>
              <el-dropdown @command="handleCommand">
                <span class="zfy-user-menu">
                  <el-avatar :size="28" :src="userAvatar">{{ userName.charAt(0) }}</el-avatar>
                  <span class="zfy-user-name">{{ userName }}</span>
                  <i class="el-icon-arrow-down el-icon--right" />
                </span>
                <el-dropdown-menu slot="dropdown">
                  <el-dropdown-item command="userCenter">个人中心</el-dropdown-item>
                  <el-dropdown-item command="adminPanel" v-if="isAdmin" divided>后台管理</el-dropdown-item>
                  <el-dropdown-item command="themeManager" v-if="isAdmin">主题管理</el-dropdown-item>
                  <el-dropdown-item command="logout" divided>退出登录</el-dropdown-item>
                </el-dropdown-menu>
              </el-dropdown>
            </template>
          </div>
        </div>
      </div>
    </header>

    <div class="zfy-main-grid" :class="{ 'zfy-main-grid--single': !showSidebar }">
      <section>
        <slot />
      </section>

      <aside v-if="showSidebar">
        <slot name="sidebar" />
        <div
          v-for="widget in pluginWidgets"
          :key="`${widget.pluginId}-${widget.widgetId}`"
          class="zfy-card zfy-widget-card"
        >
          <h3 class="zfy-widget-title" v-if="widget.title">{{ widget.title }}</h3>

          <p v-if="widget.type === 'text'" class="zfy-plugin-desc">{{ widget.content }}</p>

          <ul v-else-if="widget.type === 'list'" class="zfy-simple-list">
            <li v-for="(item, index) in widget.items" :key="`${widget.widgetId}-${index}`">
              <router-link v-if="item.to" :to="item.to">{{ item.text }}</router-link>
              <a v-else-if="item.href" :href="item.href" target="_blank">{{ item.text }}</a>
              <span v-else>{{ item.text }}</span>
              <span>{{ item.meta }}</span>
            </li>
          </ul>
        </div>
      </aside>
    </div>

    <footer class="zfy-footer" v-if="showFooter">
      <div class="zfy-footer-inner">
        <p>&copy; {{ currentYear }} {{ siteTitle }}. All rights reserved.</p>
        <p v-if="icpCode">备案号：{{ icpCode }}</p>
      </div>
    </footer>
  </div>
</template>

<script>
import { mapGetters } from 'vuex'
import { getConfig } from '@/api/blog'
import { getToken } from '@/utils/auth'

export default {
  name: 'ZfyBlogFrame',
  props: {
    activeNav: {
      type: String,
      default: '/'
    },
    showSidebar: {
      type: Boolean,
      default: true
    },
    showFooter: {
      type: Boolean,
      default: true
    },
    pluginArea: {
      type: String,
      default: 'sidebar.global'
    },
    pluginContext: {
      type: Object,
      default: () => ({})
    }
  },
  data() {
    return {
      siteTitle: 'zfy-blog',
      siteSubtitle: '高可定制内容系统',
      icpCode: '',
      token: getToken(),
      userName: '',
      userAvatar: ''
    }
  },
  computed: {
    ...mapGetters(['roles']),
    isAdmin() {
      return this.roles && this.roles.includes('admin')
    },
    currentYear() {
      return new Date().getFullYear()
    },
    pluginWidgets() {
      if (!this.$zfyPluginRuntime) {
        return []
      }
      return this.$zfyPluginRuntime.resolveWidgets(this.pluginArea, {
        ...this.pluginContext,
        route: this.$route.path
      })
    }
  },
  watch: {
    '$route.path'() {
      this.syncTokenAndUser()
    }
  },
  created() {
    this.loadSiteConfig()
    this.syncTokenAndUser()
    if (this.$zfyThemeRuntime) {
      this.$zfyThemeRuntime.initialize()
    }
    if (this.$zfyPluginRuntime) {
      this.$zfyPluginRuntime.initialize()
    }
  },
  methods: {
    async loadSiteConfig() {
      const configs = [
        { key: 'site_title', field: 'siteTitle' },
        { key: 'site_subtitle', field: 'siteSubtitle' },
        { key: 'icp_code', field: 'icpCode' }
      ]
      for (const item of configs) {
        try {
          const response = await getConfig(item.key)
          const value = response && response.data ? response.data.configValue : ''
          if (value) {
            this[item.field] = value
          }
        } catch (error) {
          // ignore single-config request failures and keep defaults
        }
      }
    },
    syncTokenAndUser() {
      this.token = getToken()
      if (this.token) {
        this.loadUserInfo()
      } else {
        this.userName = ''
        this.userAvatar = ''
      }
    },
    loadUserInfo() {
      this.$store.dispatch('GetInfo').then(() => {
        this.userName = this.$store.state.user.name || '用户'
        this.userAvatar = this.$store.state.user.avatar || ''
      }).catch(() => {
        this.userName = '用户'
        this.userAvatar = ''
      })
    },
    goToLogin() {
      this.$router.push('/login?redirect=' + encodeURIComponent(this.$route.fullPath))
    },
    goToRegister() {
      this.$router.push('/register')
    },
    handleCommand(command) {
      switch (command) {
        case 'userCenter':
          this.$router.push('/user')
          break
        case 'adminPanel':
          this.$router.push('/index')
          break
        case 'themeManager':
          this.$router.push('/blog/config')
          break
        case 'logout':
          this.$confirm('确认退出当前登录状态吗？', '提示', {
            confirmButtonText: '确认',
            cancelButtonText: '取消',
            type: 'warning'
          }).then(() => {
            this.$store.dispatch('LogOut').then(() => {
              this.token = ''
              this.userName = ''
              this.userAvatar = ''
              this.$router.push('/')
            })
          }).catch(() => {})
          break
      }
    }
  }
}
</script>
