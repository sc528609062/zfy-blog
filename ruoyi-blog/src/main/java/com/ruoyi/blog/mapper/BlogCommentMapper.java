package com.ruoyi.blog.mapper;

import com.ruoyi.blog.domain.BlogComment;
import org.apache.ibatis.annotations.Param;

import java.util.List;

/**
 * Blog comment mapper.
 */
public interface BlogCommentMapper {
    /**
     * Query comment by ID.
     *
     * @param commentId comment ID
     * @return comment
     */
    BlogComment selectBlogCommentByCommentId(Long commentId);

    /**
     * Query comment list.
     *
     * @param blogComment query conditions
     * @return comment list
     */
    List<BlogComment> selectBlogCommentList(BlogComment blogComment);

    /**
     * Query approved comments by article ID.
     *
     * @param articleId article ID
     * @return comments
     */
    List<BlogComment> selectApprovedCommentListByArticleId(Long articleId);

    /**
     * Insert comment.
     *
     * @param blogComment comment
     * @return affected rows
     */
    int insertBlogComment(BlogComment blogComment);

    /**
     * Update comment.
     *
     * @param blogComment comment
     * @return affected rows
     */
    int updateBlogComment(BlogComment blogComment);

    /**
     * Update comment status.
     *
     * @param blogComment comment with ID and status
     * @return affected rows
     */
    int updateBlogCommentStatus(BlogComment blogComment);

    /**
     * Soft delete comments by IDs.
     *
     * @param commentIds comment IDs
     * @return affected rows
     */
    int deleteBlogCommentByCommentIds(Long[] commentIds);

    /**
     * Soft delete comment by ID.
     *
     * @param commentId comment ID
     * @return affected rows
     */
    int deleteBlogCommentByCommentId(Long commentId);

    /**
     * Query article ID by comment ID.
     *
     * @param commentId comment ID
     * @return article ID
     */
    Long selectArticleIdByCommentId(Long commentId);

    /**
     * Query article IDs by comment IDs.
     *
     * @param commentIds comment IDs
     * @return article IDs
     */
    List<Long> selectArticleIdsByCommentIds(@Param("commentIds") Long[] commentIds);

    /**
     * Refresh article comment count.
     *
     * @param articleId article ID
     * @return affected rows
     */
    int refreshArticleCommentCount(Long articleId);
}
