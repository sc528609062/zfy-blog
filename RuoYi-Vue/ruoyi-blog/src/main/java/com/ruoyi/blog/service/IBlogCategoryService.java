package com.ruoyi.blog.service;

import java.util.List;
import com.ruoyi.blog.domain.BlogCategory;

/**
 * 博客分类Service接口
 *
 * @author zfy
 * @date 2026-01-12
 */
public interface IBlogCategoryService {
    /**
     * 查询博客分类
     *
     * @param categoryId 博客分类主键
     * @return 博客分类
     */
    public BlogCategory selectBlogCategoryByCategoryId(Long categoryId);

    /**
     * 查询博客分类列表
     *
     * @param blogCategory 博客分类
     * @return 博客分类集合
     */
    public List<BlogCategory> selectBlogCategoryList(BlogCategory blogCategory);

    /**
     * 新增博客分类
     *
     * @param blogCategory 博客分类
     * @return 结果
     */
    public int insertBlogCategory(BlogCategory blogCategory);

    /**
     * 修改博客分类
     *
     * @param blogCategory 博客分类
     * @return 结果
     */
    public int updateBlogCategory(BlogCategory blogCategory);

    /**
     * 批量删除博客分类
     *
     * @param categoryIds 需要删除的博客分类主键集合
     * @return 结果
     */
    public int deleteBlogCategoryByCategoryIds(Long[] categoryIds);

    /**
     * 删除博客分类信息
     *
     * @param categoryId 博客分类主键
     * @return 结果
     */
    public int deleteBlogCategoryByCategoryId(Long categoryId);
}
