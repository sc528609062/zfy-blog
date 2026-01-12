package com.ruoyi.blog.mapper;

import java.util.List;
import com.ruoyi.blog.domain.BlogArticle;
import com.ruoyi.blog.domain.BlogTag;

/**
 * 博客文章Mapper接口
 *
 * @author zfy
 * @date 2026-01-12
 */
public interface BlogArticleMapper {
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
     * 删除博客文章
     *
     * @param articleId 博客文章主键
     * @return 结果
     */
    public int deleteBlogArticleByArticleId(Long articleId);

    /**
     * 批量删除博客文章
     *
     * @param articleIds 需要删除的数据主键集合
     * @return 结果
     */
    public int deleteBlogArticleByArticleIds(Long[] articleIds);

    /**
     * 增加浏览量
     *
     * @param articleId 文章ID
     * @return 结果
     */
    public int incrementViewCount(Long articleId);

    /**
     * 查询文章关联的标签ID列表
     *
     * @param articleId 文章ID
     * @return 标签ID列表
     */
    public List<Long> selectTagIdsByArticleId(Long articleId);

    /**
     * 查询文章关联的标签详细信息
     *
     * @param articleId 文章ID
     * @return 标签列表
     */
    public List<BlogTag> selectTagsByArticleId(Long articleId);

    /**
     * 批量更新文章状态
     *
     * @param articleIds 文章ID数组
     * @param articleStatus 状态
     * @return 结果
     */
    public int updateArticleStatus(Long[] articleIds, String articleStatus);

    /**
     * 获取文章统计信息
     *
     * @return 统计信息
     */
    public java.util.Map<String, Object> getStatistics();
}
