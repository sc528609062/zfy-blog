<template>
  <div class="app-container">
    <el-form :model="queryParams" ref="queryForm" size="small" :inline="true" v-show="showSearch" label-width="68px">
      <el-form-item label="文章标题" prop="articleTitle">
        <el-input
          v-model="queryParams.articleTitle"
          placeholder="请输入文章标题"
          clearable
          @keyup.enter.native="handleQuery"
        />
      </el-form-item>
      <el-form-item label="分类" prop="categoryId">
        <el-select v-model="queryParams.categoryId" placeholder="请选择分类" clearable>
          <el-option
            v-for="item in categoryList"
            :key="item.categoryId"
            :label="item.categoryName"
            :value="item.categoryId"
          />
        </el-select>
      </el-form-item>
      <el-form-item label="标签" prop="tagId">
        <el-select v-model="queryParams.tagId" placeholder="请选择标签" clearable>
          <el-option
            v-for="item in tagList"
            :key="item.tagId"
            :label="item.tagName"
            :value="item.tagId"
          />
        </el-select>
      </el-form-item>
      <el-form-item label="状态" prop="articleStatus">
        <el-select v-model="queryParams.articleStatus" placeholder="请选择状态" clearable>
          <el-option label="草稿" value="0" />
          <el-option label="已发布" value="1" />
          <el-option label="已下架" value="2" />
        </el-select>
      </el-form-item>
      <el-form-item label="作者" prop="authorName">
        <el-input
          v-model="queryParams.authorName"
          placeholder="请输入作者"
          clearable
          @keyup.enter.native="handleQuery"
        />
      </el-form-item>
      <el-form-item label="发布时间" prop="publishTime">
        <el-date-picker
          v-model="queryParams.publishTime"
          type="daterange"
          range-separator="-"
          start-placeholder="开始日期"
          end-placeholder="结束日期"
          value-format="yyyy-MM-dd"
          clearable
        />
      </el-form-item>
      <el-form-item>
        <el-button type="primary" icon="el-icon-search" size="mini" @click="handleQuery">搜索</el-button>
        <el-button icon="el-icon-refresh" size="mini" @click="resetQuery">重置</el-button>
      </el-form-item>
    </el-form>

    <el-row :gutter="10" class="mb8">
      <el-col :span="1.5">
        <el-button
          type="primary"
          plain
          icon="el-icon-plus"
          size="mini"
          @click="handleAdd"
          v-hasPermi="['blog:article:add']"
        >写文章</el-button>
      </el-col>
      <el-col :span="1.5">
        <el-button
          type="success"
          plain
          icon="el-icon-upload2"
          size="mini"
          :disabled="multiple"
          :loading="publishLoading"
          @click="handlePublish"
          v-hasPermi="['blog:article:edit']"
        >发布</el-button>
      </el-col>
      <el-col :span="1.5">
        <el-button
          type="warning"
          plain
          icon="el-icon-download"
          size="mini"
          :disabled="multiple"
          :loading="offlineLoading"
          @click="handleUnPublish"
          v-hasPermi="['blog:article:edit']"
        >下架</el-button>
      </el-col>
      <el-col :span="1.5">
        <el-button
          type="danger"
          plain
          icon="el-icon-delete"
          size="mini"
          :disabled="multiple"
          :loading="deleteLoading"
          @click="handleDelete"
          v-hasPermi="['blog:article:remove']"
        >删除</el-button>
      </el-col>
      <right-toolbar :showSearch.sync="showSearch" @queryTable="getList"></right-toolbar>
    </el-row>

    <el-table v-loading="loading" :data="articleList" @selection-change="handleSelectionChange">
      <el-table-column type="selection" width="55" align="center" />
      <el-table-column label="封面" align="center" width="100">
        <template slot-scope="scope">
          <el-image
            v-if="scope.row.articleCover"
            :src="processImageUrl(scope.row.articleCover)"
            :preview-src-list="[processImageUrl(scope.row.articleCover)]"
            fit="cover"
            style="width: 60px; height: 40px; border-radius: 4px;"
          >
          </el-image>
          <span v-else>-</span>
        </template>
      </el-table-column>
      <el-table-column label="文章标题" align="left" prop="articleTitle" min-width="200" :show-overflow-tooltip="true" />
      <el-table-column label="作者" align="center" prop="authorName" width="100" />
      <el-table-column label="状态" align="center" prop="articleStatus" width="100">
        <template slot-scope="scope">
          <el-tag :type="getStatusType(scope.row.articleStatus)" size="mini">
            {{ getStatusText(scope.row.articleStatus) }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column label="阅读 · 点赞 · 收藏" align="center" width="150">
        <template slot-scope="scope">
          <el-tag size="mini" type="info" style="margin-right: 5px;">{{ scope.row.viewCount || 0 }}</el-tag>
          <el-tag size="mini" type="warning" style="margin-right: 5px;">{{ scope.row.likeCount || 0 }}</el-tag>
          <el-tag size="mini" type="success">{{ scope.row.favoriteCount || 0 }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column label="分类目录" align="center" prop="categoryName" width="120" />
      <el-table-column label="标签" align="center" width="150">
        <template slot-scope="scope">
          <el-tag
            v-for="tag in (scope.row.tags || [])"
            :key="tag.tagId"
            size="mini"
            style="margin-right: 5px; margin-bottom: 5px;"
            v-if="tag"
          >{{ tag.tagName }}</el-tag>
          <span v-if="!scope.row.tags || scope.row.tags.length === 0 || !scope.row.tags.find(t => t)">-</span>
        </template>
      </el-table-column>
      <el-table-column label="专题" align="center" prop="topicName" width="120">
        <template slot-scope="scope">
          <el-tag v-if="scope.row.topicName" size="mini">{{ scope.row.topicName }}</el-tag>
          <span v-else>-</span>
        </template>
      </el-table-column>
      <el-table-column label="评论" align="center" prop="commentCount" width="100">
        <template slot-scope="scope">
          <el-badge :value="scope.row.commentCount || 0" class="item">
            <el-tag size="mini">{{ scope.row.commentCount || 0 }}</el-tag>
          </el-badge>
        </template>
      </el-table-column>
      <el-table-column label="日期" align="center" prop="publishTime" width="180">
        <template slot-scope="scope">
          <span>{{ parseTime(scope.row.publishTime, '{y}-{m}-{d}') }}</span>
        </template>
      </el-table-column>
      <el-table-column label="操作" align="center" class-name="small-padding fixed-width" width="200">
        <template slot-scope="scope">
          <el-button
            size="mini"
            type="text"
            icon="el-icon-edit"
            @click="handleUpdate(scope.row)"
            v-hasPermi="['blog:article:edit']"
          >编辑</el-button>
          <el-button
            size="mini"
            type="text"
            icon="el-icon-view"
            @click="handleView(scope.row)"
          >查看</el-button>
          <el-button
            size="mini"
            type="text"
            icon="el-icon-delete"
            @click="handleDelete(scope.row)"
            v-hasPermi="['blog:article:remove']"
          >删除</el-button>
        </template>
      </el-table-column>
    </el-table>

    <pagination
      v-show="total>0"
      :total="total"
      :page.sync="queryParams.pageNum"
      :limit.sync="queryParams.pageSize"
      @pagination="getList"
    />
  </div>
</template>

<script>
import { listArticle, delArticle, listCategory, listTag, updateArticleStatus } from '@/api/blog'

export default {
  name: 'BlogArticleList',
  data() {
    return {
      loading: true,
      publishLoading: false,
      offlineLoading: false,
      deleteLoading: false,
      ids: [],
      single: true,
      multiple: true,
      showSearch: true,
      total: 0,
      articleList: [],
      categoryList: [],
      tagList: [],
      queryParams: {
        pageNum: 1,
        pageSize: 10,
        articleTitle: null,
        categoryId: null,
        tagId: null,
        articleStatus: null,
        authorName: null,
        beginTime: null,
        endTime: null
      }
    }
  },
  created() {
    this.getList()
    this.getCategoryList()
    this.getTagList()
  },
  methods: {
    getList() {
      this.loading = true
      // 处理日期范围
      if (this.queryParams.publishTime && this.queryParams.publishTime.length === 2) {
        this.queryParams.beginTime = this.queryParams.publishTime[0]
        this.queryParams.endTime = this.queryParams.publishTime[1]
      } else {
        this.queryParams.beginTime = null
        this.queryParams.endTime = null
      }

      listArticle(this.queryParams).then(response => {
        this.articleList = response.rows
        this.total = response.total
        this.loading = false
      })
    },
    getCategoryList() {
      listCategory().then(response => {
        this.categoryList = response.data
      })
    },
    getTagList() {
      listTag().then(response => {
        this.tagList = response.data
      })
    },
    handleQuery() {
      this.queryParams.pageNum = 1
      this.getList()
    },
    resetQuery() {
      this.queryParams.publishTime = null
      this.resetForm('queryForm')
      this.handleQuery()
    },
    handleSelectionChange(selection) {
      this.ids = selection.map(item => item.articleId)
      this.single = selection.length !== 1
      this.multiple = !selection.length
    },
    handleAdd() {
      this.$router.push('/blog/article/write')
    },
    handleUpdate(row) {
      this.$router.push('/blog/article/write?id=' + row.articleId)
    },
    handleView(row) {
      this.$router.push('/blog/article/' + row.articleId)
    },
    handlePublish() {
      const articleIds = this.ids
      this.$modal.confirm('确认要发布选中的 ' + articleIds.length + ' 篇文章吗？').then(() => {
        this.publishLoading = true
        return updateArticleStatus(articleIds, '1')
      }).then(() => {
        this.$modal.msgSuccess('发布成功')
        this.getList()
      }).catch(() => {
        if (this.publishLoading) {
          this.$modal.msgError('发布失败')
        }
      }).finally(() => {
        this.publishLoading = false
      })
    },
    handleUnPublish() {
      const articleIds = this.ids
      this.$modal.confirm('确认要下架选中的 ' + articleIds.length + ' 篇文章吗？').then(() => {
        this.offlineLoading = true
        return updateArticleStatus(articleIds, '2')
      }).then(() => {
        this.$modal.msgSuccess('下架成功')
        this.getList()
      }).catch(() => {
        if (this.offlineLoading) {
          this.$modal.msgError('下架失败')
        }
      }).finally(() => {
        this.offlineLoading = false
      })
    },
    handleDelete(row) {
      const articleIds = row.articleId || this.ids
      const title = row.articleTitle || '选中的文章'
      this.$modal.confirm('确认要删除"' + title + '"吗？删除后将无法恢复！').then(() => {
        this.deleteLoading = true
        return delArticle(articleIds)
      }).then(() => {
        this.$modal.msgSuccess('删除成功')
        this.getList()
      }).catch(() => {
        if (this.deleteLoading) {
          this.$modal.msgError('删除失败')
        }
      }).finally(() => {
        this.deleteLoading = false
      })
    },
    // 处理图片URL，添加 /dev-api 前缀
    processImageUrl(url) {
      if (!url) return url
      // 如果路径以 /profile/ 开头，添加 /dev-api 前缀
      if (url.startsWith('/profile/')) {
        return '/dev-api' + url
      }
      return url
    },
    // 获取状态文本
    getStatusText(status) {
      const statusMap = {
        '0': '草稿',
        '1': '已发布',
        '2': '已下架'
      }
      return statusMap[status] || '未知'
    },
    // 获取状态标签类型
    getStatusType(status) {
      const typeMap = {
        '0': 'info',
        '1': 'success',
        '2': 'danger'
      }
      return typeMap[status] || 'info'
    }
  }
}
</script>
