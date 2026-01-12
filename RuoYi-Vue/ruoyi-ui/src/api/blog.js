import request from '@/utils/request'

// ==================== 通用上传 ====================
// 通用上传请求
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

// ==================== 文章相关 ====================
// 查询博客文章列表
export function listArticle(query) {
  return request({
    url: '/blog/article/list',
    method: 'get',
    params: query
  })
}

// 查询博客文章详细（前台）
export function getArticle(articleId) {
  return request({
    url: '/blog/article/' + articleId,
    method: 'get'
  })
}

// 新增博客文章
export function addArticle(data) {
  return request({
    url: '/blog/article',
    method: 'post',
    data: data
  })
}

// 修改博客文章
export function updateArticle(data) {
  return request({
    url: '/blog/article',
    method: 'put',
    data: data
  })
}

// 删除博客文章
export function delArticle(articleId) {
  return request({
    url: '/blog/article/' + articleId,
    method: 'delete'
  })
}

// 增加浏览量
export function incrementView(articleId) {
  return request({
    url: '/blog/article/view/' + articleId,
    method: 'post'
  })
}

// ==================== 分类相关 ====================
// 查询分类列表
export function listCategory(query) {
  return request({
    url: '/blog/category/list',
    method: 'get',
    params: query
  })
}

// 查询分类详细
export function getCategory(categoryId) {
  return request({
    url: '/blog/category/' + categoryId,
    method: 'get'
  })
}

// 新增分类
export function addCategory(data) {
  return request({
    url: '/blog/category',
    method: 'post',
    data: data
  })
}

// 修改分类
export function updateCategory(data) {
  return request({
    url: '/blog/category',
    method: 'put',
    data: data
  })
}

// 删除分类
export function delCategory(categoryId) {
  return request({
    url: '/blog/category/' + categoryId,
    method: 'delete'
  })
}

// ==================== 标签相关 ====================
// 查询标签列表
export function listTag(query) {
  return request({
    url: '/blog/tag/list',
    method: 'get',
    params: query
  })
}

// 查询标签详细
export function getTag(tagId) {
  return request({
    url: '/blog/tag/' + tagId,
    method: 'get'
  })
}

// 新增标签
export function addTag(data) {
  return request({
    url: '/blog/tag',
    method: 'post',
    data: data
  })
}

// 修改标签
export function updateTag(data) {
  return request({
    url: '/blog/tag',
    method: 'put',
    data: data
  })
}

// 删除标签
export function delTag(tagId) {
  return request({
    url: '/blog/tag/' + tagId,
    method: 'delete'
  })
}

// ==================== 配置相关 ====================
// 查询博客配置（根据configKey）
export function getConfig(configKey) {
  return request({
    url: '/blog/config/key/' + configKey,
    method: 'get'
  })
}

// 查询所有配置
export function listConfig() {
  return request({
    url: '/blog/config/list',
    method: 'get'
  })
}

// 查询配置详细
export function getConfigById(configId) {
  return request({
    url: '/blog/config/' + configId,
    method: 'get'
  })
}

// 新增配置
export function addConfig(data) {
  return request({
    url: '/blog/config',
    method: 'post',
    data: data
  })
}

// 修改配置
export function updateConfig(data) {
  return request({
    url: '/blog/config',
    method: 'put',
    data: data
  })
}

// 删除配置
export function delConfig(configId) {
  return request({
    url: '/blog/config/' + configId,
    method: 'delete'
  })
}
