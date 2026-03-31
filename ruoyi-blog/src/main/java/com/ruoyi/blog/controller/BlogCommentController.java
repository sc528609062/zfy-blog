package com.ruoyi.blog.controller;

import com.ruoyi.blog.domain.BlogArticle;
import com.ruoyi.blog.domain.BlogComment;
import com.ruoyi.blog.domain.BlogConfig;
import com.ruoyi.blog.service.IBlogArticleService;
import com.ruoyi.blog.service.IBlogCommentService;
import com.ruoyi.blog.service.IBlogConfigService;
import com.ruoyi.common.annotation.Anonymous;
import com.ruoyi.common.annotation.Log;
import com.ruoyi.common.core.controller.BaseController;
import com.ruoyi.common.core.domain.AjaxResult;
import com.ruoyi.common.core.domain.model.LoginUser;
import com.ruoyi.common.core.page.TableDataInfo;
import com.ruoyi.common.enums.BusinessType;
import com.ruoyi.common.utils.SecurityUtils;
import com.ruoyi.common.utils.StringUtils;
import com.ruoyi.common.utils.ip.IpUtils;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.web.bind.annotation.DeleteMapping;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.PutMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import javax.servlet.http.HttpServletRequest;
import java.util.List;

/**
 * Blog comment controller.
 */
@RestController
@RequestMapping("/blog/comment")
public class BlogCommentController extends BaseController {
    @Autowired
    private IBlogCommentService blogCommentService;

    @Autowired
    private IBlogConfigService blogConfigService;

    @Autowired
    private IBlogArticleService blogArticleService;

    /**
     * Query comment list (admin).
     */
    @GetMapping("/list")
    public TableDataInfo list(BlogComment blogComment) {
        startPage();
        List<BlogComment> list = blogCommentService.selectBlogCommentList(blogComment);
        return getDataTable(list);
    }

    /**
     * Query approved comment tree for article (public).
     */
    @Anonymous
    @GetMapping("/public/list/{articleId}")
    public AjaxResult publicList(@PathVariable Long articleId) {
        return success(blogCommentService.selectApprovedCommentTreeByArticleId(articleId));
    }

    /**
     * Query comment detail.
     */
    @GetMapping("/{commentId}")
    public AjaxResult getInfo(@PathVariable("commentId") Long commentId) {
        return success(blogCommentService.selectBlogCommentByCommentId(commentId));
    }

    /**
     * Add comment (public submit).
     */
    @Anonymous
    @PostMapping("/public")
    public AjaxResult publicAdd(@RequestBody BlogComment blogComment, HttpServletRequest request) {
        if (blogComment == null || blogComment.getArticleId() == null) {
            return error("文章ID不能为空");
        }
        if (!isCommentAllowed()) {
            return error("评论功能已关闭");
        }

        BlogArticle article = blogArticleService.selectBlogArticleByArticleId(blogComment.getArticleId());
        if (article == null || !"1".equals(article.getArticleStatus())) {
            return error("文章不存在或未发布");
        }

        String content = StringUtils.trim(blogComment.getCommentContent());
        if (StringUtils.isBlank(content)) {
            return error("评论内容不能为空");
        }
        if (content.length() > 2000) {
            return error("评论内容不能超过2000字符");
        }
        blogComment.setCommentContent(content);

        Long parentId = blogComment.getParentId();
        if (parentId == null || parentId < 0) {
            parentId = 0L;
        }
        if (parentId > 0) {
            BlogComment parent = blogCommentService.selectBlogCommentByCommentId(parentId);
            if (parent == null || !blogComment.getArticleId().equals(parent.getArticleId())) {
                return error("回复的评论不存在");
            }
            if (!"1".equals(parent.getStatus())) {
                return error("只能回复已通过的评论");
            }
        }
        blogComment.setParentId(parentId);

        LoginUser loginUser = getCurrentLoginUser();
        if (loginUser != null && loginUser.getUserId() != null) {
            blogComment.setUserId(loginUser.getUserId());
            String nickName = loginUser.getUser() != null ? loginUser.getUser().getNickName() : null;
            blogComment.setUserName(StringUtils.defaultIfBlank(nickName, loginUser.getUsername()));
            if (loginUser.getUser() != null) {
                blogComment.setUserEmail(loginUser.getUser().getEmail());
                blogComment.setUserAvatar(loginUser.getUser().getAvatar());
            }
        } else {
            String userName = StringUtils.trim(blogComment.getUserName());
            if (StringUtils.isBlank(userName)) {
                return error("请输入昵称");
            }
            blogComment.setUserId(null);
            blogComment.setUserName(StringUtils.substring(userName, 0, 64));
            if (StringUtils.isNotBlank(blogComment.getUserEmail())) {
                blogComment.setUserEmail(StringUtils.substring(blogComment.getUserEmail().trim(), 0, 128));
            }
            blogComment.setUserAvatar(null);
        }

        blogComment.setLikeCount(0);
        blogComment.setIpAddress(IpUtils.getIpAddr(request));
        blogComment.setStatus(isCommentAuditRequired() ? "0" : "1");

        int rows = blogCommentService.insertBlogComment(blogComment);
        if (rows <= 0) {
            return error("评论提交失败");
        }

        return success()
                .put("commentId", blogComment.getCommentId())
                .put("status", blogComment.getStatus())
                .put("pending", "0".equals(blogComment.getStatus()));
    }

    /**
     * Add comment.
     */
    @PostMapping
    public AjaxResult add(@RequestBody BlogComment blogComment) {
        return toAjax(blogCommentService.insertBlogComment(blogComment));
    }

    /**
     * Update comment.
     */
    @PreAuthorize("@ss.hasPermi('blog:comment:edit')")
    @Log(title = "Blog Comment", businessType = BusinessType.UPDATE)
    @PutMapping
    public AjaxResult edit(@RequestBody BlogComment blogComment) {
        return toAjax(blogCommentService.updateBlogComment(blogComment));
    }

    /**
     * Update comment status.
     */
    @PreAuthorize("@ss.hasPermi('blog:comment:edit')")
    @Log(title = "Blog Comment", businessType = BusinessType.UPDATE)
    @PutMapping("/status")
    public AjaxResult editStatus(@RequestBody BlogComment blogComment) {
        return toAjax(blogCommentService.updateBlogCommentStatus(blogComment));
    }

    /**
     * Delete comments.
     */
    @PreAuthorize("@ss.hasPermi('blog:comment:remove')")
    @Log(title = "Blog Comment", businessType = BusinessType.DELETE)
    @DeleteMapping("/{commentIds}")
    public AjaxResult remove(@PathVariable Long[] commentIds) {
        return toAjax(blogCommentService.deleteBlogCommentByCommentIds(commentIds));
    }

    private boolean isCommentAllowed() {
        BlogConfig allow = blogConfigService.selectBlogConfigByKey("allow_comment");
        if (allow == null || StringUtils.isBlank(allow.getConfigValue())) {
            return true;
        }
        return !"0".equals(StringUtils.trim(allow.getConfigValue()));
    }

    private boolean isCommentAuditRequired() {
        BlogConfig audit = blogConfigService.selectBlogConfigByKey("comment_audit");
        if (audit == null || StringUtils.isBlank(audit.getConfigValue())) {
            return true;
        }
        return "1".equals(StringUtils.trim(audit.getConfigValue()));
    }

    private LoginUser getCurrentLoginUser() {
        try {
            return SecurityUtils.getLoginUser();
        } catch (Exception ignored) {
            return null;
        }
    }
}
