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
            <el-button
              :type="isLiked ? 'success' : 'primary'"
              icon="el-icon-thumb"
              :loading="likeLoading"
              @click="handleLike"
            >
              {{ isLiked ? '已点赞' : '点赞' }} {{ article.likeCount || 0 }}
            </el-button>
            <el-button
              :type="isFavorited ? 'warning' : 'default'"
              icon="el-icon-star-on"
              :loading="favoriteLoading"
              @click="handleFavorite"
            >
              {{ isFavorited ? '已收藏' : '收藏' }} {{ article.favoriteCount || 0 }}
            </el-button>
            <el-button icon="el-icon-share" @click="shareArticle">分享</el-button>
          </div>
        </div>

        <div class="zfy-card zfy-widget-card zfy-comment-card" style="margin-top:18px;">
          <h3 class="zfy-widget-title">评论 ({{ article.commentCount || 0 }})</h3>

          <el-alert
            v-if="!commentEnabled"
            title="站点已关闭评论功能"
            type="warning"
            :closable="false"
            style="margin-bottom:12px;"
          />

          <div class="comment-form" :class="{ disabled: !commentEnabled }">
            <div v-if="!isLoggedIn" class="comment-form-row">
              <el-input
                v-model="commentForm.userName"
                placeholder="昵称（必填）"
                maxlength="64"
                show-word-limit
              />
              <el-input
                v-model="commentForm.userEmail"
                placeholder="邮箱（选填）"
                maxlength="128"
              />
            </div>

            <div v-else class="comment-user-tip">当前登录用户：{{ currentUserName }}</div>

            <div v-if="replyingComment" class="comment-reply-tip">
              正在回复 @{{ replyingComment.userName }}
              <el-button type="text" @click="cancelReply">取消回复</el-button>
            </div>

            <el-input
              v-model="commentForm.commentContent"
              type="textarea"
              :rows="4"
              :maxlength="2000"
              show-word-limit
              :placeholder="replyingComment ? `回复 @${replyingComment.userName}` : '写下你的评论...'"
            />

            <div class="comment-form-actions">
              <el-button
                type="primary"
                :loading="submitCommentLoading"
                :disabled="!commentEnabled"
                @click="submitComment"
              >发布评论</el-button>
            </div>
          </div>

          <div class="comment-list" v-loading="commentLoading">
            <div v-if="comments.length === 0" class="comment-empty">暂无评论，欢迎抢沙发。</div>

            <div v-for="item in comments" :key="item.commentId" class="comment-item">
              <div class="comment-main">
                <el-avatar :size="36" :src="processImageUrl(item.userAvatar)" />
                <div class="comment-body">
                  <div class="comment-meta">
                    <span class="comment-author">{{ item.userName || '匿名用户' }}</span>
                    <span class="comment-time">{{ formatCommentTime(item.createTime) }}</span>
                  </div>
                  <div class="comment-content">{{ item.commentContent }}</div>
                  <el-button type="text" size="mini" @click="handleReply(item)">回复</el-button>
                </div>
              </div>

              <div v-if="item.children && item.children.length > 0" class="comment-children">
                <div v-for="child in item.children" :key="child.commentId" class="comment-item child">
                  <div class="comment-main">
                    <el-avatar :size="30" :src="processImageUrl(child.userAvatar)" />
                    <div class="comment-body">
                      <div class="comment-meta">
                        <span class="comment-author">{{ child.userName || '匿名用户' }}</span>
                        <span class="comment-time">{{ formatCommentTime(child.createTime) }}</span>
                      </div>
                      <div class="comment-content">{{ child.commentContent }}</div>
                      <el-button type="text" size="mini" @click="handleReply(child)">回复</el-button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
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
  addPublicComment,
  checkFavorited,
  checkLiked,
  favoriteArticle,
  getArticle,
  getConfig,
  incrementView,
  likeArticle,
  listArticle,
  listCategory,
  listPublicComment,
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
      isFavorited: false,
      likeLoading: false,
      favoriteLoading: false,
      comments: [],
      commentLoading: false,
      submitCommentLoading: false,
      commentEnabled: true,
      replyingComment: null,
      commentForm: {
        userName: '',
        userEmail: '',
        commentContent: ''
      }
    }
  },
  computed: {
    isLoggedIn() {
      return !!this.$store.getters.token
    },
    currentUserName() {
      return this.$store.getters.nickName || this.$store.getters.name || '已登录用户'
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
    this.loadCommentConfig()
  },
  methods: {
    loadArticle() {
      const articleId = this.$route.params.id
      if (!articleId) {
        return
      }
      this.loading = true
      this.replyingComment = null
      this.comments = []
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
        this.loadComments()
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
    loadCommentConfig() {
      getConfig('allow_comment').then(response => {
        const value = response && response.data ? response.data.configValue : '1'
        this.commentEnabled = value !== '0'
      }).catch(() => {
        this.commentEnabled = true
      })
    },
    loadComments() {
      if (!this.article || !this.article.articleId) {
        this.comments = []
        return
      }
      this.commentLoading = true
      listPublicComment(this.article.articleId).then(response => {
        this.comments = response.data || []
      }).catch(() => {
        this.comments = []
      }).finally(() => {
        this.commentLoading = false
      })
    },
    checkLikeState(articleId) {
      checkLiked(articleId).then(response => {
        const liked = response && response.data ? response.data.liked : response.liked
        this.isLiked = !!liked
      }).catch(() => {
        this.isLiked = false
      })
    },
    checkFavoriteState(articleId) {
      checkFavorited(articleId).then(response => {
        const favorited = response && response.data ? response.data.favorited : response.favorited
        this.isFavorited = !!favorited
      }).catch(() => {
        this.isFavorited = false
      })
    },
    handleLike() {
      if (!this.article || this.likeLoading) {
        return
      }
      this.likeLoading = true
      const nextLiked = !this.isLiked
      const request = nextLiked ? likeArticle(this.article.articleId) : unlikeArticle(this.article.articleId)
      request.then(() => {
        this.isLiked = nextLiked
        const current = Number(this.article.likeCount || 0)
        this.article.likeCount = Math.max(0, current + (nextLiked ? 1 : -1))
        this.syncArticleCounts()
      }).finally(() => {
        this.likeLoading = false
      })
    },
    handleFavorite() {
      if (!this.article || this.favoriteLoading) {
        return
      }
      this.favoriteLoading = true
      const nextFavorited = !this.isFavorited
      const request = nextFavorited
        ? favoriteArticle(this.article.articleId)
        : unfavoriteArticle(this.article.articleId)
      request.then(() => {
        this.isFavorited = nextFavorited
        const current = Number(this.article.favoriteCount || 0)
        this.article.favoriteCount = Math.max(0, current + (nextFavorited ? 1 : -1))
        this.syncArticleCounts()
      }).finally(() => {
        this.favoriteLoading = false
      })
    },
    syncArticleCounts() {
      if (!this.article || !this.article.articleId) {
        return
      }
      getArticle(this.article.articleId).then(response => {
        const latest = response.data || {}
        this.article.likeCount = Number(latest.likeCount || 0)
        this.article.favoriteCount = Number(latest.favoriteCount || 0)
        this.article.commentCount = Number(latest.commentCount || 0)
      })
    },
    handleReply(comment) {
      this.replyingComment = comment
    },
    cancelReply() {
      this.replyingComment = null
    },
    submitComment() {
      if (!this.article || !this.article.articleId) {
        return
      }
      if (!this.commentEnabled) {
        this.$message.warning('评论功能已关闭')
        return
      }

      const commentContent = (this.commentForm.commentContent || '').trim()
      if (!commentContent) {
        this.$message.warning('请输入评论内容')
        return
      }
      if (!this.isLoggedIn && !(this.commentForm.userName || '').trim()) {
        this.$message.warning('请输入昵称')
        return
      }

      const payload = {
        articleId: this.article.articleId,
        parentId: this.replyingComment ? this.replyingComment.commentId : 0,
        commentContent,
        userName: this.isLoggedIn ? undefined : this.commentForm.userName,
        userEmail: this.isLoggedIn ? undefined : this.commentForm.userEmail
      }

      this.submitCommentLoading = true
      addPublicComment(payload).then(response => {
        const pending = response && response.data ? response.data.pending : response.pending
        this.commentForm.commentContent = ''
        this.replyingComment = null
        this.syncArticleCounts()

        if (pending === true) {
          this.$message.success('评论已提交，等待审核')
        } else {
          this.$message.success('评论发布成功')
          this.loadComments()
        }
      }).finally(() => {
        this.submitCommentLoading = false
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
    formatCommentTime(value) {
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
        return ''
      }
      if (url.startsWith('/profile/')) {
        return `/dev-api${url}`
      }
      return url
    }
  }
}
</script>

<style scoped>
.zfy-comment-card {
  margin-top: 18px;
}

.comment-form.disabled {
  opacity: 0.8;
}

.comment-form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-bottom: 12px;
}

.comment-user-tip {
  margin-bottom: 12px;
  color: #606266;
  font-size: 13px;
}

.comment-reply-tip {
  margin-bottom: 8px;
  color: #606266;
  font-size: 13px;
}

.comment-form-actions {
  margin-top: 12px;
  display: flex;
  justify-content: flex-end;
}

.comment-list {
  margin-top: 16px;
}

.comment-empty {
  color: #909399;
  font-size: 13px;
  padding: 12px 0;
}

.comment-item {
  border-top: 1px solid #ebeef5;
  padding: 14px 0;
}

.comment-main {
  display: flex;
  gap: 10px;
}

.comment-body {
  flex: 1;
}

.comment-meta {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 6px;
}

.comment-author {
  color: #303133;
  font-weight: 600;
}

.comment-time {
  color: #909399;
  font-size: 12px;
}

.comment-content {
  color: #303133;
  line-height: 1.7;
  word-break: break-word;
}

.comment-children {
  margin-left: 46px;
}

.comment-item.child {
  padding: 10px 0;
}

@media (max-width: 768px) {
  .comment-form-row {
    grid-template-columns: 1fr;
  }

  .comment-children {
    margin-left: 18px;
  }
}
</style>