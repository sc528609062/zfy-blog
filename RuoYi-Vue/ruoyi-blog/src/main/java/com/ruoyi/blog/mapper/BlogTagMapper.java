package com.ruoyi.blog.mapper;

import java.util.List;
import com.ruoyi.blog.domain.BlogTag;

/**
 * 博客标签Mapper接口
 *
 * @author zfy
 * @date 2026-01-12
 */
public interface BlogTagMapper {
    /**
     * 查询博客标签
     *
     * @param tagId 博客标签主键
     * @return 博客标签
     */
    public BlogTag selectBlogTagByTagId(Long tagId);

    /**
     * 查询博客标签列表
     *
     * @param blogTag 博客标签
     * @return 博客标签集合
     */
    public List<BlogTag> selectBlogTagList(BlogTag blogTag);

    /**
     * 新增博客标签
     *
     * @param blogTag 博客标签
     * @return 结果
     */
    public int insertBlogTag(BlogTag blogTag);

    /**
     * 修改博客标签
     *
     * @param blogTag 博客标签
     * @return 结果
     */
    public int updateBlogTag(BlogTag blogTag);

    /**
     * 删除博客标签
     *
     * @param tagId 博客标签主键
     * @return 结果
     */
    public int deleteBlogTagByTagId(Long tagId);

    /**
     * 批量删除博客标签
     *
     * @param tagIds 需要删除的数据主键集合
     * @return 结果
     */
    public int deleteBlogTagByTagIds(Long[] tagIds);

    /**
     * 更新标签文章数量
     *
     * @param tagId 标签ID
     * @return 结果
     */
    public int updateTagArticleCount(Long tagId);

    /**
     * 批量更新所有标签的文章数量
     *
     * @return 结果
     */
    public int updateAllTagArticleCount();
}
