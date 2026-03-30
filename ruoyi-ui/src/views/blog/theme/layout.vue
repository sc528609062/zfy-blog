<template>
  <div class="app-container">
    <el-card shadow="never">
      <div slot="header">
        <span>布局模块配置</span>
      </div>
      <el-form label-width="130px" :model="form">
        <el-form-item label="首页显示侧栏">
          <el-switch v-model="form.homeSidebar" />
        </el-form-item>
        <el-form-item label="详情页显示侧栏">
          <el-switch v-model="form.articleSidebar" />
        </el-form-item>
        <el-form-item label="侧栏宽度(px)">
          <el-input-number v-model="form.sidebarWidth" :min="240" :max="420" :step="10" />
        </el-form-item>
        <el-form-item label="卡片间距(px)">
          <el-input-number v-model="form.cardGap" :min="8" :max="40" :step="2" />
        </el-form-item>
      </el-form>
      <el-button type="primary" size="small" @click="save" v-hasPermi="['blog:layout:config']">保存布局</el-button>
    </el-card>
  </div>
</template>

<script>
export default {
  name: 'BlogThemeLayout',
  data() {
    return {
      form: {
        homeSidebar: true,
        articleSidebar: true,
        sidebarWidth: 320,
        cardGap: 16
      }
    }
  },
  async created() {
    const value = await this.$zfyConfigCenter.getJson('zfy_layout_settings', this.form)
    this.form = { ...this.form, ...value }
  },
  methods: {
    async save() {
      await this.$zfyConfigCenter.save('zfy_layout_settings', this.form, {
        group: 'theme',
        desc: 'ZFY布局设置'
      })
      this.$modal.msgSuccess('布局设置已保存')
    }
  }
}
</script>
