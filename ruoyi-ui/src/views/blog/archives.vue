<template>
  <zfy-blog-frame
    active-nav="/blog/archives"
    :plugin-context="{ archives, articles }"
  >
    <div class="zfy-card zfy-content-card">
      <h2 class="zfy-page-title">文章归档</h2>
      <div v-if="sortedYears.length">
        <div class="zfy-card zfy-widget-card" style="margin-bottom:12px;" v-for="year in sortedYears" :key="year">
          <h3 class="zfy-widget-title">{{ year }} 年</h3>
          <ul class="zfy-simple-list">
            <li v-for="item in archives[year]" :key="item.articleId">
              <router-link :to="`/blog/article/${item.articleId}`">
                {{ item.articleTitle }}
              </router-link>
              <span>{{ formatDate(item.publishTime, '{m}-{d}') }}</span>
            </li>
          </ul>
        </div>
      </div>
      <el-empty v-else description="暂无归档数据" />
    </div>

    <template #sidebar>
      <div class="zfy-card zfy-widget-card">
        <h3 class="zfy-widget-title">归档统计</h3>
        <div class="zfy-stat-grid">
          <div class="zfy-stat-item">
            <span>文章数</span>
            <strong>{{ totalArticles }}</strong>
          </div>
          <div class="zfy-stat-item">
            <span>年份</span>
            <strong>{{ sortedYears.length }}</strong>
          </div>
          <div class="zfy-stat-item">
            <span>跨度</span>
            <strong>{{ yearSpan }}</strong>
          </div>
        </div>
      </div>
    </template>
  </zfy-blog-frame>
</template>

<script>
import { listArticle } from '@/api/blog'
import ZfyBlogFrame from './components/ZfyBlogFrame'

export default {
  name: 'BlogArchives',
  components: {
    ZfyBlogFrame
  },
  data() {
    return {
      articles: [],
      archives: {}
    }
  },
  computed: {
    totalArticles() {
      return this.articles.length
    },
    sortedYears() {
      return Object.keys(this.archives).sort((a, b) => Number(b) - Number(a))
    },
    yearSpan() {
      if (!this.sortedYears.length) {
        return '-'
      }
      if (this.sortedYears.length === 1) {
        return this.sortedYears[0]
      }
      return `${this.sortedYears[this.sortedYears.length - 1]}-${this.sortedYears[0]}`
    }
  },
  created() {
    this.loadArticles()
  },
  methods: {
    loadArticles() {
      listArticle({
        pageNum: 1,
        pageSize: 1000,
        articleStatus: '1'
      }).then(response => {
        this.articles = response.rows || []
        this.groupArchives()
      })
    },
    groupArchives() {
      const map = {}
      this.articles.forEach(item => {
        if (!item.publishTime) {
          return
        }
        const year = String(new Date(item.publishTime).getFullYear())
        if (!map[year]) {
          map[year] = []
        }
        map[year].push(item)
      })
      Object.keys(map).forEach(year => {
        map[year] = map[year].sort((a, b) => new Date(b.publishTime) - new Date(a.publishTime))
      })
      this.archives = map
    },
    formatDate(value, format = '{y}-{m}-{d}') {
      if (!value) {
        return ''
      }
      return this.parseTime(value, format)
    }
  }
}
</script>
