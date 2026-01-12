package com.ruoyi.blog.service;

import java.util.List;
import com.ruoyi.blog.domain.BlogTag;

/**
 * 博客标签Service接口
 *
 * @author zfy
 * @date 2026-01-12
 */
public interface IBlogTagService {
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
     * 批量删除博客标签
     *
     * @param tagIds 需要删除的博客标签主键集合
     * @return 结果
     */
    public int deleteBlogTagByTagIds(Long[] tagIds);

    /**
     * 删除博客标签信息
     *
     * @param tagId 博客标签主键
     * @return 结果
     */
    public int deleteBlogTagByTagId(Long tagId);
}
