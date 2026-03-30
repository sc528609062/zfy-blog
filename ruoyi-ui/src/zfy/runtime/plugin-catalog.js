const zfyNoticePlugin = {
  id: 'zfy-notice-board',
  name: '公告看板',
  description: '在侧边栏展示可配置公告信息。',
  version: '1.0.0',
  author: 'zfy',
  defaultEnabled: true,
  settings: [
    { key: 'title', label: '标题', type: 'text', default: '站点公告' },
    { key: 'content', label: '内容', type: 'textarea', default: '欢迎访问 zfy-blog，祝你阅读愉快。' }
  ],
  widgets: [
    {
      id: 'zfy-notice-widget',
      area: 'sidebar.global',
      order: 10,
      render(context, settings) {
        return {
          type: 'text',
          title: settings.title,
          content: settings.content
        }
      }
    }
  ]
}

const zfyHotPostsPlugin = {
  id: 'zfy-hot-posts',
  name: '热门文章',
  description: '根据阅读量在侧边栏展示热门文章。',
  version: '1.0.0',
  author: 'zfy',
  defaultEnabled: true,
  settings: [
    { key: 'title', label: '标题', type: 'text', default: '热门文章' },
    { key: 'limit', label: '数量', type: 'number', default: 5, min: 3, max: 15, step: 1 }
  ],
  widgets: [
    {
      id: 'zfy-hot-posts-widget',
      area: 'sidebar.global',
      order: 30,
      render(context, settings) {
        const limit = Number(settings.limit || 5)
        const sourceList = (context.hotArticles || context.articles || []).slice()
        const sorted = sourceList.sort((a, b) => (b.viewCount || 0) - (a.viewCount || 0)).slice(0, limit)
        const items = sorted.map(item => ({
          text: item.articleTitle,
          to: `/blog/article/${item.articleId}`,
          meta: `${item.viewCount || 0} 阅读`
        }))
        return {
          type: 'list',
          title: settings.title,
          items
        }
      }
    }
  ]
}

const zfyExcerptPlugin = {
  id: 'zfy-excerpt-enhancer',
  name: '摘要增强器',
  description: '自动清洗摘要文本并控制长度，便于主题统一排版。',
  version: '1.0.0',
  author: 'zfy',
  defaultEnabled: true,
  settings: [
    { key: 'maxLength', label: '摘要长度', type: 'number', default: 140, min: 60, max: 320, step: 10 }
  ],
  hooks: {
    filters: {
      'blog:article:summary'(summary, context, settings) {
        const text = String(summary || '')
          .replace(/<[^>]+>/g, '')
          .replace(/\s+/g, ' ')
          .trim()
        const maxLength = Number(settings.maxLength || 140)
        if (text.length <= maxLength) {
          return text
        }
        return `${text.slice(0, maxLength)}...`
      }
    }
  }
}

export const zfyPluginCatalog = [zfyNoticePlugin, zfyHotPostsPlugin, zfyExcerptPlugin]
