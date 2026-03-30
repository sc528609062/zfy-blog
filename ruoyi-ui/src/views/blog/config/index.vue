<template>
  <div class="app-container">
    <el-row :gutter="10" class="mb8">
      <el-col :span="1.5">
        <el-button type="primary" plain icon="el-icon-refresh" size="mini" @click="refreshRuntime">
          刷新运行时
        </el-button>
      </el-col>
      <el-col :span="1.5">
        <el-button type="success" plain icon="el-icon-plus" size="mini" @click="handleAddConfig" v-hasPermi="['blog:config:add']">
          新增配置
        </el-button>
      </el-col>
    </el-row>

    <el-tabs v-model="activeTab">
      <el-tab-pane label="主题管理器" name="theme">
        <div class="zfy-theme-manager">
          <div class="zfy-card zfy-content-card">
            <h3 class="zfy-widget-title">可用主题</h3>
            <div
              v-for="theme in themeList"
              :key="theme.id"
              class="zfy-theme-preview-item"
              :class="{ 'zfy-theme-preview-item--active': selectedThemeId === theme.id }"
              @click="selectTheme(theme.id)"
            >
              <div style="display:flex;justify-content:space-between;align-items:center;">
                <div>
                  <span class="zfy-theme-preview-dot" :style="{ background: theme.previewColor }" />
                  <strong>{{ theme.name }}</strong>
                </div>
                <el-tag size="mini" v-if="theme.id === activeThemeId" type="success">启用中</el-tag>
              </div>
              <p class="zfy-plugin-desc">{{ theme.description }}</p>
            </div>
          </div>

          <div class="zfy-card zfy-content-card" v-if="selectedTheme">
            <h3 class="zfy-widget-title">主题自定义</h3>
            <el-alert
              v-if="selectedThemeId !== activeThemeId"
              type="warning"
              :closable="false"
              title="当前选中的主题尚未启用，需先启用主题后才能保存该主题配置。"
              style="margin-bottom:12px;"
            />

            <el-form label-width="130px">
              <el-form-item v-for="field in selectedTheme.settings" :key="field.key" :label="field.label">
                <el-color-picker
                  v-if="field.type === 'color'"
                  v-model="themeForm[field.key]"
                  :show-alpha="false"
                />

                <el-switch
                  v-else-if="field.type === 'switch'"
                  v-model="themeForm[field.key]"
                />

                <el-input-number
                  v-else-if="field.type === 'number'"
                  v-model="themeForm[field.key]"
                  :min="field.min || 0"
                  :max="field.max || 10000"
                  :step="field.step || 1"
                  controls-position="right"
                />

                <el-input
                  v-else
                  v-model="themeForm[field.key]"
                />
              </el-form-item>
            </el-form>

            <div>
              <el-button type="primary" @click="activateTheme(selectedThemeId)" :disabled="selectedThemeId === activeThemeId">
                启用主题
              </el-button>
              <el-button type="success" @click="saveThemeSettings" :disabled="selectedThemeId !== activeThemeId">
                保存主题配置
              </el-button>
              <el-button @click="resetThemeSettings" :disabled="selectedThemeId !== activeThemeId">
                重置当前主题配置
              </el-button>
            </div>
          </div>
        </div>
      </el-tab-pane>

      <el-tab-pane label="插件管理器" name="plugin">
        <div class="zfy-card zfy-content-card">
          <h3 class="zfy-widget-title">插件列表</h3>

          <div v-for="plugin in pluginList" :key="plugin.id" class="zfy-plugin-item">
            <div class="zfy-plugin-head">
              <div>
                <strong>{{ plugin.name }}</strong>
                <el-tag size="mini" style="margin-left:8px;">v{{ plugin.version }}</el-tag>
              </div>
              <el-switch
                :value="plugin.enabled"
                @change="togglePlugin(plugin, $event)"
              />
            </div>

            <p class="zfy-plugin-desc">{{ plugin.description }}</p>

            <div class="zfy-plugin-setting" v-if="plugin.settings && plugin.settings.length">
              <el-form label-width="120px">
                <el-form-item v-for="field in plugin.settings" :key="`${plugin.id}-${field.key}`" :label="field.label">
                  <el-input
                    v-if="field.type === 'text'"
                    :value="plugin.runtimeSettings[field.key]"
                    @change="updatePluginSetting(plugin, field, $event)"
                  />
                  <el-input
                    type="textarea"
                    :rows="3"
                    v-else-if="field.type === 'textarea'"
                    :value="plugin.runtimeSettings[field.key]"
                    @change="updatePluginSetting(plugin, field, $event)"
                  />
                  <el-input-number
                    v-else-if="field.type === 'number'"
                    :value="plugin.runtimeSettings[field.key]"
                    :min="field.min || 0"
                    :max="field.max || 10000"
                    :step="field.step || 1"
                    controls-position="right"
                    @change="updatePluginSetting(plugin, field, $event)"
                  />
                  <el-switch
                    v-else-if="field.type === 'switch'"
                    :value="plugin.runtimeSettings[field.key]"
                    @change="updatePluginSetting(plugin, field, $event)"
                  />
                  <el-input
                    v-else
                    :value="plugin.runtimeSettings[field.key]"
                    @change="updatePluginSetting(plugin, field, $event)"
                  />
                </el-form-item>
              </el-form>
            </div>
          </div>
        </div>
      </el-tab-pane>

      <el-tab-pane label="系统配置中心" name="config">
        <el-table v-loading="loading" :data="configList">
          <el-table-column label="配置ID" align="center" prop="configId" width="80" />
          <el-table-column label="配置键" align="center" prop="configKey" />
          <el-table-column label="配置值" align="center" prop="configValue" :show-overflow-tooltip="true" />
          <el-table-column label="描述" align="center" prop="configDesc" :show-overflow-tooltip="true" />
          <el-table-column label="分组" align="center" prop="configGroup" width="120" />
          <el-table-column label="更新时间" align="center" prop="updateTime" width="180">
            <template slot-scope="scope">
              <span>{{ parseTime(scope.row.updateTime || scope.row.createTime) }}</span>
            </template>
          </el-table-column>
          <el-table-column label="操作" align="center" class-name="small-padding fixed-width" width="220">
            <template slot-scope="scope">
              <el-button size="mini" type="text" icon="el-icon-edit" @click="handleEditConfig(scope.row)" v-hasPermi="['blog:config:edit']">
                编辑
              </el-button>
              <el-button size="mini" type="text" icon="el-icon-delete" @click="handleDeleteConfig(scope.row)" v-hasPermi="['blog:config:remove']">
                删除
              </el-button>
            </template>
          </el-table-column>
        </el-table>
      </el-tab-pane>
    </el-tabs>

    <el-dialog :title="dialogTitle" :visible.sync="dialogVisible" width="620px" append-to-body>
      <el-form ref="configFormRef" :model="configForm" :rules="rules" label-width="90px">
        <el-form-item label="配置键" prop="configKey">
          <el-input v-model="configForm.configKey" placeholder="如：zfy_theme_active" />
        </el-form-item>
        <el-form-item label="配置值" prop="configValue">
          <el-input v-model="configForm.configValue" type="textarea" :rows="5" />
        </el-form-item>
        <el-form-item label="描述" prop="configDesc">
          <el-input v-model="configForm.configDesc" />
        </el-form-item>
        <el-form-item label="分组" prop="configGroup">
          <el-input v-model="configForm.configGroup" placeholder="如：theme/plugin/basic" />
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button type="primary" @click="submitConfigForm">确 定</el-button>
        <el-button @click="dialogVisible = false">取 消</el-button>
      </div>
    </el-dialog>
  </div>
</template>

<script>
import { addConfig, delConfig, listConfig, updateConfig } from '@/api/blog'

function cloneThemeSettings(theme, allSettings = {}) {
  const values = {}
  const current = allSettings[theme.id] || {}
  ;(theme.settings || []).forEach(field => {
    if (current[field.key] !== undefined) {
      values[field.key] = current[field.key]
    } else {
      values[field.key] = field.default
    }
  })
  return values
}

export default {
  name: 'BlogConfigList',
  data() {
    return {
      activeTab: 'theme',
      loading: false,
      themeList: [],
      selectedThemeId: '',
      activeThemeId: '',
      themeForm: {},
      pluginList: [],
      configList: [],
      dialogVisible: false,
      dialogTitle: '',
      configForm: {
        configId: null,
        configKey: '',
        configValue: '',
        configDesc: '',
        configGroup: 'basic'
      },
      rules: {
        configKey: [{ required: true, message: '配置键不能为空', trigger: 'blur' }],
        configValue: [{ required: true, message: '配置值不能为空', trigger: 'blur' }]
      }
    }
  },
  computed: {
    selectedTheme() {
      return this.themeList.find(item => item.id === this.selectedThemeId) || null
    }
  },
  created() {
    this.refreshRuntime()
    this.fetchConfigList()
  },
  methods: {
    async refreshRuntime() {
      if (this.$zfyThemeRuntime) {
        await this.$zfyThemeRuntime.initialize()
        this.themeList = this.$zfyThemeRuntime.getThemeList()
        this.activeThemeId = this.$zfyThemeRuntime.activeThemeId
        this.selectedThemeId = this.selectedThemeId || this.activeThemeId
        const selected = this.themeList.find(item => item.id === this.selectedThemeId)
        if (selected) {
          this.themeForm = cloneThemeSettings(selected, this.$zfyThemeRuntime.themeSettings)
        }
      }
      if (this.$zfyPluginRuntime) {
        await this.$zfyPluginRuntime.initialize()
        this.pluginList = this.$zfyPluginRuntime.getPluginList()
      }
    },
    selectTheme(themeId) {
      this.selectedThemeId = themeId
      const selected = this.themeList.find(item => item.id === themeId)
      if (selected) {
        this.themeForm = cloneThemeSettings(selected, this.$zfyThemeRuntime.themeSettings)
      }
    },
    async activateTheme(themeId) {
      await this.$zfyThemeRuntime.setActiveTheme(themeId)
      this.activeThemeId = themeId
      this.selectTheme(themeId)
      this.$modal.msgSuccess('主题启用成功')
      this.fetchConfigList()
    },
    async saveThemeSettings() {
      await this.$zfyThemeRuntime.updateSettings(this.themeForm)
      this.$modal.msgSuccess('主题配置已保存')
      this.fetchConfigList()
    },
    async resetThemeSettings() {
      await this.$zfyThemeRuntime.resetCurrentThemeSettings()
      this.selectTheme(this.activeThemeId)
      this.$modal.msgSuccess('当前主题配置已重置')
      this.fetchConfigList()
    },
    async togglePlugin(plugin, enabled) {
      await this.$zfyPluginRuntime.setEnabled(plugin.id, enabled)
      this.pluginList = this.$zfyPluginRuntime.getPluginList()
      this.$modal.msgSuccess('插件状态已更新')
      this.fetchConfigList()
    },
    async updatePluginSetting(plugin, field, value) {
      await this.$zfyPluginRuntime.updateSetting(plugin.id, field.key, value)
      this.pluginList = this.$zfyPluginRuntime.getPluginList()
    },
    fetchConfigList() {
      this.loading = true
      listConfig().then(response => {
        this.configList = response.rows || []
      }).finally(() => {
        this.loading = false
      })
    },
    resetConfigForm() {
      this.configForm = {
        configId: null,
        configKey: '',
        configValue: '',
        configDesc: '',
        configGroup: 'basic'
      }
    },
    handleAddConfig() {
      this.dialogTitle = '新增配置'
      this.resetConfigForm()
      this.dialogVisible = true
    },
    handleEditConfig(row) {
      this.dialogTitle = '编辑配置'
      this.configForm = { ...row }
      this.dialogVisible = true
    },
    submitConfigForm() {
      this.$refs.configFormRef.validate(valid => {
        if (!valid) {
          return
        }
        const request = this.configForm.configId ? updateConfig(this.configForm) : addConfig(this.configForm)
        request.then(() => {
          this.$modal.msgSuccess(this.configForm.configId ? '更新成功' : '新增成功')
          this.dialogVisible = false
          this.fetchConfigList()
          this.refreshRuntime()
        })
      })
    },
    handleDeleteConfig(row) {
      this.$modal.confirm(`确认删除配置键 "${row.configKey}" 吗？`).then(() => {
        return delConfig(row.configId)
      }).then(() => {
        this.$modal.msgSuccess('删除成功')
        this.fetchConfigList()
        this.refreshRuntime()
      }).catch(() => {})
    }
  }
}
</script>
