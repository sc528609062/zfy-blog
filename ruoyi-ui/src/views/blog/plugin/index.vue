<template>
  <div class="app-container">
    <el-alert title="插件开关和配置将持久化到 blog_config，支持后续热扩展。" type="success" :closable="false" style="margin-bottom:14px;" />

    <el-card shadow="never">
      <div slot="header" style="display:flex;justify-content:space-between;align-items:center;">
        <span>插件列表</span>
        <el-button size="mini" @click="refresh">刷新</el-button>
      </div>

      <div v-for="plugin in pluginList" :key="plugin.id" style="border:1px solid #ebeef5;border-radius:6px;padding:14px;margin-bottom:12px;">
        <div style="display:flex;justify-content:space-between;align-items:center;">
          <div>
            <strong>{{ plugin.name }}</strong>
            <el-tag size="mini" style="margin-left:8px;">v{{ plugin.version }}</el-tag>
          </div>
          <el-switch :value="plugin.enabled" @change="togglePlugin(plugin, $event)" v-hasPermi="['blog:plugin:enable']" />
        </div>
        <p style="margin-top:8px;color:#909399;">{{ plugin.description }}</p>

        <el-form label-width="100px" style="margin-top:8px;" v-if="plugin.settings && plugin.settings.length">
          <el-form-item v-for="field in plugin.settings" :key="`${plugin.id}-${field.key}`" :label="field.label">
            <el-input
              v-if="field.type === 'text'"
              :value="plugin.runtimeSettings[field.key]"
              @change="changeSetting(plugin, field.key, $event)"
              size="small"
            />
            <el-input
              v-else-if="field.type === 'textarea'"
              type="textarea"
              :rows="3"
              :value="plugin.runtimeSettings[field.key]"
              @change="changeSetting(plugin, field.key, $event)"
              size="small"
            />
            <el-input-number
              v-else-if="field.type === 'number'"
              :value="plugin.runtimeSettings[field.key]"
              :min="field.min || 0"
              :max="field.max || 10000"
              :step="field.step || 1"
              controls-position="right"
              @change="changeSetting(plugin, field.key, $event)"
            />
            <el-switch
              v-else-if="field.type === 'switch'"
              :value="plugin.runtimeSettings[field.key]"
              @change="changeSetting(plugin, field.key, $event)"
            />
            <el-input
              v-else
              :value="plugin.runtimeSettings[field.key]"
              @change="changeSetting(plugin, field.key, $event)"
              size="small"
            />
          </el-form-item>
        </el-form>
      </div>
    </el-card>
  </div>
</template>

<script>
export default {
  name: 'BlogPluginIndex',
  data() {
    return {
      pluginList: []
    }
  },
  created() {
    this.refresh()
  },
  methods: {
    async refresh() {
      await this.$zfyPluginRuntime.initialize()
      this.pluginList = this.$zfyPluginRuntime.getPluginList()
    },
    async togglePlugin(plugin, enabled) {
      await this.$zfyPluginRuntime.setEnabled(plugin.id, enabled)
      this.refresh()
      this.$modal.msgSuccess('插件状态已更新')
    },
    async changeSetting(plugin, key, value) {
      await this.$zfyPluginRuntime.updateSetting(plugin.id, key, value)
      this.refresh()
    }
  }
}
</script>
