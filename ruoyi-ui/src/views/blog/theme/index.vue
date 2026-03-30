<template>
  <div class="app-container">
    <el-alert
      title="主题选择会实时作用于前台样式，并持久化到 blog_config。"
      type="info"
      :closable="false"
      style="margin-bottom: 14px;"
    />

    <el-row :gutter="16">
      <el-col :span="8" v-for="theme in themeList" :key="theme.id">
        <el-card shadow="hover" :body-style="{ padding: '16px' }">
          <div style="display:flex;align-items:center;justify-content:space-between;">
            <div>
              <strong>{{ theme.name }}</strong>
              <el-tag size="mini" v-if="theme.id === activeThemeId" type="success" style="margin-left:8px;">启用中</el-tag>
            </div>
            <span style="display:inline-block;width:14px;height:14px;border-radius:50%;" :style="{ background: theme.previewColor }" />
          </div>
          <p style="margin:12px 0 0;color:#909399;line-height:1.6;">{{ theme.description }}</p>
          <div style="margin-top:14px;">
            <el-button
              size="mini"
              type="primary"
              :disabled="theme.id === activeThemeId"
              @click="activateTheme(theme.id)"
              v-hasPermi="['blog:theme:config']"
            >
              启用主题
            </el-button>
            <el-button size="mini" @click="$router.push('/theme/theme-config')" v-hasPermi="['blog:theme:config']">
              自定义
            </el-button>
          </div>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script>
export default {
  name: 'BlogThemeIndex',
  data() {
    return {
      themeList: [],
      activeThemeId: ''
    }
  },
  created() {
    this.loadThemes()
  },
  methods: {
    async loadThemes() {
      await this.$zfyThemeRuntime.initialize()
      this.themeList = this.$zfyThemeRuntime.getThemeList()
      this.activeThemeId = this.$zfyThemeRuntime.activeThemeId
    },
    async activateTheme(themeId) {
      await this.$zfyThemeRuntime.setActiveTheme(themeId)
      this.activeThemeId = themeId
      this.$modal.msgSuccess('主题已切换')
    }
  }
}
</script>
