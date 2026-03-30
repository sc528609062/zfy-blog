<template>
  <div class="app-container">
    <el-alert title="当前页面用于配置“已启用主题”的自定义参数。" type="warning" :closable="false" style="margin-bottom:14px;" />

    <el-card shadow="never">
      <div slot="header" style="display:flex;justify-content:space-between;align-items:center;">
        <span>{{ activeTheme ? activeTheme.name : '-' }} 配置项</span>
        <div>
          <el-button size="mini" @click="resetSettings" v-hasPermi="['blog:theme:config']">重置</el-button>
          <el-button type="primary" size="mini" @click="saveSettings" v-hasPermi="['blog:theme:config']">保存</el-button>
        </div>
      </div>

      <el-empty v-if="!activeTheme" description="未加载到主题信息" />
      <el-form v-else label-width="130px">
        <el-form-item v-for="item in activeTheme.settings" :key="item.key" :label="item.label">
          <el-color-picker v-if="item.type === 'color'" v-model="form[item.key]" />
          <el-switch v-else-if="item.type === 'switch'" v-model="form[item.key]" />
          <el-input-number
            v-else-if="item.type === 'number'"
            v-model="form[item.key]"
            :min="item.min || 0"
            :max="item.max || 10000"
            :step="item.step || 1"
            controls-position="right"
          />
          <el-input v-else v-model="form[item.key]" />
        </el-form-item>
      </el-form>
    </el-card>
  </div>
</template>

<script>
export default {
  name: 'BlogThemeConfig',
  data() {
    return {
      activeTheme: null,
      form: {}
    }
  },
  created() {
    this.load()
  },
  methods: {
    async load() {
      await this.$zfyThemeRuntime.initialize()
      this.activeTheme = this.$zfyThemeRuntime.getActiveTheme()
      if (!this.activeTheme) {
        return
      }
      const values = this.$zfyThemeRuntime.getSettingsForTheme(this.activeTheme.id)
      const nextForm = {}
      ;(this.activeTheme.settings || []).forEach(item => {
        if (values[item.key] !== undefined) {
          nextForm[item.key] = values[item.key]
        } else {
          nextForm[item.key] = item.default
        }
      })
      this.form = nextForm
    },
    async saveSettings() {
      await this.$zfyThemeRuntime.updateSettings(this.form)
      this.$modal.msgSuccess('主题配置已保存')
    },
    async resetSettings() {
      await this.$zfyThemeRuntime.resetCurrentThemeSettings()
      this.load()
      this.$modal.msgSuccess('已重置当前主题配置')
    }
  }
}
</script>
