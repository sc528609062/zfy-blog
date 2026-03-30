<template>
  <div class="app-container">
    <el-card shadow="never">
      <div slot="header" style="display:flex;justify-content:space-between;align-items:center;">
        <span>导航菜单配置</span>
        <el-button type="primary" size="mini" @click="addRow" v-hasPermi="['blog:menu:config']">新增菜单</el-button>
      </div>

      <el-table :data="menuList">
        <el-table-column label="标题" min-width="180">
          <template slot-scope="scope">
            <el-input v-model="scope.row.title" size="mini" />
          </template>
        </el-table-column>
        <el-table-column label="路径" min-width="220">
          <template slot-scope="scope">
            <el-input v-model="scope.row.path" size="mini" />
          </template>
        </el-table-column>
        <el-table-column label="排序" width="120">
          <template slot-scope="scope">
            <el-input-number v-model="scope.row.sort" size="mini" :min="0" :max="999" />
          </template>
        </el-table-column>
        <el-table-column label="启用" width="100">
          <template slot-scope="scope">
            <el-switch v-model="scope.row.enabled" />
          </template>
        </el-table-column>
        <el-table-column label="操作" width="120">
          <template slot-scope="scope">
            <el-button type="text" size="mini" style="color:#f56c6c;" @click="remove(scope.$index)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <div style="margin-top:14px;">
        <el-button type="primary" size="small" @click="save" v-hasPermi="['blog:menu:config']">保存菜单配置</el-button>
      </div>
    </el-card>
  </div>
</template>

<script>
const DEFAULT_MENU = [
  { title: '首页', path: '/', sort: 1, enabled: true },
  { title: '分类', path: '/blog/category', sort: 2, enabled: true },
  { title: '标签', path: '/blog/tag', sort: 3, enabled: true },
  { title: '归档', path: '/blog/archives', sort: 4, enabled: true },
  { title: '关于', path: '/blog/about', sort: 5, enabled: true }
]

export default {
  name: 'BlogThemeMenu',
  data() {
    return {
      menuList: []
    }
  },
  async created() {
    this.menuList = await this.$zfyConfigCenter.getJson('zfy_menu_settings', DEFAULT_MENU)
  },
  methods: {
    addRow() {
      this.menuList.push({ title: '', path: '', sort: this.menuList.length + 1, enabled: true })
    },
    remove(index) {
      this.menuList.splice(index, 1)
    },
    async save() {
      await this.$zfyConfigCenter.save('zfy_menu_settings', this.menuList, {
        group: 'theme',
        desc: 'ZFY导航菜单设置'
      })
      this.$modal.msgSuccess('菜单设置已保存')
    }
  }
}
</script>
