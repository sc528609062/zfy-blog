<template>
  <div class="app-container">
    <el-alert title="主题安装支持上传 zip 包，当前为本地记录模式。" type="info" :closable="false" style="margin-bottom:14px;" />

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
const CACHE_KEY = 'zfy_theme_install_records'

export default {
  name: 'BlogThemeInstall',
  data() {
    return {
      installedList: []
    }
  },
  created() {
    this.installedList = this.$cache.local.getJSON(CACHE_KEY) || []
  },
  methods: {
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
    installTheme(option) {
      const file = option.file
      const row = {
        themeName: file.name.replace(/\.zip$/i, ''),
        packageName: file.name,
        installTime: new Date()
      }
      this.installedList.unshift(row)
      this.$cache.local.setJSON(CACHE_KEY, this.installedList)
      this.$modal.msgSuccess('主题安装完成（本地模拟）')
      option.onSuccess({ code: 200, msg: 'ok' })
    }
  }
}
</script>
