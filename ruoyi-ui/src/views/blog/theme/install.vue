<template>
  <div class="app-container">
    <el-alert title="主题安装记录已改为后端持久化（blog_config）" type="info" :closable="false" style="margin-bottom:14px;" />

    <el-upload
      action=""
      drag
      :show-file-list="false"
      :http-request="installTheme"
      :before-upload="beforeUpload"
      style="margin-bottom:16px;"
    >
      <i class="el-icon-upload"></i>
      <div class="el-upload__text">将主题压缩包拖到此处，或<em>点击上传</em></div>
      <div class="el-upload__tip" slot="tip">仅支持 .zip 文件，大小不超过 50MB</div>
    </el-upload>

    <el-table :data="installedList">
      <el-table-column prop="themeName" label="主题名" min-width="220" />
      <el-table-column prop="packageName" label="安装包" min-width="220" />
      <el-table-column prop="installTime" label="安装时间" width="180">
        <template slot-scope="scope">{{ parseTime(scope.row.installTime) }}</template>
      </el-table-column>
    </el-table>
  </div>
</template>

<script>
const STORE_KEY = 'zfy_theme_install_records'

export default {
  name: 'BlogThemeInstall',
  data() {
    return {
      installedList: []
    }
  },
  async created() {
    this.installedList = await this.$zfyConfigCenter.getJson(STORE_KEY, [])
  },
  methods: {
    async persist() {
      await this.$zfyConfigCenter.save(STORE_KEY, this.installedList, {
        group: 'theme',
        desc: '主题安装记录'
      })
    },
    beforeUpload(file) {
      const isZip = /\.zip$/i.test(file.name)
      if (!isZip) {
        this.$modal.msgError('仅支持 zip 安装包')
        return false
      }
      const maxMb = 50
      if (file.size / 1024 / 1024 > maxMb) {
        this.$modal.msgError(`安装包不能超过 ${maxMb}MB`)
        return false
      }
      return true
    },
    async installTheme(option) {
      const file = option.file
      this.installedList.unshift({
        themeName: file.name.replace(/\.zip$/i, ''),
        packageName: file.name,
        installTime: new Date()
      })
      await this.persist()
      this.$modal.msgSuccess('主题安装完成')
      option.onSuccess({ code: 200, msg: 'ok' })
    }
  }
}
</script>
