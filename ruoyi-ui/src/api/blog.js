import request from '@/utils/request'

// ==================== 閫氱敤涓婁紶 ====================
// 閫氱敤涓婁紶璇锋眰
export function upload(file) {
  const formData = new FormData()
  formData.append('file', file)
  return request({
    url: '/common/upload',
    method: 'post',
    data: formData,
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  })
}

// ==================== 鏂囩珷鐩稿叧 ====================
// 鏌ヨ鍗氬鏂囩珷鍒楄〃
export function listArticle(query) {
  return request({
    url: '/blog/article/list',
    method: 'get',
    params: query
  })
}

// 鏌ヨ鍗氬鏂囩珷璇︾粏锛堝墠鍙帮級
export function getArticle(articleId) {
  return request({
    url: '/blog/article/' + articleId,
    method: 'get'
  })
}

// 鏂板鍗氬鏂囩珷
export function addArticle(data) {
  return request({
    url: '/blog/article',
    method: 'post',
    data: data
  })
}

// 淇敼鍗氬鏂囩珷
export function updateArticle(data) {
  return request({
    url: '/blog/article',
    method: 'put',
    data: data
  })
}

// 鍒犻櫎鍗氬鏂囩珷
export function delArticle(articleId) {
  return request({
    url: '/blog/article/' + articleId,
    method: 'delete'
  })
}

// 澧炲姞娴忚閲?
export function incrementView(articleId) {
  return request({
    url: '/blog/article/view/' + articleId,
    method: 'post'
  })
}

// 鐐硅禐鏂囩珷
export function likeArticle(articleId) {
  return request({
    url: '/blog/article/like/' + articleId,
    method: 'post'
  })
}

// 鍙栨秷鐐硅禐
export function unlikeArticle(articleId) {
  return request({
    url: '/blog/article/like/' + articleId,
    method: 'delete'
  })
}

// 妫€鏌ユ槸鍚﹀凡鐐硅禐
export function checkLiked(articleId) {
  return request({
    url: '/blog/article/like/check/' + articleId,
    method: 'get'
  })
}

// 鏀惰棌鏂囩珷
export function favoriteArticle(articleId) {
  return request({
    url: '/blog/article/favorite/' + articleId,
    method: 'post'
  })
}

// 鍙栨秷鏀惰棌
export function unfavoriteArticle(articleId) {
  return request({
    url: '/blog/article/favorite/' + articleId,
    method: 'delete'
  })
}

// 妫€鏌ユ槸鍚﹀凡鏀惰棌
export function checkFavorited(articleId) {
  return request({
    url: '/blog/article/favorite/check/' + articleId,
    method: 'get'
  })
}

// 鎵归噺鍙戝竷鏂囩珷
export function updateArticleStatus(articleIds, status) {
  const url = status === '1' ? '/blog/article/publish' : '/blog/article/offline'
  return request({
    url: url,
    method: 'put',
    data: articleIds
  })
}

// 鑾峰彇缁熻鏁版嵁
export function getStatistics() {
  return request({
    url: '/blog/article/statistics',
    method: 'get'
  })
}

// ==================== 鍒嗙被鐩稿叧 ====================
// 鏌ヨ鍒嗙被鍒楄〃
export function listCategory(query) {
  return request({
    url: '/blog/category/list',
    method: 'get',
    params: query
  })
}

// 鏌ヨ鍒嗙被璇︾粏
export function getCategory(categoryId) {
  return request({
    url: '/blog/category/' + categoryId,
    method: 'get'
  })
}

// 鏂板鍒嗙被
export function addCategory(data) {
  return request({
    url: '/blog/category',
    method: 'post',
    data: data
  })
}

// 淇敼鍒嗙被
export function updateCategory(data) {
  return request({
    url: '/blog/category',
    method: 'put',
    data: data
  })
}

// 鍒犻櫎鍒嗙被
export function delCategory(categoryId) {
  return request({
    url: '/blog/category/' + categoryId,
    method: 'delete'
  })
}

// 鍚屾鍒嗙被鏂囩珷鏁伴噺
export function syncCategoryCount() {
  return request({
    url: '/blog/category/syncCount',
    method: 'get'
  })
}

// ==================== 鏍囩鐩稿叧 ====================
// 鏌ヨ鏍囩鍒楄〃
export function listTag(query) {
  return request({
    url: '/blog/tag/list',
    method: 'get',
    params: query
  })
}

// 鏌ヨ鏍囩璇︾粏
export function getTag(tagId) {
  return request({
    url: '/blog/tag/' + tagId,
    method: 'get'
  })
}

// 鏂板鏍囩
export function addTag(data) {
  return request({
    url: '/blog/tag',
    method: 'post',
    data: data
  })
}

// 淇敼鏍囩
export function updateTag(data) {
  return request({
    url: '/blog/tag',
    method: 'put',
    data: data
  })
}

// 鍒犻櫎鏍囩
export function delTag(tagId) {
  return request({
    url: '/blog/tag/' + tagId,
    method: 'delete'
  })
}

// 鍚屾鏍囩鏂囩珷鏁伴噺
export function syncTagCount() {
  return request({
    url: '/blog/tag/syncCount',
    method: 'get'
  })
}

// ==================== 涓撻鐩稿叧 ====================
// 鏌ヨ涓撻鍒楄〃
export function listTopic(query) {
  return request({
    url: '/blog/topic/list',
    method: 'get',
    params: query
  })
}

// 鏌ヨ涓撻璇︾粏
export function getTopic(topicId) {
  return request({
    url: '/blog/topic/' + topicId,
    method: 'get'
  })
}

// 鏂板涓撻
export function addTopic(data) {
  return request({
    url: '/blog/topic',
    method: 'post',
    data: data
  })
}

// 淇敼涓撻
export function updateTopic(data) {
  return request({
    url: '/blog/topic',
    method: 'put',
    data: data
  })
}

// 鍒犻櫎涓撻
export function delTopic(topicId) {
  return request({
    url: '/blog/topic/' + topicId,
    method: 'delete'
  })
}

// 鍚屾涓撻鏂囩珷鏁伴噺
export function syncTopicCount() {
  return request({
    url: '/blog/topic/syncCount',
    method: 'get'
  })
}

// ==================== 閰嶇疆鐩稿叧 ====================
// 鏌ヨ鍗氬閰嶇疆锛堟牴鎹甤onfigKey锛?
export function getConfig(configKey) {
  return request({
    url: '/blog/config/key/' + configKey,
    method: 'get'
  })
}

// 鏌ヨ鎵€鏈夐厤缃?
export function listConfig() {
  return request({
    url: '/blog/config/list',
    method: 'get'
  })
}

// 鏌ヨ閰嶇疆璇︾粏
export function getConfigById(configId) {
  return request({
    url: '/blog/config/' + configId,
    method: 'get'
  })
}

// 鏂板閰嶇疆
export function addConfig(data) {
  return request({
    url: '/blog/config',
    method: 'post',
    data: data
  })
}

// 淇敼閰嶇疆
export function updateConfig(data) {
  return request({
    url: '/blog/config',
    method: 'put',
    data: data
  })
}

// 鍒犻櫎閰嶇疆
export function delConfig(configId) {
  return request({
    url: '/blog/config/' + configId,
    method: 'delete'
  })
}

// ==================== 璇勮鐩稿叧 ====================
// 鏌ヨ璇勮鍒楄〃
export function listComment(query) {
  return request({
    url: '/blog/comment/list',
    method: 'get',
    params: query
  })
}

export function listPublicComment(articleId) {
  return request({
    url: '/blog/comment/public/list/' + articleId,
    method: 'get'
  })
}

export function getComment(commentId) {
  return request({
    url: '/blog/comment/' + commentId,
    method: 'get'
  })
}

export function addComment(data) {
  return request({
    url: '/blog/comment',
    method: 'post',
    data: data
  })
}

export function addPublicComment(data) {
  return request({
    url: '/blog/comment/public',
    method: 'post',
    data: data
  })
}

export function updateComment(data) {
  return request({
    url: '/blog/comment',
    method: 'put',
    data: data
  })
}

export function updateCommentStatus(commentId, status) {
  return request({
    url: '/blog/comment/status',
    method: 'put',
    data: {
      commentId,
      status
    }
  })
}

export function delComment(commentId) {
  return request({
    url: '/blog/comment/' + commentId,
    method: 'delete'
  })
}

// ==================== 鍙嬮摼鐩稿叧 ====================
// 鏌ヨ鍙嬮摼鍒楄〃
export function listLink(query) {
  return request({
    url: '/blog/link/list',
    method: 'get',
    params: query
  })
}

// 鏌ヨ鍙嬮摼璇︽儏
export function getLink(linkId) {
  return request({
    url: '/blog/link/' + linkId,
    method: 'get'
  })
}

// 鏂板鍙嬮摼
export function addLink(data) {
  return request({
    url: '/blog/link',
    method: 'post',
    data: data
  })
}

// 淇敼鍙嬮摼
export function updateLink(data) {
  return request({
    url: '/blog/link',
    method: 'put',
    data: data
  })
}

// 鍒犻櫎鍙嬮摼
export function delLink(linkId) {
  return request({
    url: '/blog/link/' + linkId,
    method: 'delete'
  })
}

// ==================== 椤甸潰鐩稿叧 ====================
// 鏌ヨ椤甸潰鍒楄〃
export function listPage(query) {
  return request({
    url: '/blog/page/list',
    method: 'get',
    params: query
  })
}

// 鏌ヨ椤甸潰璇︽儏
export function getPage(pageId) {
  return request({
    url: '/blog/page/' + pageId,
    method: 'get'
  })
}

// 鏂板椤甸潰
export function addPage(data) {
  return request({
    url: '/blog/page',
    method: 'post',
    data: data
  })
}

// 淇敼椤甸潰
export function updatePage(data) {
  return request({
    url: '/blog/page',
    method: 'put',
    data: data
  })
}

// 鍒犻櫎椤甸潰
export function delPage(pageId) {
  return request({
    url: '/blog/page/' + pageId,
    method: 'delete'
  })
}

