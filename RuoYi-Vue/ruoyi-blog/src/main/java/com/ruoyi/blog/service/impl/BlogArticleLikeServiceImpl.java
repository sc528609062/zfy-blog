package com.ruoyi.blog.service.impl;

import com.ruoyi.blog.domain.BlogArticleLike;
import com.ruoyi.blog.mapper.BlogArticleLikeMapper;
import com.ruoyi.blog.service.IBlogArticleLikeService;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

/**
 * 文章点赞Service业务层处理
 *
 * @author zfy
 * @date 2026-01-13
 */
@Service
public class BlogArticleLikeServiceImpl implements IBlogArticleLikeService {
    @Autowired
    private BlogArticleLikeMapper blogArticleLikeMapper;

    /**
     * 点赞文章
     */
    @Override
    @Transactional
    public void likeArticle(Long articleId, Long userId, String userIp) {
        BlogArticleLike like = blogArticleLikeMapper.checkLiked(articleId, userId, userIp);
        if (like == null) {
            like = new BlogArticleLike();
            like.setArticleId(articleId);
            like.setUserId(userId);
            like.setUserIp(userIp);
            blogArticleLikeMapper.insertBlogArticleLike(like);
            blogArticleLikeMapper.updateArticleLikeCount(articleId, 1);
        }
    }

    /**
     * 取消点赞
     */
    @Override
    @Transactional
    public void unlikeArticle(Long articleId, Long userId, String userIp) {
        BlogArticleLike like = blogArticleLikeMapper.checkLiked(articleId, userId, userIp);
        if (like != null) {
            blogArticleLikeMapper.deleteBlogArticleLike(articleId, userId, userIp);
            blogArticleLikeMapper.updateArticleLikeCount(articleId, -1);
        }
    }

    /**
     * 检查是否已点赞
     */
    @Override
    public boolean isLiked(Long articleId, Long userId, String userIp) {
        return blogArticleLikeMapper.checkLiked(articleId, userId, userIp) != null;
    }
}
