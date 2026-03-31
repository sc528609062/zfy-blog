package com.ruoyi.blog.service.impl;

import com.ruoyi.blog.domain.BlogArticleFavorite;
import com.ruoyi.blog.mapper.BlogArticleFavoriteMapper;
import com.ruoyi.blog.service.IBlogArticleFavoriteService;
import com.ruoyi.common.utils.StringUtils;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

/**
 * 文章收藏 Service 实现。
 */
@Service
public class BlogArticleFavoriteServiceImpl implements IBlogArticleFavoriteService {
    private static final String LOGIN_USER_IP_PLACEHOLDER = "LOGIN";

    @Autowired
    private BlogArticleFavoriteMapper blogArticleFavoriteMapper;

    /**
     * 收藏文章。
     */
    @Override
    @Transactional
    public void favoriteArticle(Long articleId, Long userId, String userIp) {
        Identity identity = resolveIdentity(userId, userIp);
        BlogArticleFavorite favorite = blogArticleFavoriteMapper.checkFavorited(articleId, identity.getUserId(), identity.getUserIp());
        if (favorite == null) {
            favorite = new BlogArticleFavorite();
            favorite.setArticleId(articleId);
            favorite.setUserId(identity.getUserId());
            favorite.setUserIp(identity.getUserIp());
            blogArticleFavoriteMapper.insertBlogArticleFavorite(favorite);
            blogArticleFavoriteMapper.updateArticleFavoriteCount(articleId, 1);
        }
        // 自修复历史脏数据：确保文章收藏数与收藏关系一致
        blogArticleFavoriteMapper.refreshArticleFavoriteCount(articleId);
    }

    /**
     * 取消收藏。
     */
    @Override
    @Transactional
    public void unfavoriteArticle(Long articleId, Long userId, String userIp) {
        Identity identity = resolveIdentity(userId, userIp);
        BlogArticleFavorite favorite = blogArticleFavoriteMapper.checkFavorited(articleId, identity.getUserId(), identity.getUserIp());
        if (favorite != null) {
            blogArticleFavoriteMapper.deleteBlogArticleFavorite(articleId, identity.getUserId(), identity.getUserIp());
            blogArticleFavoriteMapper.updateArticleFavoriteCount(articleId, -1);
        }
        // 自修复历史脏数据：确保文章收藏数与收藏关系一致
        blogArticleFavoriteMapper.refreshArticleFavoriteCount(articleId);
    }

    /**
     * 检查是否已收藏。
     */
    @Override
    public boolean isFavorited(Long articleId, Long userId, String userIp) {
        // 页面刷新时顺便校正收藏计数，避免历史异常数据导致显示为0
        blogArticleFavoriteMapper.refreshArticleFavoriteCount(articleId);
        Identity identity = resolveIdentity(userId, userIp);
        return blogArticleFavoriteMapper.checkFavorited(articleId, identity.getUserId(), identity.getUserIp()) != null;
    }

    private Identity resolveIdentity(Long userId, String userIp) {
        if (userId != null) {
            // 登录用户只按 userId 判重，不叠加真实 IP；这里用固定占位值让唯一索引生效。
            return new Identity(userId, LOGIN_USER_IP_PLACEHOLDER);
        }
        return new Identity(null, StringUtils.defaultIfBlank(userIp, "unknown"));
    }

    private static final class Identity {
        private final Long userId;
        private final String userIp;

        private Identity(Long userId, String userIp) {
            this.userId = userId;
            this.userIp = userIp;
        }

        public Long getUserId() {
            return userId;
        }

        public String getUserIp() {
            return userIp;
        }
    }
}
