package com.ruoyi.blog.controller;

import com.ruoyi.blog.domain.BlogPage;
import com.ruoyi.blog.service.IBlogPageService;
import com.ruoyi.common.annotation.Anonymous;
import com.ruoyi.common.annotation.Log;
import com.ruoyi.common.core.controller.BaseController;
import com.ruoyi.common.core.domain.AjaxResult;
import com.ruoyi.common.core.page.TableDataInfo;
import com.ruoyi.common.enums.BusinessType;
import java.util.List;
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

/**
 * Blog page controller.
 */
@RestController
@RequestMapping("/blog/page")
public class BlogPageController extends BaseController {
    @Autowired
    private IBlogPageService blogPageService;

    /**
     * Query page list.
     */
    @Anonymous
    @GetMapping("/list")
    public TableDataInfo list(BlogPage blogPage) {
        startPage();
        List<BlogPage> list = blogPageService.selectBlogPageList(blogPage);
        return getDataTable(list);
    }

    /**
     * Query page detail.
     */
    @Anonymous
    @GetMapping("/{pageId}")
    public AjaxResult getInfo(@PathVariable("pageId") Long pageId) {
        return success(blogPageService.selectBlogPageByPageId(pageId));
    }

    /**
     * Add page.
     */
    @PreAuthorize("@ss.hasPermi('blog:page:add')")
    @Log(title = "Blog Page", businessType = BusinessType.INSERT)
    @PostMapping
    public AjaxResult add(@RequestBody BlogPage blogPage) {
        return toAjax(blogPageService.insertBlogPage(blogPage));
    }

    /**
     * Update page.
     */
    @PreAuthorize("@ss.hasPermi('blog:page:edit')")
    @Log(title = "Blog Page", businessType = BusinessType.UPDATE)
    @PutMapping
    public AjaxResult edit(@RequestBody BlogPage blogPage) {
        return toAjax(blogPageService.updateBlogPage(blogPage));
    }

    /**
     * Delete pages.
     */
    @PreAuthorize("@ss.hasPermi('blog:page:remove')")
    @Log(title = "Blog Page", businessType = BusinessType.DELETE)
    @DeleteMapping("/{pageIds}")
    public AjaxResult remove(@PathVariable Long[] pageIds) {
        return toAjax(blogPageService.deleteBlogPageByPageIds(pageIds));
    }
}
