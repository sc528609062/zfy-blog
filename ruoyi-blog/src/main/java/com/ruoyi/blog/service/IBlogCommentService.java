package com.ruoyi.blog.service;

import com.ruoyi.blog.domain.BlogComment;
import java.util.List;

/**
 * Blog comment service.
 */
public interface IBlogCommentService {
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
     * Query approved comment tree by article ID.
     *
     * @param articleId article ID
     * @return comment tree list
     */
    List<BlogComment> selectApprovedCommentTreeByArticleId(Long articleId);

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
}
