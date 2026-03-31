<template>
  <div class="app-container">
    <el-row :gutter="10" class="mb8">
      <el-col :span="8">
        <el-input v-model="draft.title" size="small" placeholder="消息标题" />
      </el-col>
      <el-col :span="8">
        <el-input v-model="draft.receiver" size="small" placeholder="接收人（默认全站用户）" />
      </el-col>
      <el-col :span="8">
        <el-button type="primary" size="small" icon="el-icon-s-promotion" @click="sendMessage" v-hasPermi="['blog:message:send']">
          发送消息
        </el-button>
      </el-col>
    </el-row>
    <el-input type="textarea" :rows="3" v-model="draft.content" placeholder="请输入消息内容" style="margin-bottom:14px;" />

    <el-table :data="messageList">
      <el-table-column prop="messageId" label="ID" width="80" />
      <el-table-column prop="title" label="标题" min-width="180" />
      <el-table-column prop="content" label="内容" min-width="260" :show-overflow-tooltip="true" />
      <el-table-column prop="receiver" label="接收人" width="140" />
      <el-table-column prop="sendTime" label="发送时间" width="180">
        <template slot-scope="scope">{{ parseTime(scope.row.sendTime) }}</template>
      </el-table-column>
      <el-table-column label="操作" width="120">
        <template slot-scope="scope">
          <el-button size="mini" type="text" style="color:#f56c6c;" @click="remove(scope.row)" v-hasPermi="['blog:message:remove']">删除</el-button>
        </template>
      </el-table-column>
    </el-table>
  </div>
</template>

<script>
const STORE_KEY = 'zfy_message_records'

export default {
  name: 'BlogMessageList',
  data() {
    return {
      draft: {
        title: '',
        receiver: '',
        content: ''
      },
      messageList: []
    }
  },
  async created() {
    this.messageList = await this.$zfyConfigCenter.getJson(STORE_KEY, [])
  },
  methods: {
    async persist() {
      await this.$zfyConfigCenter.save(STORE_KEY, this.messageList, {
        group: 'message',
        desc: '站内消息记录'
      })
    },
    async sendMessage() {
      if (!this.draft.title || !this.draft.content) {
        this.$modal.msgWarning('请填写标题和内容')
        return
      }
      const maxId = this.messageList.reduce((max, item) => Math.max(max, Number(item.messageId || 0)), 0)
      this.messageList.unshift({
        messageId: maxId + 1,
        title: this.draft.title,
        content: this.draft.content,
        receiver: this.draft.receiver || '全站用户',
        sendTime: new Date()
      })
      await this.persist()
      this.draft = { title: '', receiver: '', content: '' }
      this.$modal.msgSuccess('消息已发送')
    },
    remove(row) {
      this.$modal.confirm(`确认删除消息 "${row.title}" 吗？`).then(async () => {
        this.messageList = this.messageList.filter(item => item.messageId !== row.messageId)
        await this.persist()
        this.$modal.msgSuccess('删除成功')
      }).catch(() => {})
    }
  }
}
</script>
