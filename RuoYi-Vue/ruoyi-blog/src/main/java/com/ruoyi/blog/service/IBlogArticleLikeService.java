package com.ruoyi.blog.service;

/**
 * 文章点赞Service接口
 *
 * @author zfy
 * @date 2026-01-13
 */
public interface IBlogArticleLikeService {
    /**
     * 点赞文章
     *
     * @param articleId 文章ID
     * @param userId 用户ID
     * @param userIp 用户IP
     * @return 结果
     */
    public void likeArticle(Long articleId, Long userId, String userIp);

    /**
     * 取消点赞
     *
     * @param articleId 文章ID
     * @param userId 用户ID
     * @param userIp 用户IP
     * @return 结果
     */
    public void unlikeArticle(Long articleId, Long userId, String userIp);

    /**
     * 检查是否已点赞
     *
     * @param articleId 文章ID
     * @param userId 用户ID
     * @param userIp 用户IP
     * @return 是否已点赞
     */
    public boolean isLiked(Long articleId, Long userId, String userIp);
}
