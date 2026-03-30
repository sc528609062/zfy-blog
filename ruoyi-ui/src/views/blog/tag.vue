<template>
  <zfy-blog-frame
    active-nav="/blog/tag"
    :plugin-context="{ tags, articles }"
  >
    <div class="zfy-card zfy-content-card">
      <h2 class="zfy-page-title">标签云</h2>
      <div class="zfy-tag-cloud">
        <el-tag
          v-for="tag in tags"
          :key="tag.tagId"
          :type="resolveTagType(tag.articleCount)"
          :size="resolveTagSize(tag.articleCount)"
          style="cursor:pointer;"
          @click="$router.push(`/blog/tag/${tag.tagId}`)"
        >
          {{ tag.tagName }} ({{ tag.articleCount || 0 }})
        </el-tag>
      </div>
      <el-empty v-if="tags.length === 0" description="暂无标签" />
    </div>

    <template #sidebar>
      <div class="zfy-card zfy-widget-card">
        <h3 class="zfy-widget-title">标签统计</h3>
        <div class="zfy-stat-grid">
          <div class="zfy-stat-item">
            <span>标签数</span>
            <strong>{{ tags.length }}</strong>
          </div>
          <div class="zfy-stat-item">
            <span>关联数</span>
            <strong>{{ totalArticles }}</strong>
          </div>
          <div class="zfy-stat-item">
            <span>热门标签</span>
            <strong>{{ hottestTag }}</strong>
          </div>
        </div>
      </div>
    </template>
  </zfy-blog-frame>
</template>

<script>
import { listArticle, listTag } from '@/api/blog'
import ZfyBlogFrame from './components/ZfyBlogFrame'

export default {
  name: 'BlogTag',
  components: {
    ZfyBlogFrame
  },
  data() {
    return {
      tags: [],
      articles: []
    }
  },
  computed: {
    totalArticles() {
      return this.tags.reduce((sum, item) => sum + Number(item.articleCount || 0), 0)
    },
    hottestTag() {
      if (!this.tags.length) {
        return '-'
      }
      const sorted = this.tags.slice().sort((a, b) => Number(b.articleCount || 0) - Number(a.articleCount || 0))
      return sorted[0].tagName
    }
  },
  created() {
    this.loadTags()
    this.loadArticles()
  },
  methods: {
    loadTags() {
      listTag({ status: '0' }).then(response => {
        this.tags = response.rows || []
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
    },
    resolveTagSize(count) {
      const value = Number(count || 0)
      if (value > 12) {
        return 'medium'
      }
      return 'mini'
    },
    resolveTagType(count) {
      const value = Number(count || 0)
      if (value >= 16) {
        return 'danger'
      }
      if (value >= 10) {
        return 'warning'
      }
      if (value >= 6) {
        return 'success'
      }
      return 'info'
    }
  }
}
</script>
