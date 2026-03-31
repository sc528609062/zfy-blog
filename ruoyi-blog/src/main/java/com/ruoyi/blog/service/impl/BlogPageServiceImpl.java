package com.ruoyi.blog.service.impl;

import com.ruoyi.blog.domain.BlogPage;
import com.ruoyi.blog.mapper.BlogPageMapper;
import com.ruoyi.blog.service.IBlogPageService;
import com.ruoyi.common.utils.DateUtils;
import java.util.List;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;

/**
 * Blog page service implementation.
 */
@Service
public class BlogPageServiceImpl implements IBlogPageService {
    @Autowired
    private BlogPageMapper blogPageMapper;

    @Override
    public BlogPage selectBlogPageByPageId(Long pageId) {
        return blogPageMapper.selectBlogPageByPageId(pageId);
    }

    @Override
    public List<BlogPage> selectBlogPageList(BlogPage blogPage) {
        return blogPageMapper.selectBlogPageList(blogPage);
    }

    @Override
    public int insertBlogPage(BlogPage blogPage) {
        blogPage.setCreateTime(DateUtils.getNowDate());
        return blogPageMapper.insertBlogPage(blogPage);
    }

    @Override
    public int updateBlogPage(BlogPage blogPage) {
        blogPage.setUpdateTime(DateUtils.getNowDate());
        return blogPageMapper.updateBlogPage(blogPage);
    }

    @Override
    public int deleteBlogPageByPageIds(Long[] pageIds) {
        return blogPageMapper.deleteBlogPageByPageIds(pageIds);
    }

    @Override
    public int deleteBlogPageByPageId(Long pageId) {
        return blogPageMapper.deleteBlogPageByPageId(pageId);
    }
}
