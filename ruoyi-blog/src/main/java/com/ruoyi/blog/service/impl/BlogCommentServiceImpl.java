package com.ruoyi.blog.service.impl;

import com.ruoyi.blog.domain.BlogComment;
import com.ruoyi.blog.mapper.BlogCommentMapper;
import com.ruoyi.blog.service.IBlogCommentService;
import com.ruoyi.common.utils.DateUtils;
import com.ruoyi.common.utils.StringUtils;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.LinkedHashSet;
import java.util.List;
import java.util.Map;
import java.util.Set;

/**
 * Blog comment service implementation.
 */
@Service
public class BlogCommentServiceImpl implements IBlogCommentService {
    @Autowired
    private BlogCommentMapper blogCommentMapper;

    @Override
    public BlogComment selectBlogCommentByCommentId(Long commentId) {
        return blogCommentMapper.selectBlogCommentByCommentId(commentId);
    }

    @Override
    public List<BlogComment> selectBlogCommentList(BlogComment blogComment) {
        return blogCommentMapper.selectBlogCommentList(blogComment);
    }

    @Override
    public List<BlogComment> selectApprovedCommentTreeByArticleId(Long articleId) {
        List<BlogComment> comments = blogCommentMapper.selectApprovedCommentListByArticleId(articleId);
        if (comments == null || comments.isEmpty()) {
            return new ArrayList<>();
        }

        Map<Long, BlogComment> commentMap = new LinkedHashMap<>();
        for (BlogComment comment : comments) {
            comment.setChildren(new ArrayList<>());
            commentMap.put(comment.getCommentId(), comment);
        }

        List<BlogComment> roots = new ArrayList<>();
        for (BlogComment comment : comments) {
            Long parentId = comment.getParentId();
            if (parentId == null || parentId == 0L) {
                roots.add(comment);
                continue;
            }
            BlogComment parent = commentMap.get(parentId);
            if (parent == null) {
                roots.add(comment);
            } else {
                parent.getChildren().add(comment);
            }
        }
        return roots;
    }

    @Override
    @Transactional
    public int insertBlogComment(BlogComment blogComment) {
        blogComment.setCreateTime(DateUtils.getNowDate());
        if (StringUtils.isBlank(blogComment.getStatus())) {
            blogComment.setStatus("0");
        }
        int rows = blogCommentMapper.insertBlogComment(blogComment);
        if (rows > 0) {
            refreshArticleCommentCount(blogComment.getArticleId());
        }
        return rows;
    }

    @Override
    @Transactional
    public int updateBlogComment(BlogComment blogComment) {
        BlogComment old = null;
        if (blogComment.getCommentId() != null) {
            old = blogCommentMapper.selectBlogCommentByCommentId(blogComment.getCommentId());
        }
        int rows = blogCommentMapper.updateBlogComment(blogComment);
        if (rows > 0) {
            if (old != null && !java.util.Objects.equals(old.getArticleId(), blogComment.getArticleId())) {
                refreshArticleCommentCount(old.getArticleId());
            }
            refreshArticleCommentCount(blogComment.getArticleId());
        }
        return rows;
    }

    @Override
    @Transactional
    public int updateBlogCommentStatus(BlogComment blogComment) {
        Long articleId = blogCommentMapper.selectArticleIdByCommentId(blogComment.getCommentId());
        int rows = blogCommentMapper.updateBlogCommentStatus(blogComment);
        if (rows > 0) {
            refreshArticleCommentCount(articleId);
        }
        return rows;
    }

    @Override
    @Transactional
    public int deleteBlogCommentByCommentIds(Long[] commentIds) {
        List<Long> articleIds = blogCommentMapper.selectArticleIdsByCommentIds(commentIds);
        int rows = blogCommentMapper.deleteBlogCommentByCommentIds(commentIds);
        if (rows > 0) {
            refreshArticleCommentCounts(articleIds);
        }
        return rows;
    }

    @Override
    @Transactional
    public int deleteBlogCommentByCommentId(Long commentId) {
        Long articleId = blogCommentMapper.selectArticleIdByCommentId(commentId);
        int rows = blogCommentMapper.deleteBlogCommentByCommentId(commentId);
        if (rows > 0) {
            refreshArticleCommentCount(articleId);
        }
        return rows;
    }

    private void refreshArticleCommentCounts(List<Long> articleIds) {
        if (articleIds == null || articleIds.isEmpty()) {
            return;
        }
        Set<Long> uniqueIds = new LinkedHashSet<>();
        for (Long articleId : articleIds) {
            if (articleId != null) {
                uniqueIds.add(articleId);
            }
        }
        for (Long articleId : uniqueIds) {
            blogCommentMapper.refreshArticleCommentCount(articleId);
        }
    }

    private void refreshArticleCommentCount(Long articleId) {
        if (articleId != null) {
            blogCommentMapper.refreshArticleCommentCount(articleId);
        }
    }
}
