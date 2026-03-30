package com.ruoyi.blog.service;

import java.util.List;
import com.ruoyi.blog.domain.BlogArticle;

/**
 * 博客文章Service接口
 *
 * @author zfy
 * @date 2026-01-12
 */
public interface IBlogArticleService {
    /**
     * 查询博客文章
     *
     * @param articleId 博客文章主键
     * @return 博客文章
     */
    public BlogArticle selectBlogArticleByArticleId(Long articleId);

    /**
     * 查询博客文章列表
     *
     * @param blogArticle 博客文章
     * @return 博客文章集合
     */
    public List<BlogArticle> selectBlogArticleList(BlogArticle blogArticle);

    /**
     * 新增博客文章
     *
     * @param blogArticle 博客文章
     * @return 结果
     */
    public int insertBlogArticle(BlogArticle blogArticle);

    /**
     * 修改博客文章
     *
     * @param blogArticle 博客文章
     * @return 结果
     */
    public int updateBlogArticle(BlogArticle blogArticle);

    /**
     * 批量删除博客文章
     *
     * @param articleIds 需要删除的博客文章主键集合
     * @return 结果
     */
    public int deleteBlogArticleByArticleIds(Long[] articleIds);

    /**
     * 删除博客文章信息
     *
     * @param articleId 博客文章主键
     * @return 结果
     */
    public int deleteBlogArticleByArticleId(Long articleId);

    /**
     * 增加浏览量
     *
     * @param articleId 文章ID
     * @return 结果
     */
    public int incrementViewCount(Long articleId);

    /**
     * 批量更新文章状态
     *
     * @param articleIds 文章ID数组
     * @param status 状态（0草稿 1发布 2下架）
     * @return 结果
     */
    public int updateArticleStatus(Long[] articleIds, String status);

    /**
     * 获取统计数据
     *
     * @return 统计信息Map
     */
    public java.util.Map<String, Object> getStatistics();
}
