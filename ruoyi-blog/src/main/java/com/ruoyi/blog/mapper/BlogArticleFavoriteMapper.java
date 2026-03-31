package com.ruoyi.blog.mapper;

import com.ruoyi.blog.domain.BlogArticleFavorite;
import org.apache.ibatis.annotations.Param;

/**
 * 文章收藏 Mapper。
 */
public interface BlogArticleFavoriteMapper {
    BlogArticleFavorite checkFavorited(@Param("articleId") Long articleId,
                                       @Param("userId") Long userId,
                                       @Param("userIp") String userIp);

    int insertBlogArticleFavorite(BlogArticleFavorite blogArticleFavorite);

    int deleteBlogArticleFavorite(@Param("articleId") Long articleId,
                                  @Param("userId") Long userId,
                                  @Param("userIp") String userIp);

    int updateArticleFavoriteCount(@Param("articleId") Long articleId,
                                   @Param("increment") int increment);

    int refreshArticleFavoriteCount(@Param("articleId") Long articleId);
}
