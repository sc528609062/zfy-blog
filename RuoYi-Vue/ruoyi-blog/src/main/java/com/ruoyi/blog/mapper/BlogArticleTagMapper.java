package com.ruoyi.blog.mapper;

import java.util.List;
import com.ruoyi.blog.domain.BlogArticleTag;

/**
 * 博客文章标签关联Mapper接口
 *
 * @author zfy
 * @date 2026-01-12
 */
public interface BlogArticleTagMapper {
    /**
     * 批量删除文章标签关联
     *
     * @param articleId 文章ID
     * @return 结果
     */
    public int deleteBlogArticleTagByArticleId(Long articleId);

    /**
     * 批量新增文章标签关联
     *
     * @param articleTagList 文章标签关联列表
     * @return 结果
     */
    public int batchBlogArticleTag(List<BlogArticleTag> articleTagList);

    /**
     * 查询文章标签ID列表
     *
     * @param articleId 文章ID
     * @return 标签ID列表
     */
    public List<Long> selectTagIdsByArticleId(Long articleId);
}
