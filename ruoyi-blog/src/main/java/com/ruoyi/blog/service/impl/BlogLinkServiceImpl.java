package com.ruoyi.blog.service.impl;

import com.ruoyi.blog.domain.BlogLink;
import com.ruoyi.blog.mapper.BlogLinkMapper;
import com.ruoyi.blog.service.IBlogLinkService;
import com.ruoyi.common.utils.DateUtils;
import java.util.List;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;

/**
 * Blog link service implementation.
 */
@Service
public class BlogLinkServiceImpl implements IBlogLinkService {
    @Autowired
    private BlogLinkMapper blogLinkMapper;

    @Override
    public BlogLink selectBlogLinkByLinkId(Long linkId) {
        return blogLinkMapper.selectBlogLinkByLinkId(linkId);
    }

    @Override
    public List<BlogLink> selectBlogLinkList(BlogLink blogLink) {
        return blogLinkMapper.selectBlogLinkList(blogLink);
    }

    @Override
    public int insertBlogLink(BlogLink blogLink) {
        blogLink.setCreateTime(DateUtils.getNowDate());
        return blogLinkMapper.insertBlogLink(blogLink);
    }

    @Override
    public int updateBlogLink(BlogLink blogLink) {
        blogLink.setUpdateTime(DateUtils.getNowDate());
        return blogLinkMapper.updateBlogLink(blogLink);
    }

    @Override
    public int deleteBlogLinkByLinkIds(Long[] linkIds) {
        return blogLinkMapper.deleteBlogLinkByLinkIds(linkIds);
    }

    @Override
    public int deleteBlogLinkByLinkId(Long linkId) {
        return blogLinkMapper.deleteBlogLinkByLinkId(linkId);
    }
}
