<template>
  <zfy-blog-frame
    active-nav="/"
    :plugin-context="{ articles: relatedArticles, hotArticles: relatedArticles, categories }"
  >
    <div class="zfy-card zfy-content-card" v-loading="loading">
      <template v-if="article">
        <header>
          <h1 class="zfy-page-title">{{ article.articleTitle }}</h1>
          <div class="zfy-post-meta">
            <span><i class="el-icon-user" /> {{ article.authorName }}</span>
            <span><i class="el-icon-time" /> {{ formatDate(article.publishTime) }}</span>
            <span><i class="el-icon-folder" /> {{ article.categoryName }}</span>
            <span><i class="el-icon-view" /> {{ article.viewCount || 0 }}</span>
            <span><i class="el-icon-chat-dot-round" /> {{ article.commentCount || 0 }}</span>
          </div>
        </header>

        <div class="zfy-post-thumb" style="width:100%;height:330px;margin-top:18px;" v-if="article.articleCover">
          <img :src="processImageUrl(article.articleCover)" :alt="article.articleTitle">
        </div>

        <div class="zfy-article-view zfy-article-content" style="margin-top:18px;">
          <mavon-editor
            :value="article.articleContent"
            :toolbars="{}"
            :subfield="false"
            :boxShadow="false"
            :preview="true"
            defaultOpen="preview"
            :editable="false"
            :scrollStyle="true"
            :ishljs="true"
          />
        </div>

        <div class="zfy-card zfy-widget-card" style="margin-top:18px;">
          <div class="zfy-tag-cloud" style="margin-bottom:12px;">
            <el-tag
              v-for="tag in article.tags || []"
              :key="tag.tagId"
              size="mini"
              @click="$router.push(`/blog/tag/${tag.tagId}`)"
            >
              {{ tag.tagName }}
            </el-tag>
          </div>
          <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <el-button :type="isLiked ? 'success' : 'primary'" icon="el-icon-thumb" @click="handleLike">
              {{ isLiked ? '已点赞' : '点赞' }} {{ article.likeCount || 0 }}
            </el-button>
            <el-button :type="isFavorited ? 'warning' : 'default'" icon="el-icon-star-on" @click="handleFavorite">
              {{ isFavorited ? '已收藏' : '收藏' }} {{ article.favoriteCount || 0 }}
            </el-button>
            <el-button icon="el-icon-share" @click="shareArticle">分享</el-button>
          </div>
        </div>

        <div class="zfy-card zfy-widget-card" style="margin-top:18px;" v-if="prevArticle || nextArticle">
          <div class="zfy-simple-list">
            <li v-if="prevArticle">
              <span>上一篇</span>
              <router-link :to="`/blog/article/${prevArticle.articleId}`">{{ prevArticle.articleTitle }}</router-link>
            </li>
            <li v-if="nextArticle">
              <span>下一篇</span>
              <router-link :to="`/blog/article/${nextArticle.articleId}`">{{ nextArticle.articleTitle }}</router-link>
            </li>
          </div>
        </div>
      </template>
    </div>

    <template #sidebar>
      <div class="zfy-card zfy-widget-card">
        <h3 class="zfy-widget-title">相关文章</h3>
        <ul class="zfy-simple-list">
          <li v-for="item in relatedArticles" :key="item.articleId">
            <router-link :to="`/blog/article/${item.articleId}`">{{ item.articleTitle }}</router-link>
            <span>{{ item.viewCount || 0 }}</span>
          </li>
        </ul>
      </div>

      <div class="zfy-card zfy-widget-card">
        <h3 class="zfy-widget-title">分类</h3>
        <ul class="zfy-simple-list">
          <li v-for="category in categories" :key="category.categoryId">
            <router-link :to="`/blog/category/${category.categoryId}`">{{ category.categoryName }}</router-link>
            <span>{{ category.articleCount || 0 }}</span>
          </li>
        </ul>
      </div>
    </template>
  </zfy-blog-frame>
</template>

<script>
import {
  checkFavorited,
  checkLiked,
  favoriteArticle,
  getArticle,
  incrementView,
  likeArticle,
  listArticle,
  listCategory,
  unfavoriteArticle,
  unlikeArticle
} from '@/api/blog'
import { mavonEditor } from 'mavon-editor'
import 'mavon-editor/dist/css/index.css'
import ZfyBlogFrame from './components/ZfyBlogFrame'

export default {
  name: 'BlogArticle',
  components: {
    ZfyBlogFrame,
    mavonEditor
  },
  data() {
    return {
      loading: false,
      article: null,
      prevArticle: null,
      nextArticle: null,
      relatedArticles: [],
      categories: [],
      isLiked: false,
      isFavorited: false
    }
  },
  watch: {
    '$route.params.id': {
      handler() {
        this.loadArticle()
      },
      immediate: true
    }
  },
  created() {
    this.loadCategories()
  },
  methods: {
    loadArticle() {
      const articleId = this.$route.params.id
      if (!articleId) {
        return
      }
      this.loading = true
      getArticle(articleId).then(response => {
        this.article = response.data || null
        if (this.article && this.article.articleContent) {
          this.article.articleContent = this.processImageUrls(this.article.articleContent)
        }
        incrementView(articleId)
        this.loadRelatedArticles()
        this.loadNeighborArticles()
        this.checkLikeState(articleId)
        this.checkFavoriteState(articleId)
      }).finally(() => {
        this.loading = false
      })
    },
    loadRelatedArticles() {
      if (!this.article) {
        return
      }
      listArticle({
        pageNum: 1,
        pageSize: 6,
        articleStatus: '1',
        categoryId: this.article.categoryId,
        excludeId: this.article.articleId
      }).then(response => {
        this.relatedArticles = response.rows || []
      })
    },
    loadNeighborArticles() {
      if (!this.article) {
        return
      }
      listArticle({
        pageNum: 1,
        pageSize: 50,
        articleStatus: '1',
        categoryId: this.article.categoryId
      }).then(response => {
        const list = response.rows || []
        const index = list.findIndex(item => item.articleId === this.article.articleId)
        this.prevArticle = index > 0 ? list[index - 1] : null
        this.nextArticle = index >= 0 && index < list.length - 1 ? list[index + 1] : null
      })
    },
    loadCategories() {
      listCategory({ status: '0' }).then(response => {
        this.categories = response.rows || []
      })
    },
    checkLikeState(articleId) {
      checkLiked(articleId).then(response => {
        this.isLiked = !!(response.data && response.data.liked)
      }).catch(() => {
        this.isLiked = false
      })
    },
    checkFavoriteState(articleId) {
      checkFavorited(articleId).then(response => {
        this.isFavorited = !!(response.data && response.data.favorited)
      }).catch(() => {
        this.isFavorited = false
      })
    },
    handleLike() {
      if (!this.article) {
        return
      }
      const request = this.isLiked ? unlikeArticle(this.article.articleId) : likeArticle(this.article.articleId)
      request.then(() => {
        this.isLiked = !this.isLiked
        this.article.likeCount = Number(this.article.likeCount || 0) + (this.isLiked ? 1 : -1)
      })
    },
    handleFavorite() {
      if (!this.article) {
        return
      }
      const request = this.isFavorited
        ? unfavoriteArticle(this.article.articleId)
        : favoriteArticle(this.article.articleId)
      request.then(() => {
        this.isFavorited = !this.isFavorited
        this.article.favoriteCount = Number(this.article.favoriteCount || 0) + (this.isFavorited ? 1 : -1)
      })
    },
    shareArticle() {
      if (!this.article) {
        return
      }
      if (navigator.share) {
        navigator.share({
          title: this.article.articleTitle,
          url: window.location.href
        })
      } else {
        this.$message.info('请手动复制当前页面链接进行分享。')
      }
    },
    formatDate(value) {
      if (!value) {
        return ''
      }
      return this.parseTime(value, '{y}-{m}-{d} {h}:{i}')
    },
    processImageUrls(content) {
      if (!content) {
        return content
      }
      return content
        .replace(/!\[([^\]]*)\]\(\/profile\//g, '![$1](/dev-api/profile/')
        .replace(/src="\/profile\//g, 'src="/dev-api/profile/')
    },
    processImageUrl(url) {
      if (!url) {
        return url
      }
      if (url.startsWith('/profile/')) {
        return `/dev-api${url}`
      }
      return url
    }
  }
}
</script>
