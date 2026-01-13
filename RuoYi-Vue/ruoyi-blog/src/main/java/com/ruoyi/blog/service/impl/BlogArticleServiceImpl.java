package com.ruoyi.blog.service.impl;

import java.util.ArrayList;
import java.util.List;
import com.ruoyi.common.utils.DateUtils;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import com.ruoyi.blog.mapper.BlogArticleMapper;
import com.ruoyi.blog.mapper.BlogArticleTagMapper;
import com.ruoyi.blog.mapper.BlogCategoryMapper;
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

    @Autowired
    private BlogCategoryMapper blogCategoryMapper;

    @Autowired
    private com.ruoyi.blog.mapper.BlogTagMapper blogTagMapper;

    @Autowired
    private com.ruoyi.blog.mapper.BlogTopicMapper blogTopicMapper;

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
            // 加载标签详细信息
            article.setTags(blogArticleMapper.selectTagsByArticleId(articleId));
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
        List<BlogArticle> articleList = blogArticleMapper.selectBlogArticleList(blogArticle);
        // 为每篇文章加载标签信息
        if (articleList != null && !articleList.isEmpty()) {
            for (BlogArticle article : articleList) {
                if (article.getArticleId() != null) {
                    article.setTags(blogArticleMapper.selectTagsByArticleId(article.getArticleId()));
                }
            }
        }
        return articleList;
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
        // 设置发布时间
        if (blogArticle.getPublishTime() == null) {
            blogArticle.setPublishTime(DateUtils.getNowDate());
        }
        int rows = blogArticleMapper.insertBlogArticle(blogArticle);
        if (rows > 0) {
            if (blogArticle.getTagIds() != null && blogArticle.getTagIds().length > 0) {
                insertArticleTags(blogArticle.getArticleId(), blogArticle.getTagIds());
            }
            // 如果是发布状态，更新分类文章数量
            if ("1".equals(blogArticle.getArticleStatus()) && blogArticle.getCategoryId() != null) {
                blogCategoryMapper.updateCategoryArticleCount(blogArticle.getCategoryId());
            }
            // 更新所有标签的文章数量
            if (blogArticle.getTagIds() != null && blogArticle.getTagIds().length > 0) {
                blogTagMapper.updateAllTagArticleCount();
            }
            // 如果是发布状态，更新专题文章数量
            if ("1".equals(blogArticle.getArticleStatus()) && blogArticle.getTopicId() != null) {
                blogTopicMapper.updateTopicArticleCount(blogArticle.getTopicId());
            }
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
        // 获取更新前的文章信息
        BlogArticle oldArticle = blogArticleMapper.selectBlogArticleByArticleId(blogArticle.getArticleId());

        blogArticle.setUpdateTime(DateUtils.getNowDate());
        // 如果文章状态是已发布且没有设置发布时间,则设置为当前时间
        if ("1".equals(blogArticle.getArticleStatus()) && blogArticle.getPublishTime() == null) {
            blogArticle.setPublishTime(DateUtils.getNowDate());
        }
        int rows = blogArticleMapper.updateBlogArticle(blogArticle);
        if (rows > 0) {
            blogArticleTagMapper.deleteBlogArticleTagByArticleId(blogArticle.getArticleId());
            if (blogArticle.getTagIds() != null && blogArticle.getTagIds().length > 0) {
                insertArticleTags(blogArticle.getArticleId(), blogArticle.getTagIds());
            }

            // 如果分类发生变化，需要更新旧分类和新分类的文章数量
            if (oldArticle != null) {
                Long oldCategoryId = oldArticle.getCategoryId();
                Long newCategoryId = blogArticle.getCategoryId();

                // 如果分类变了，更新旧分类和新分类
                if (!java.util.Objects.equals(oldCategoryId, newCategoryId)) {
                    if (oldCategoryId != null) {
                        blogCategoryMapper.updateCategoryArticleCount(oldCategoryId);
                    }
                    if (newCategoryId != null) {
                        blogCategoryMapper.updateCategoryArticleCount(newCategoryId);
                    }
                } else if (newCategoryId != null) {
                    // 如果分类没变，更新该分类的文章数量（可能是状态变了）
                    blogCategoryMapper.updateCategoryArticleCount(newCategoryId);
                }

                // 如果专题发生变化，需要更新旧专题和新专题的文章数量
                Long oldTopicId = oldArticle.getTopicId();
                Long newTopicId = blogArticle.getTopicId();

                // 如果专题变了，更新旧专题和新专题
                if (!java.util.Objects.equals(oldTopicId, newTopicId)) {
                    if (oldTopicId != null) {
                        blogTopicMapper.updateTopicArticleCount(oldTopicId);
                    }
                    if (newTopicId != null) {
                        blogTopicMapper.updateTopicArticleCount(newTopicId);
                    }
                } else if (newTopicId != null) {
                    // 如果专题没变，更新该专题的文章数量（可能是状态变了）
                    blogTopicMapper.updateTopicArticleCount(newTopicId);
                }
            }

            // 如果标签发生变化，更新所有标签的文章数量
            Long[] oldTagIds = oldArticle != null ? oldArticle.getTagIds() : null;
            Long[] newTagIds = blogArticle.getTagIds();
            if (!java.util.Arrays.equals(oldTagIds, newTagIds)) {
                blogTagMapper.updateAllTagArticleCount();
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
    @Transactional
    public int deleteBlogArticleByArticleIds(Long[] articleIds) {
        int rows = blogArticleMapper.deleteBlogArticleByArticleIds(articleIds);
        // 批量更新所有分类、标签和专题的文章数量
        if (rows > 0) {
            blogCategoryMapper.updateAllCategoryArticleCount();
            blogTagMapper.updateAllTagArticleCount();
            blogTopicMapper.updateAllTopicArticleCount();
        }
        return rows;
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
     * 批量更新文章状态
     *
     * @param articleIds 文章ID数组
     * @param status 状态（0草稿 1发布 2下架）
     * @return 结果
     */
    @Override
    @Transactional
    public int updateArticleStatus(Long[] articleIds, String status) {
        int rows = blogArticleMapper.updateArticleStatus(articleIds, status);
        // 更新所有分类、标签和专题的文章数量
        if (rows > 0) {
            blogCategoryMapper.updateAllCategoryArticleCount();
            blogTagMapper.updateAllTagArticleCount();
            blogTopicMapper.updateAllTopicArticleCount();
        }
        return rows;
    }

    /**
     * 获取统计数据
     *
     * @return 统计信息Map
     */
    @Override
    public java.util.Map<String, Object> getStatistics() {
        return blogArticleMapper.getStatistics();
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
