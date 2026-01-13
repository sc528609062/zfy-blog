package com.ruoyi.blog.service.impl;

import com.ruoyi.blog.domain.BlogArticleFavorite;
import com.ruoyi.blog.mapper.BlogArticleFavoriteMapper;
import com.ruoyi.blog.service.IBlogArticleFavoriteService;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

/**
 * 文章收藏Service业务层处理
 *
 * @author zfy
 * @date 2026-01-13
 */
@Service
public class BlogArticleFavoriteServiceImpl implements IBlogArticleFavoriteService {
    @Autowired
    private BlogArticleFavoriteMapper blogArticleFavoriteMapper;

    /**
     * 收藏文章
     */
    @Override
    @Transactional
    public void favoriteArticle(Long articleId, Long userId, String userIp) {
        BlogArticleFavorite favorite = blogArticleFavoriteMapper.checkFavorited(articleId, userId, userIp);
        if (favorite == null) {
            favorite = new BlogArticleFavorite();
            favorite.setArticleId(articleId);
            favorite.setUserId(userId);
            favorite.setUserIp(userIp);
            blogArticleFavoriteMapper.insertBlogArticleFavorite(favorite);
            blogArticleFavoriteMapper.updateArticleFavoriteCount(articleId, 1);
        }
    }

    /**
     * 取消收藏
     */
    @Override
    @Transactional
    public void unfavoriteArticle(Long articleId, Long userId, String userIp) {
        BlogArticleFavorite favorite = blogArticleFavoriteMapper.checkFavorited(articleId, userId, userIp);
        if (favorite != null) {
            blogArticleFavoriteMapper.deleteBlogArticleFavorite(articleId, userId, userIp);
            blogArticleFavoriteMapper.updateArticleFavoriteCount(articleId, -1);
        }
    }

    /**
     * 检查是否已收藏
     */
    @Override
    public boolean isFavorited(Long articleId, Long userId, String userIp) {
        return blogArticleFavoriteMapper.checkFavorited(articleId, userId, userIp) != null;
    }
}
