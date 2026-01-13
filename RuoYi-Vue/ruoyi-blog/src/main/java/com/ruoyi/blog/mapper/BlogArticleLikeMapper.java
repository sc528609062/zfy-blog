package com.ruoyi.blog.mapper;

import com.ruoyi.blog.domain.BlogArticleLike;
import org.apache.ibatis.annotations.Param;

/**
 * 文章点赞Mapper接口
 *
 * @author zfy
 * @date 2026-01-13
 */
public interface BlogArticleLikeMapper {
    /**
     * 检查是否已点赞
     *
     * @param articleId 文章ID
     * @param userId 用户ID
     * @param userIp 用户IP
     * @return 点赞记录
     */
    public BlogArticleLike checkLiked(@Param("articleId") Long articleId, @Param("userId") Long userId, @Param("userIp") String userIp);

    /**
     * 新增点赞
     *
     * @param blogArticleLike 文章点赞
     * @return 结果
     */
    public int insertBlogArticleLike(BlogArticleLike blogArticleLike);

    /**
     * 取消点赞
     *
     * @param articleId 文章ID
     * @param userId 用户ID
     * @param userIp 用户IP
     * @return 结果
     */
    public int deleteBlogArticleLike(@Param("articleId") Long articleId, @Param("userId") Long userId, @Param("userIp") String userIp);

    /**
     * 更新文章点赞数
     *
     * @param articleId 文章ID
     * @param increment 增量(1或-1)
     * @return 结果
     */
    public int updateArticleLikeCount(@Param("articleId") Long articleId, @Param("increment") int increment);
}
