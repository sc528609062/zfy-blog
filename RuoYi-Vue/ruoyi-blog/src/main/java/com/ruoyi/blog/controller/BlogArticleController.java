package com.ruoyi.blog.controller;

import java.util.List;
import javax.servlet.http.HttpServletResponse;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.PutMapping;
import org.springframework.web.bind.annotation.DeleteMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;
import com.ruoyi.common.annotation.Anonymous;
import com.ruoyi.common.annotation.Log;
import com.ruoyi.common.core.controller.BaseController;
import com.ruoyi.common.core.domain.AjaxResult;
import com.ruoyi.common.enums.BusinessType;
import com.ruoyi.blog.domain.BlogArticle;
import com.ruoyi.blog.service.IBlogArticleService;
import com.ruoyi.common.utils.poi.ExcelUtil;
import com.ruoyi.common.core.page.TableDataInfo;

/**
 * 博客文章Controller
 *
 * @author zfy
 * @date 2026-01-12
 */
@RestController
@RequestMapping("/blog/article")
public class BlogArticleController extends BaseController {
    @Autowired
    private IBlogArticleService blogArticleService;

    /**
     * 前台获取博客文章详细信息（匿名访问）
     */
    @Anonymous
    @GetMapping(value = "/{articleId}")
    public AjaxResult getArticleInfo(@PathVariable("articleId") Long articleId) {
        return success(blogArticleService.selectBlogArticleByArticleId(articleId));
    }

    /**
     * 前台获取博客文章列表（匿名访问）
     */
    @Anonymous
    @GetMapping("/public/list")
    public TableDataInfo publicList(BlogArticle blogArticle) {
        startPage();
        List<BlogArticle> list = blogArticleService.selectBlogArticleList(blogArticle);
        return getDataTable(list);
    }

    /**
     * 查询博客文章列表
     */
    @PreAuthorize("@ss.hasPermi('blog:article:list')")
    @GetMapping("/list")
    public TableDataInfo list(BlogArticle blogArticle) {
        startPage();
        List<BlogArticle> list = blogArticleService.selectBlogArticleList(blogArticle);
        return getDataTable(list);
    }

    /**
     * 导出博客文章列表
     */
    @PreAuthorize("@ss.hasPermi('blog:article:export')")
    @Log(title = "博客文章", businessType = BusinessType.EXPORT)
    @PostMapping("/export")
    public void export(HttpServletResponse response, BlogArticle blogArticle) {
        List<BlogArticle> list = blogArticleService.selectBlogArticleList(blogArticle);
        ExcelUtil<BlogArticle> util = new ExcelUtil<BlogArticle>(BlogArticle.class);
        util.exportExcel(response, list, "博客文章数据");
    }

    /**
     * 获取博客文章详细信息
     */
    @PreAuthorize("@ss.hasPermi('blog:article:query')")
    @GetMapping(value = "/detail/{articleId}")
    public AjaxResult getInfo(@PathVariable("articleId") Long articleId) {
        return success(blogArticleService.selectBlogArticleByArticleId(articleId));
    }

    /**
     * 新增博客文章
     */
    @PreAuthorize("@ss.hasPermi('blog:article:add')")
    @Log(title = "博客文章", businessType = BusinessType.INSERT)
    @PostMapping
    public AjaxResult add(@RequestBody BlogArticle blogArticle) {
        return toAjax(blogArticleService.insertBlogArticle(blogArticle));
    }

    /**
     * 修改博客文章
     */
    @PreAuthorize("@ss.hasPermi('blog:article:edit')")
    @Log(title = "博客文章", businessType = BusinessType.UPDATE)
    @PutMapping
    public AjaxResult edit(@RequestBody BlogArticle blogArticle) {
        return toAjax(blogArticleService.updateBlogArticle(blogArticle));
    }

    /**
     * 删除博客文章
     */
    @PreAuthorize("@ss.hasPermi('blog:article:remove')")
    @Log(title = "博客文章", businessType = BusinessType.DELETE)
    @DeleteMapping("/{articleIds}")
    public AjaxResult remove(@PathVariable Long[] articleIds) {
        return toAjax(blogArticleService.deleteBlogArticleByArticleIds(articleIds));
    }

    /**
     * 增加浏览量
     */
    @PostMapping("/view/{articleId}")
    public AjaxResult incrementView(@PathVariable Long articleId) {
        blogArticleService.incrementViewCount(articleId);
        return success();
    }

    /**
     * 批量发布文章
     */
    @PreAuthorize("@ss.hasPermi('blog:article:edit')")
    @Log(title = "批量发布文章", businessType = BusinessType.UPDATE)
    @PutMapping("/publish")
    public AjaxResult publish(@RequestBody Long[] articleIds) {
        return toAjax(blogArticleService.updateArticleStatus(articleIds, "1"));
    }

    /**
     * 批量下架文章
     */
    @PreAuthorize("@ss.hasPermi('blog:article:edit')")
    @Log(title = "批量下架文章", businessType = BusinessType.UPDATE)
    @PutMapping("/offline")
    public AjaxResult offline(@RequestBody Long[] articleIds) {
        return toAjax(blogArticleService.updateArticleStatus(articleIds, "2"));
    }

    /**
     * 获取统计数据
     */
    @PreAuthorize("@ss.hasPermi('blog:article:list')")
    @GetMapping("/statistics")
    public AjaxResult statistics() {
        return success(blogArticleService.getStatistics());
    }
}
