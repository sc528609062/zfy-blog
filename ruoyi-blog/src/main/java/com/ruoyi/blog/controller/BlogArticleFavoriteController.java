package com.ruoyi.blog.controller;

import com.ruoyi.common.annotation.Anonymous;
import com.ruoyi.common.annotation.Log;
import com.ruoyi.common.core.controller.BaseController;
import com.ruoyi.common.core.domain.AjaxResult;
import com.ruoyi.common.enums.BusinessType;
import com.ruoyi.blog.service.IBlogArticleFavoriteService;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.web.bind.annotation.*;

import javax.servlet.http.HttpServletRequest;

/**
 * 文章收藏Controller
 *
 * @author zfy
 * @date 2026-01-13
 */
@RestController
@RequestMapping("/blog/article/favorite")
public class BlogArticleFavoriteController extends BaseController {
    @Autowired
    private IBlogArticleFavoriteService blogArticleFavoriteService;

    /**
     * 收藏文章
     */
    @Anonymous
    @Log(title = "文章收藏", businessType = BusinessType.OTHER)
    @PostMapping("/{articleId}")
    public AjaxResult favorite(@PathVariable Long articleId,
                             @RequestHeader(value = "userId", required = false) Long userId,
                             HttpServletRequest request) {
        String userIp = getClientIp(request);
        blogArticleFavoriteService.favoriteArticle(articleId, userId, userIp);
        return success();
    }

    /**
     * 取消收藏
     */
    @Anonymous
    @Log(title = "取消收藏", businessType = BusinessType.OTHER)
    @DeleteMapping("/{articleId}")
    public AjaxResult unfavorite(@PathVariable Long articleId,
                               @RequestHeader(value = "userId", required = false) Long userId,
                               HttpServletRequest request) {
        String userIp = getClientIp(request);
        blogArticleFavoriteService.unfavoriteArticle(articleId, userId, userIp);
        return success();
    }

    /**
     * 检查是否已收藏
     */
    @Anonymous
    @GetMapping("/check/{articleId}")
    public AjaxResult checkFavorited(@PathVariable Long articleId,
                                    @RequestHeader(value = "userId", required = false) Long userId,
                                    HttpServletRequest request) {
        String userIp = getClientIp(request);
        boolean favorited = blogArticleFavoriteService.isFavorited(articleId, userId, userIp);
        return success().put("favorited", favorited);
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
