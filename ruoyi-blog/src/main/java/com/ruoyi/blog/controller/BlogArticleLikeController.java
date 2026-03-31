package com.ruoyi.blog.controller;

import com.ruoyi.blog.service.IBlogArticleLikeService;
import com.ruoyi.common.annotation.Anonymous;
import com.ruoyi.common.annotation.Log;
import com.ruoyi.common.core.controller.BaseController;
import com.ruoyi.common.core.domain.AjaxResult;
import com.ruoyi.common.enums.BusinessType;
import com.ruoyi.common.utils.SecurityUtils;
import com.ruoyi.common.utils.StringUtils;
import com.ruoyi.common.utils.ip.IpUtils;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.web.bind.annotation.DeleteMapping;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import javax.servlet.http.HttpServletRequest;

/**
 * 文章点赞 Controller。
 */
@RestController
@RequestMapping("/blog/article/like")
public class BlogArticleLikeController extends BaseController {
    @Autowired
    private IBlogArticleLikeService blogArticleLikeService;

    /**
     * 点赞文章。
     */
    @Anonymous
    @Log(title = "文章点赞", businessType = BusinessType.OTHER)
    @PostMapping("/{articleId}")
    public AjaxResult like(@PathVariable Long articleId, HttpServletRequest request) {
        Long userId = resolveUserId(request);
        String userIp = IpUtils.getIpAddr(request);
        blogArticleLikeService.likeArticle(articleId, userId, userIp);
        return success();
    }

    /**
     * 取消点赞。
     */
    @Anonymous
    @Log(title = "取消点赞", businessType = BusinessType.OTHER)
    @DeleteMapping("/{articleId}")
    public AjaxResult unlike(@PathVariable Long articleId, HttpServletRequest request) {
        Long userId = resolveUserId(request);
        String userIp = IpUtils.getIpAddr(request);
        blogArticleLikeService.unlikeArticle(articleId, userId, userIp);
        return success();
    }

    /**
     * 检查是否已点赞。
     */
    @Anonymous
    @GetMapping("/check/{articleId}")
    public AjaxResult checkLiked(@PathVariable Long articleId, HttpServletRequest request) {
        Long userId = resolveUserId(request);
        String userIp = IpUtils.getIpAddr(request);
        boolean liked = blogArticleLikeService.isLiked(articleId, userId, userIp);
        return success().put("liked", liked);
    }

    /**
     * 解析用户ID：优先读取当前登录用户；未登录时回退请求头 userId。
     */
    private Long resolveUserId(HttpServletRequest request) {
        try {
            return SecurityUtils.getUserId();
        } catch (Exception ignored) {
            String userIdHeader = request.getHeader("userId");
            if (StringUtils.isNotBlank(userIdHeader)) {
                try {
                    return Long.valueOf(userIdHeader);
                } catch (NumberFormatException ex) {
                    return null;
                }
            }
            return null;
        }
    }
}
