package com.ruoyi.blog.controller;

import com.ruoyi.common.annotation.Anonymous;
import com.ruoyi.common.annotation.Log;
import com.ruoyi.common.core.controller.BaseController;
import com.ruoyi.common.core.domain.AjaxResult;
import com.ruoyi.common.enums.BusinessType;
import com.ruoyi.blog.service.IBlogArticleLikeService;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.web.bind.annotation.*;

import javax.servlet.http.HttpServletRequest;

/**
 * 文章点赞Controller
 *
 * @author zfy
 * @date 2026-01-13
 */
@RestController
@RequestMapping("/blog/article/like")
public class BlogArticleLikeController extends BaseController {
    @Autowired
    private IBlogArticleLikeService blogArticleLikeService;

    /**
     * 点赞文章
     */
    @Anonymous
    @Log(title = "文章点赞", businessType = BusinessType.OTHER)
    @PostMapping("/{articleId}")
    public AjaxResult like(@PathVariable Long articleId,
                         @RequestHeader(value = "userId", required = false) Long userId,
                         HttpServletRequest request) {
        String userIp = getClientIp(request);
        blogArticleLikeService.likeArticle(articleId, userId, userIp);
        return success();
    }

    /**
     * 取消点赞
     */
    @Anonymous
    @Log(title = "取消点赞", businessType = BusinessType.OTHER)
    @DeleteMapping("/{articleId}")
    public AjaxResult unlike(@PathVariable Long articleId,
                           @RequestHeader(value = "userId", required = false) Long userId,
                           HttpServletRequest request) {
        String userIp = getClientIp(request);
        blogArticleLikeService.unlikeArticle(articleId, userId, userIp);
        return success();
    }

    /**
     * 检查是否已点赞
     */
    @Anonymous
    @GetMapping("/check/{articleId}")
    public AjaxResult checkLiked(@PathVariable Long articleId,
                               @RequestHeader(value = "userId", required = false) Long userId,
                               HttpServletRequest request) {
        String userIp = getClientIp(request);
        boolean liked = blogArticleLikeService.isLiked(articleId, userId, userIp);
        return success().put("liked", liked);
    }

    /**
     * 获取客户端IP
     */
    private String getClientIp(HttpServletRequest request) {
        String ip = request.getHeader("X-Forwarded-For");
        if (ip == null || ip.length() == 0 || "unknown".equalsIgnoreCase(ip)) {
            ip = request.getHeader("Proxy-Client-IP");
        }
        if (ip == null || ip.length() == 0 || "unknown".equalsIgnoreCase(ip)) {
            ip = request.getHeader("WL-Proxy-Client-IP");
        }
        if (ip == null || ip.length() == 0 || "unknown".equalsIgnoreCase(ip)) {
            ip = request.getRemoteAddr();
        }
        // 多个IP时取第一个
        if (ip != null && ip.contains(",")) {
            ip = ip.split(",")[0].trim();
        }
        return ip;
    }
}
