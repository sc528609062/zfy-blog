package com.ruoyi.blog.controller;

import com.ruoyi.blog.service.IBlogArticleFavoriteService;
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
 * 文章收藏 Controller。
 */
@RestController
@RequestMapping("/blog/article/favorite")
public class BlogArticleFavoriteController extends BaseController {
    @Autowired
    private IBlogArticleFavoriteService blogArticleFavoriteService;

    /**
     * 收藏文章。
     */
    @Anonymous
    @Log(title = "文章收藏", businessType = BusinessType.OTHER)
    @PostMapping("/{articleId}")
    public AjaxResult favorite(@PathVariable Long articleId, HttpServletRequest request) {
        Long userId = resolveUserId(request);
        String userIp = IpUtils.getIpAddr(request);
        blogArticleFavoriteService.favoriteArticle(articleId, userId, userIp);
        return success();
    }

    /**
     * 取消收藏。
     */
    @Anonymous
    @Log(title = "取消收藏", businessType = BusinessType.OTHER)
    @DeleteMapping("/{articleId}")
    public AjaxResult unfavorite(@PathVariable Long articleId, HttpServletRequest request) {
        Long userId = resolveUserId(request);
        String userIp = IpUtils.getIpAddr(request);
        blogArticleFavoriteService.unfavoriteArticle(articleId, userId, userIp);
        return success();
    }

    /**
     * 检查是否已收藏。
     */
    @Anonymous
    @GetMapping("/check/{articleId}")
    public AjaxResult checkFavorited(@PathVariable Long articleId, HttpServletRequest request) {
        Long userId = resolveUserId(request);
        String userIp = IpUtils.getIpAddr(request);
        boolean favorited = blogArticleFavoriteService.isFavorited(articleId, userId, userIp);
        return success().put("favorited", favorited);
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
