<template>
  <zfy-blog-frame
    active-nav="/"
    :plugin-context="{ articles: allArticles, hotArticles: allArticles, categories, tags }"
  >
    <div class="zfy-post-list">
      <article
        v-for="article in topArticles"
        :key="`top-${article.articleId}`"
        class="zfy-card zfy-post-item"
      >
        <div class="zfy-post-thumb" v-if="article.articleCover">
          <img :src="processImageUrl(article.articleCover)" :alt="article.articleTitle">
        </div>
        <div class="zfy-post-body">
          <div class="zfy-post-meta">
            <el-tag size="mini" type="danger">置顶</el-tag>
            <span>{{ formatDate(article.publishTime) }}</span>
            <span>{{ article.categoryName }}</span>
          </div>
          <h2 class="zfy-post-title">
            <router-link :to="`/blog/article/${article.articleId}`">{{ article.articleTitle }}</router-link>
          </h2>
          <p class="zfy-post-summary">{{ renderSummary(article.articleSummary, article) }}</p>
          <div class="zfy-post-footer">
            <span><i class="el-icon-view" /> {{ article.viewCount || 0 }}</span>
            <span><i class="el-icon-chat-dot-round" /> {{ article.commentCount || 0 }}</span>
            <span><i class="el-icon-thumb" /> {{ article.likeCount || 0 }}</span>
          </div>
        </div>
      </article>

      <article
        v-for="article in normalArticles"
        :key="article.articleId"
        class="zfy-card zfy-post-item"
      >
        <div class="zfy-post-thumb" v-if="article.articleCover">
          <img :src="processImageUrl(article.articleCover)" :alt="article.articleTitle">
        </div>
        <div class="zfy-post-body">
          <div class="zfy-post-meta">
            <span>{{ formatDate(article.publishTime) }}</span>
            <span>{{ article.categoryName }}</span>
            <el-tag :type="getStatusType(article.articleStatus)" size="mini">{{ getStatusText(article.articleStatus) }}</el-tag>
          </div>
          <h2 class="zfy-post-title">
            <router-link :to="`/blog/article/${article.articleId}`">{{ article.articleTitle }}</router-link>
          </h2>
          <p class="zfy-post-summary">{{ renderSummary(article.articleSummary, article) }}</p>
          <div class="zfy-post-footer">
            <span><i class="el-icon-view" /> {{ article.viewCount || 0 }}</span>
            <span><i class="el-icon-chat-dot-round" /> {{ article.commentCount || 0 }}</span>
            <span><i class="el-icon-thumb" /> {{ article.likeCount || 0 }}</span>
          </div>
        </div>
      </article>

      <div class="zfy-pagination" v-if="total > 0">
        <el-pagination
          background
          layout="prev, pager, next"
          :total="total"
          :page-size="pageSize"
          :current-page="pageNum"
          @current-change="handlePageChange"
        />
      </div>
    </div>

    <template #sidebar>
      <div class="zfy-card zfy-widget-card">
        <h3 class="zfy-widget-title">站长信息</h3>
        <p>{{ authorName }}</p>
        <p class="zfy-plugin-desc">聚焦技术与产品设计，持续输出高质量内容。</p>
      </div>

      <div class="zfy-card zfy-widget-card">
        <h3 class="zfy-widget-title">分类目录</h3>
        <ul class="zfy-simple-list">
          <li v-for="category in categories" :key="category.categoryId">
            <router-link :to="`/blog/category/${category.categoryId}`">{{ category.categoryName }}</router-link>
            <span>{{ category.articleCount || 0 }}</span>
          </li>
        </ul>
      </div>

      <div class="zfy-card zfy-widget-card">
        <h3 class="zfy-widget-title">标签云</h3>
        <div class="zfy-tag-cloud">
          <el-tag
            v-for="tag in tags"
            :key="tag.tagId"
            size="mini"
            @click="$router.push(`/blog/tag/${tag.tagId}`)"
          >
            {{ tag.tagName }}
          </el-tag>
        </div>
      </div>
    </template>
  </zfy-blog-frame>
</template>

<script>
import { getConfig, listArticle, listCategory, listTag } from '@/api/blog'
import ZfyBlogFrame from './components/ZfyBlogFrame'

export default {
  name: 'BlogIndex',
  components: {
    ZfyBlogFrame
  },
  data() {
    return {
      pageNum: 1,
      pageSize: 10,
      total: 0,
      allArticles: [],
      topArticles: [],
      normalArticles: [],
      categories: [],
      tags: [],
      authorName: '博主'
    }
  },
  created() {
    this.loadPageSize()
    this.loadArticles()
    this.loadCategories()
    this.loadTags()
  },
  methods: {
    async loadPageSize() {
      try {
        const response = await getConfig('posts_per_page')
        const value = response && response.data ? Number(response.data.configValue) : 10
        this.pageSize = value > 0 ? value : 10
      } catch (error) {
        this.pageSize = 10
      }
    },
    loadArticles() {
      listArticle({
        pageNum: this.pageNum,
        pageSize: this.pageSize,
        articleStatus: '1'
      }).then(response => {
        const rows = response.rows || []
        const filteredRows = this.$zfyPluginRuntime
          ? this.$zfyPluginRuntime.applyFilters('blog:home:articles', rows, { route: this.$route.path })
          : rows
        this.allArticles = filteredRows
        this.topArticles = filteredRows.filter(item => item.isTop === '1')
        this.normalArticles = filteredRows.filter(item => item.isTop !== '1')
        this.total = response.total || filteredRows.length
      })
    },
    loadCategories() {
      listCategory({ status: '0' }).then(response => {
        this.categories = response.rows || []
      })
    },
    loadTags() {
      listTag({ status: '0' }).then(response => {
        this.tags = response.rows || []
      })
    },
    handlePageChange(pageNum) {
      this.pageNum = pageNum
      this.loadArticles()
    },
    formatDate(value) {
      if (!value) {
        return ''
      }
      return this.parseTime(value, '{y}-{m}-{d}')
    },
    getStatusText(status) {
      const map = {
        '0': '草稿',
        '1': '已发布',
        '2': '已下线'
      }
      return map[status] || '未知'
    },
    getStatusType(status) {
      const map = {
        '0': 'info',
        '1': 'success',
        '2': 'danger'
      }
      return map[status] || 'info'
    },
    processImageUrl(url) {
      if (!url) {
        return url
      }
      if (url.startsWith('/profile/')) {
        return `/dev-api${url}`
      }
      return url
    },
    renderSummary(summary, article) {
      if (!this.$zfyPluginRuntime) {
        return summary || '暂无摘要'
      }
      return this.$zfyPluginRuntime.applyFilters('blog:article:summary', summary || '', { article })
    }
  }
}
</script>
