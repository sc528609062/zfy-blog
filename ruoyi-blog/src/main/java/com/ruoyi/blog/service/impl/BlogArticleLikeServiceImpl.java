package com.ruoyi.blog.service.impl;

import com.ruoyi.blog.domain.BlogArticleLike;
import com.ruoyi.blog.mapper.BlogArticleLikeMapper;
import com.ruoyi.blog.service.IBlogArticleLikeService;
import com.ruoyi.common.utils.StringUtils;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

/**
 * 文章点赞 Service 实现。
 */
@Service
public class BlogArticleLikeServiceImpl implements IBlogArticleLikeService {
    private static final String LOGIN_USER_IP_PLACEHOLDER = "LOGIN";

    @Autowired
    private BlogArticleLikeMapper blogArticleLikeMapper;

    /**
     * 点赞文章。
     */
    @Override
    @Transactional
    public void likeArticle(Long articleId, Long userId, String userIp) {
        Identity identity = resolveIdentity(userId, userIp);
        BlogArticleLike like = blogArticleLikeMapper.checkLiked(articleId, identity.getUserId(), identity.getUserIp());
        if (like == null) {
            like = new BlogArticleLike();
            like.setArticleId(articleId);
            like.setUserId(identity.getUserId());
            like.setUserIp(identity.getUserIp());
            blogArticleLikeMapper.insertBlogArticleLike(like);
            blogArticleLikeMapper.updateArticleLikeCount(articleId, 1);
        }
    }

    /**
     * 取消点赞。
     */
    @Override
    @Transactional
    public void unlikeArticle(Long articleId, Long userId, String userIp) {
        Identity identity = resolveIdentity(userId, userIp);
        BlogArticleLike like = blogArticleLikeMapper.checkLiked(articleId, identity.getUserId(), identity.getUserIp());
        if (like != null) {
            blogArticleLikeMapper.deleteBlogArticleLike(articleId, identity.getUserId(), identity.getUserIp());
            blogArticleLikeMapper.updateArticleLikeCount(articleId, -1);
        }
    }

    /**
     * 检查是否已点赞。
     */
    @Override
    public boolean isLiked(Long articleId, Long userId, String userIp) {
        Identity identity = resolveIdentity(userId, userIp);
        return blogArticleLikeMapper.checkLiked(articleId, identity.getUserId(), identity.getUserIp()) != null;
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
