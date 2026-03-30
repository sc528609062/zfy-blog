<template>
  <div class="app-container">
    <el-row :gutter="10" class="mb8">
      <el-col :span="4">
        <el-input v-model="query.fileName" size="small" placeholder="按文件名筛选" clearable />
      </el-col>
      <el-col :span="4">
        <el-select v-model="query.fileType" size="small" placeholder="文件类型" clearable>
          <el-option label="图片" value="image" />
          <el-option label="文档" value="document" />
          <el-option label="其他" value="other" />
        </el-select>
      </el-col>
      <el-col :span="8">
        <el-upload
          action=""
          :show-file-list="false"
          :http-request="uploadFile"
          :before-upload="beforeUpload"
        >
          <el-button type="primary" size="small" icon="el-icon-upload2" v-hasPermi="['blog:file:upload']">上传文件</el-button>
        </el-upload>
      </el-col>
      <el-col :span="8" style="text-align: right;">
        <el-button size="small" icon="el-icon-refresh" @click="loadFiles">刷新</el-button>
      </el-col>
    </el-row>

    <el-table v-loading="loading" :data="filteredFiles">
      <el-table-column prop="fileName" label="文件名" min-width="260" :show-overflow-tooltip="true" />
      <el-table-column prop="fileType" label="类型" width="120">
        <template slot-scope="scope">
          <el-tag size="mini" :type="tagType(scope.row.fileType)">{{ scope.row.fileType }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="fileSizeText" label="大小" width="120" />
      <el-table-column prop="createTime" label="上传时间" width="180">
        <template slot-scope="scope">{{ parseTime(scope.row.createTime) }}</template>
      </el-table-column>
      <el-table-column label="操作" width="280" fixed="right">
        <template slot-scope="scope">
          <el-button size="mini" type="text" @click="copyUrl(scope.row.url)">复制地址</el-button>
          <el-button size="mini" type="text" @click="openUrl(scope.row.url)">预览</el-button>
          <el-button
            size="mini"
            type="text"
            style="color:#f56c6c;"
            @click="remove(scope.row)"
            v-hasPermi="['blog:file:remove']"
          >
            删除
          </el-button>
        </template>
      </el-table-column>
    </el-table>
  </div>
</template>

<script>
import { upload } from '@/api/blog'

const FILE_CACHE_KEY = 'zfy_admin_file_records'

function normalizeType(fileName = '') {
  const lowerName = fileName.toLowerCase()
  if (/\.(png|jpg|jpeg|gif|webp|svg)$/.test(lowerName)) {
    return 'image'
  }
  if (/\.(pdf|doc|docx|xls|xlsx|ppt|pptx|txt|md)$/.test(lowerName)) {
    return 'document'
  }
  return 'other'
}

export default {
  name: 'BlogFileList',
  data() {
    return {
      loading: false,
      query: {
        fileName: '',
        fileType: ''
      },
      fileList: []
    }
  },
  computed: {
    filteredFiles() {
      const name = this.query.fileName.trim().toLowerCase()
      const fileType = this.query.fileType
      return this.fileList.filter(item => {
        const matchName = !name || item.fileName.toLowerCase().includes(name)
        const matchType = !fileType || item.fileType === fileType
        return matchName && matchType
      })
    }
  },
  created() {
    this.loadFiles()
  },
  methods: {
    loadFiles() {
      this.loading = true
      const raw = this.$cache.local.getJSON(FILE_CACHE_KEY) || []
      this.fileList = raw
      this.loading = false
    },
    persistFiles() {
      this.$cache.local.setJSON(FILE_CACHE_KEY, this.fileList)
    },
    beforeUpload(file) {
      const maxSizeMb = 20
      const sizeMb = file.size / 1024 / 1024
      if (sizeMb > maxSizeMb) {
        this.$modal.msgError(`文件大小不能超过 ${maxSizeMb}MB`)
        return false
      }
      return true
    },
    uploadFile(option) {
      upload(option.file).then(response => {
        const data = response.data || {}
        const url = data.url || data.fileName || ''
        const row = {
          id: `${Date.now()}-${Math.random()}`,
          fileName: option.file.name,
          fileType: normalizeType(option.file.name),
          fileSize: option.file.size,
          fileSizeText: this.$modal && this.$modal.fileSizeFormat ? this.$modal.fileSizeFormat(option.file.size) : `${(option.file.size / 1024).toFixed(1)}KB`,
          url,
          createTime: new Date()
        }
        this.fileList.unshift(row)
        this.persistFiles()
        this.$modal.msgSuccess('上传成功')
        option.onSuccess(response)
      }).catch(error => {
        option.onError(error)
      })
    },
    copyUrl(url) {
      if (!url) {
        this.$modal.msgWarning('无可复制的地址')
        return
      }
      navigator.clipboard.writeText(url).then(() => {
        this.$modal.msgSuccess('地址已复制')
      }).catch(() => {
        this.$modal.msgError('复制失败，请手动复制')
      })
    },
    openUrl(url) {
      if (!url) {
        this.$modal.msgWarning('无可预览地址')
        return
      }
      window.open(url, '_blank')
    },
    remove(row) {
      this.$modal.confirm(`确认删除文件 "${row.fileName}" 吗？`).then(() => {
        this.fileList = this.fileList.filter(item => item.id !== row.id)
        this.persistFiles()
        this.$modal.msgSuccess('删除成功')
      }).catch(() => {})
    },
    tagType(fileType) {
      if (fileType === 'image') {
        return 'success'
      }
      if (fileType === 'document') {
        return 'warning'
      }
      return 'info'
    }
  }
}
</script>
