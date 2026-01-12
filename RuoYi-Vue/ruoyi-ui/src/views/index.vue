<template>
  <div class="app-container home">
    <!-- 统计卡片 -->
    <el-row :gutter="20" class="mb20">
      <el-col :xs="24" :sm="12" :md="6" :lg="6" :xl="5">
        <el-card class="stat-card" shadow="hover">
          <div class="stat-content">
            <div class="stat-icon" style="background: #409eff">
              <i class="el-icon-document"></i>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ statistics.totalArticles || 0 }}</div>
              <div class="stat-label">文章总数</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :xs="24" :sm="12" :md="6" :lg="6" :xl="5">
        <el-card class="stat-card" shadow="hover">
          <div class="stat-content">
            <div class="stat-icon" style="background: #67c23a">
              <i class="el-icon-view"></i>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ statistics.totalViews || 0 }}</div>
              <div class="stat-label">总浏览量</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :xs="24" :sm="12" :md="6" :lg="6" :xl="5">
        <el-card class="stat-card" shadow="hover">
          <div class="stat-content">
            <div class="stat-icon" style="background: #e6a23c">
              <i class="el-icon-star-on"></i>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ statistics.totalLikes || 0 }}</div>
              <div class="stat-label">总点赞数</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :xs="24" :sm="12" :md="6" :lg="6" :xl="5">
        <el-card class="stat-card" shadow="hover">
          <div class="stat-content">
            <div class="stat-icon" style="background: #f56c6c">
              <i class="el-icon-chat-dot-round"></i>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ statistics.totalComments || 0 }}</div>
              <div class="stat-label">总评论数</div>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 详细统计 -->
    <el-row :gutter="20" class="mb20">
      <el-col :xs="24" :sm="12" :md="8" :lg="6" :xl="4">
        <div class="detail-stat">
          <div class="detail-label">已发布</div>
          <div class="detail-value published">{{ statistics.publishedArticles || 0 }}</div>
        </div>
      </el-col>
      <el-col :xs="24" :sm="12" :md="8" :lg="6" :xl="4">
        <div class="detail-stat">
          <div class="detail-label">草稿</div>
          <div class="detail-value draft">{{ statistics.draftArticles || 0 }}</div>
        </div>
      </el-col>
      <el-col :xs="24" :sm="12" :md="8" :lg="6" :xl="4">
        <div class="detail-stat">
          <div class="detail-label">已下架</div>
          <div class="detail-value offline">{{ statistics.offlineArticles || 0 }}</div>
        </div>
      </el-col>
      <el-col :xs="24" :sm="12" :md="8" :lg="6" :xl="4">
        <div class="detail-stat">
          <div class="detail-label">分类数</div>
          <div class="detail-value">{{ statistics.totalCategories || 0 }}</div>
        </div>
      </el-col>
      <el-col :xs="24" :sm="12" :md="8" :lg="6" :xl="4">
        <div class="detail-stat">
          <div class="detail-label">标签数</div>
          <div class="detail-value">{{ statistics.totalTags || 0 }}</div>
        </div>
      </el-col>
      <el-col :xs="24" :sm="12" :md="8" :lg="6" :xl="4">
        <div class="detail-stat">
          <div class="detail-label">专题数</div>
          <div class="detail-value">{{ statistics.totalTopics || 0 }}</div>
        </div>
      </el-col>
    </el-row>
  </div>
</template>

<script>
import { getStatistics } from '@/api/blog'

export default {
  name: "Index",
  data() {
    return {
      // 统计数据
      statistics: {
        totalArticles: 0,
        publishedArticles: 0,
        draftArticles: 0,
        offlineArticles: 0,
        totalCategories: 0,
        totalTags: 0,
        totalTopics: 0,
        totalViews: 0,
        totalLikes: 0,
        totalComments: 0
      }
    }
  },
  created() {
    this.loadStatistics()
  },
  methods: {
    loadStatistics() {
      getStatistics().then(response => {
        console.log('统计数据响应:', response)
        this.statistics = response.data
      }).catch(error => {
        console.log('加载统计数据失败:', error)
      })
    }
  }
}
</script>

<style scoped lang="scss">
.home {
  font-family: "open sans", "Helvetica Neue", Helvetica, Arial, sans-serif;
  font-size: 13px;
  color: #676a6c;
  overflow-x: hidden;

  // 统计卡片样式
  .stat-card {
    margin-bottom: 20px;

    .stat-content {
      display: flex;
      align-items: center;
      padding: 10px;

      .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;

        i {
          font-size: 30px;
          color: #fff;
        }
      }

      .stat-info {
        flex: 1;

        .stat-value {
          font-size: 24px;
          font-weight: bold;
          color: #303133;
          margin-bottom: 5px;
        }

        .stat-label {
          font-size: 14px;
          color: #909399;
        }
      }
    }
  }

  // 详细统计样式
  .detail-stat {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
    transition: all 0.3s;

    &:hover {
      transform: translateY(-5px);
      box-shadow: 0 4px 20px 0 rgba(0, 0, 0, 0.15);
    }

    .detail-label {
      font-size: 14px;
      color: #909399;
      margin-bottom: 10px;
    }

    .detail-value {
      font-size: 28px;
      font-weight: bold;
      color: #303133;

      &.published {
        color: #67c23a;
      }

      &.draft {
        color: #e6a23c;
      }

      &.offline {
        color: #f56c6c;
      }
    }
  }

  .mb20 {
    margin-bottom: 20px;
  }
}
</style>

