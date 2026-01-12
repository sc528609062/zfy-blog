package com.ruoyi.blog.service.impl;

import java.util.ArrayList;
import java.util.List;
import com.ruoyi.common.utils.DateUtils;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import com.ruoyi.blog.mapper.BlogArticleMapper;
import com.ruoyi.blog.mapper.BlogArticleTagMapper;
import com.ruoyi.blog.domain.BlogArticle;
import com.ruoyi.blog.domain.BlogArticleTag;
import com.ruoyi.blog.service.IBlogArticleService;

/**
 * 博客文章Service业务层处理
 *
 * @author zfy
 * @date 2026-01-12
 */
@Service
public class BlogArticleServiceImpl implements IBlogArticleService {
    @Autowired
    private BlogArticleMapper blogArticleMapper;

    @Autowired
    private BlogArticleTagMapper blogArticleTagMapper;

    /**
     * 查询博客文章
     *
     * @param articleId 博客文章主键
     * @return 博客文章
     */
    @Override
    public BlogArticle selectBlogArticleByArticleId(Long articleId) {
        BlogArticle article = blogArticleMapper.selectBlogArticleByArticleId(articleId);
        if (article != null) {
            List<Long> tagIds = blogArticleMapper.selectTagIdsByArticleId(articleId);
            article.setTagIds(tagIds.toArray(new Long[0]));
        }
        return article;
    }

    /**
     * 查询博客文章列表
     *
     * @param blogArticle 博客文章
     * @return 博客文章
     */
    @Override
    public List<BlogArticle> selectBlogArticleList(BlogArticle blogArticle) {
        return blogArticleMapper.selectBlogArticleList(blogArticle);
    }

    /**
     * 新增博客文章
     *
     * @param blogArticle 博客文章
     * @return 结果
     */
    @Override
    @Transactional
    public int insertBlogArticle(BlogArticle blogArticle) {
        int rows = blogArticleMapper.insertBlogArticle(blogArticle);
        if (rows > 0 && blogArticle.getTagIds() != null && blogArticle.getTagIds().length > 0) {
            insertArticleTags(blogArticle.getArticleId(), blogArticle.getTagIds());
        }
        return rows;
    }

    /**
     * 修改博客文章
     *
     * @param blogArticle 博客文章
     * @return 结果
     */
    @Override
    @Transactional
    public int updateBlogArticle(BlogArticle blogArticle) {
        blogArticle.setUpdateTime(DateUtils.getNowDate());
        int rows = blogArticleMapper.updateBlogArticle(blogArticle);
        if (rows > 0) {
            blogArticleTagMapper.deleteBlogArticleTagByArticleId(blogArticle.getArticleId());
            if (blogArticle.getTagIds() != null && blogArticle.getTagIds().length > 0) {
                insertArticleTags(blogArticle.getArticleId(), blogArticle.getTagIds());
            }
        }
        return rows;
    }

    /**
     * 批量删除博客文章
     *
     * @param articleIds 需要删除的博客文章主键
     * @return 结果
     */
    @Override
    public int deleteBlogArticleByArticleIds(Long[] articleIds) {
        return blogArticleMapper.deleteBlogArticleByArticleIds(articleIds);
    }

    /**
     * 删除博客文章信息
     *
     * @param articleId 博客文章主键
     * @return 结果
     */
    @Override
    public int deleteBlogArticleByArticleId(Long articleId) {
        return blogArticleMapper.deleteBlogArticleByArticleId(articleId);
    }

    /**
     * 增加浏览量
     *
     * @param articleId 文章ID
     * @return 结果
     */
    @Override
    public int incrementViewCount(Long articleId) {
        return blogArticleMapper.incrementViewCount(articleId);
    }

    /**
     * 批量插入文章标签关联
     *
     * @param articleId 文章ID
     * @param tagIds 标签ID数组
     */
    private void insertArticleTags(Long articleId, Long[] tagIds) {
        List<BlogArticleTag> articleTagList = new ArrayList<>();
        for (Long tagId : tagIds) {
            BlogArticleTag articleTag = new BlogArticleTag();
            articleTag.setArticleId(articleId);
            articleTag.setTagId(tagId);
            articleTagList.add(articleTag);
        }
        blogArticleTagMapper.batchBlogArticleTag(articleTagList);
    }
}
