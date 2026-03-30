package com.ruoyi.blog.mapper;

import com.ruoyi.blog.domain.BlogArticleFavorite;
import org.apache.ibatis.annotations.Param;

/**
 * 文章收藏Mapper接口
 *
 * @author zfy
 * @date 2026-01-13
 */
public interface BlogArticleFavoriteMapper {
    /**
     * 检查是否已收藏
     *
     * @param articleId 文章ID
     * @param userId 用户ID
     * @param userIp 用户IP
     * @return 收藏记录
     */
    public BlogArticleFavorite checkFavorited(@Param("articleId") Long articleId, @Param("userId") Long userId, @Param("userIp") String userIp);

    /**
     * 新增收藏
     *
     * @param blogArticleFavorite 文章收藏
     * @return 结果
     */
    public int insertBlogArticleFavorite(BlogArticleFavorite blogArticleFavorite);

    /**
     * 取消收藏
     *
     * @param articleId 文章ID
     * @param userId 用户ID
     * @param userIp 用户IP
     * @return 结果
     */
    public int deleteBlogArticleFavorite(@Param("articleId") Long articleId, @Param("userId") Long userId, @Param("userIp") String userIp);

    /**
     * 更新文章收藏数
     *
     * @param articleId 文章ID
     * @param increment 增量(1或-1)
     * @return 结果
     */
    public int updateArticleFavoriteCount(@Param("articleId") Long articleId, @Param("increment") int increment);
}
