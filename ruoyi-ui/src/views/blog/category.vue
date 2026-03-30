<template>
  <zfy-blog-frame
    active-nav="/blog/category"
    :plugin-context="{ categories, articles }"
  >
    <div class="zfy-card zfy-content-card">
      <h2 class="zfy-page-title">文章分类</h2>
      <div class="zfy-post-list">
        <article class="zfy-card zfy-content-card" v-for="category in categories" :key="category.categoryId">
          <div class="zfy-post-meta">
            <el-tag type="info" size="mini">分类</el-tag>
            <span>{{ category.articleCount || 0 }} 篇文章</span>
          </div>
          <h3 class="zfy-post-title" style="font-size:20px;">
            <router-link :to="`/blog/category/${category.categoryId}`">{{ category.categoryName }}</router-link>
          </h3>
          <p class="zfy-post-summary">{{ category.categoryDesc || '暂无分类描述' }}</p>
        </article>
      </div>
      <el-empty v-if="categories.length === 0" description="暂无分类" />
    </div>

    <template #sidebar>
      <div class="zfy-card zfy-widget-card">
        <h3 class="zfy-widget-title">分类统计</h3>
        <div class="zfy-stat-grid">
          <div class="zfy-stat-item">
            <span>分类数</span>
            <strong>{{ categories.length }}</strong>
          </div>
          <div class="zfy-stat-item">
            <span>文章数</span>
            <strong>{{ totalArticles }}</strong>
          </div>
          <div class="zfy-stat-item">
            <span>平均数</span>
            <strong>{{ averageArticles }}</strong>
          </div>
        </div>
      </div>
    </template>
  </zfy-blog-frame>
</template>

<script>
import { listArticle, listCategory } from '@/api/blog'
import ZfyBlogFrame from './components/ZfyBlogFrame'

export default {
  name: 'BlogCategory',
  components: {
    ZfyBlogFrame
  },
  data() {
    return {
      categories: [],
      articles: []
    }
  },
  computed: {
    totalArticles() {
      return this.categories.reduce((sum, item) => sum + Number(item.articleCount || 0), 0)
    },
    averageArticles() {
      if (!this.categories.length) {
        return 0
      }
      return Math.round((this.totalArticles / this.categories.length) * 10) / 10
    }
  },
  created() {
    this.loadCategories()
    this.loadArticles()
  },
  methods: {
    loadCategories() {
      listCategory({ status: '0' }).then(response => {
        this.categories = response.rows || []
      })
    },
    loadArticles() {
      listArticle({
        pageNum: 1,
        pageSize: 100,
        articleStatus: '1'
      }).then(response => {
        this.articles = response.rows || []
      })
    }
  }
}
</script>
