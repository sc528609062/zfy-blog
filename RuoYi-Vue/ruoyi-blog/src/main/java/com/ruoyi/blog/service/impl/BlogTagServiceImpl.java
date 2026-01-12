package com.ruoyi.blog.service.impl;

import java.util.List;
import com.ruoyi.common.utils.DateUtils;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import com.ruoyi.blog.mapper.BlogTagMapper;
import com.ruoyi.blog.domain.BlogTag;
import com.ruoyi.blog.service.IBlogTagService;

/**
 * 博客标签Service业务层处理
 *
 * @author zfy
 * @date 2026-01-12
 */
@Service
public class BlogTagServiceImpl implements IBlogTagService {
    @Autowired
    private BlogTagMapper blogTagMapper;

    /**
     * 查询博客标签
     *
     * @param tagId 博客标签主键
     * @return 博客标签
     */
    @Override
    public BlogTag selectBlogTagByTagId(Long tagId) {
        return blogTagMapper.selectBlogTagByTagId(tagId);
    }

    /**
     * 查询博客标签列表
     *
     * @param blogTag 博客标签
     * @return 博客标签
     */
    @Override
    public List<BlogTag> selectBlogTagList(BlogTag blogTag) {
        return blogTagMapper.selectBlogTagList(blogTag);
    }

    /**
     * 新增博客标签
     *
     * @param blogTag 博客标签
     * @return 结果
     */
    @Override
    public int insertBlogTag(BlogTag blogTag) {
        blogTag.setCreateTime(DateUtils.getNowDate());
        return blogTagMapper.insertBlogTag(blogTag);
    }

    /**
     * 修改博客标签
     *
     * @param blogTag 博客标签
     * @return 结果
     */
    @Override
    public int updateBlogTag(BlogTag blogTag) {
        blogTag.setUpdateTime(DateUtils.getNowDate());
        return blogTagMapper.updateBlogTag(blogTag);
    }

    /**
     * 批量删除博客标签
     *
     * @param tagIds 需要删除的博客标签主键
     * @return 结果
     */
    @Override
    public int deleteBlogTagByTagIds(Long[] tagIds) {
        return blogTagMapper.deleteBlogTagByTagIds(tagIds);
    }

    /**
     * 删除博客标签信息
     *
     * @param tagId 博客标签主键
     * @return 结果
     */
    @Override
    public int deleteBlogTagByTagId(Long tagId) {
        return blogTagMapper.deleteBlogTagByTagId(tagId);
    }
}
