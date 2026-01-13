package com.ruoyi.blog.service;

/**
 * 文章收藏Service接口
 *
 * @author zfy
 * @date 2026-01-13
 */
public interface IBlogArticleFavoriteService {
    /**
     * 收藏文章
     *
     * @param articleId 文章ID
     * @param userId 用户ID
     * @param userIp 用户IP
     * @return 结果
     */
    public void favoriteArticle(Long articleId, Long userId, String userIp);

    /**
     * 取消收藏
     *
     * @param articleId 文章ID
     * @param userId 用户ID
     * @param userIp 用户IP
     * @return 结果
     */
    public void unfavoriteArticle(Long articleId, Long userId, String userIp);

    /**
     * 检查是否已收藏
     *
     * @param articleId 文章ID
     * @param userId 用户ID
     * @param userIp 用户IP
     * @return 是否已收藏
     */
    public boolean isFavorited(Long articleId, Long userId, String userIp);
}
