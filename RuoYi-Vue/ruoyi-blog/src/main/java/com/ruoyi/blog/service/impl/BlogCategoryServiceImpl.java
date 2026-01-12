package com.ruoyi.blog.service.impl;

import java.util.List;
import com.ruoyi.common.utils.DateUtils;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import com.ruoyi.blog.mapper.BlogCategoryMapper;
import com.ruoyi.blog.domain.BlogCategory;
import com.ruoyi.blog.service.IBlogCategoryService;

/**
 * 博客分类Service业务层处理
 *
 * @author zfy
 * @date 2026-01-12
 */
@Service
public class BlogCategoryServiceImpl implements IBlogCategoryService {
    @Autowired
    private BlogCategoryMapper blogCategoryMapper;

    /**
     * 查询博客分类
     *
     * @param categoryId 博客分类主键
     * @return 博客分类
     */
    @Override
    public BlogCategory selectBlogCategoryByCategoryId(Long categoryId) {
        return blogCategoryMapper.selectBlogCategoryByCategoryId(categoryId);
    }

    /**
     * 查询博客分类列表
     *
     * @param blogCategory 博客分类
     * @return 博客分类
     */
    @Override
    public List<BlogCategory> selectBlogCategoryList(BlogCategory blogCategory) {
        return blogCategoryMapper.selectBlogCategoryList(blogCategory);
    }

    /**
     * 新增博客分类
     *
     * @param blogCategory 博客分类
     * @return 结果
     */
    @Override
    public int insertBlogCategory(BlogCategory blogCategory) {
        blogCategory.setCreateTime(DateUtils.getNowDate());
        return blogCategoryMapper.insertBlogCategory(blogCategory);
    }

    /**
     * 修改博客分类
     *
     * @param blogCategory 博客分类
     * @return 结果
     */
    @Override
    public int updateBlogCategory(BlogCategory blogCategory) {
        blogCategory.setUpdateTime(DateUtils.getNowDate());
        return blogCategoryMapper.updateBlogCategory(blogCategory);
    }

    /**
     * 批量删除博客分类
     *
     * @param categoryIds 需要删除的博客分类主键
     * @return 结果
     */
    @Override
    public int deleteBlogCategoryByCategoryIds(Long[] categoryIds) {
        return blogCategoryMapper.deleteBlogCategoryByCategoryIds(categoryIds);
    }

    /**
     * 删除博客分类信息
     *
     * @param categoryId 博客分类主键
     * @return 结果
     */
    @Override
    public int deleteBlogCategoryByCategoryId(Long categoryId) {
        return blogCategoryMapper.deleteBlogCategoryByCategoryId(categoryId);
    }
}
