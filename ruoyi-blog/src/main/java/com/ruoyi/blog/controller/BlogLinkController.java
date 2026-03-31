package com.ruoyi.blog.controller;

import com.ruoyi.blog.domain.BlogLink;
import com.ruoyi.blog.service.IBlogLinkService;
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
 * Blog link controller.
 */
@RestController
@RequestMapping("/blog/link")
public class BlogLinkController extends BaseController {
    @Autowired
    private IBlogLinkService blogLinkService;

    /**
     * Query link list.
     */
    @Anonymous
    @GetMapping("/list")
    public TableDataInfo list(BlogLink blogLink) {
        startPage();
        List<BlogLink> list = blogLinkService.selectBlogLinkList(blogLink);
        return getDataTable(list);
    }

    /**
     * Query link detail.
     */
    @Anonymous
    @GetMapping("/{linkId}")
    public AjaxResult getInfo(@PathVariable("linkId") Long linkId) {
        return success(blogLinkService.selectBlogLinkByLinkId(linkId));
    }

    /**
     * Add link.
     */
    @PreAuthorize("@ss.hasPermi('blog:link:add')")
    @Log(title = "Blog Link", businessType = BusinessType.INSERT)
    @PostMapping
    public AjaxResult add(@RequestBody BlogLink blogLink) {
        return toAjax(blogLinkService.insertBlogLink(blogLink));
    }

    /**
     * Update link.
     */
    @PreAuthorize("@ss.hasPermi('blog:link:edit')")
    @Log(title = "Blog Link", businessType = BusinessType.UPDATE)
    @PutMapping
    public AjaxResult edit(@RequestBody BlogLink blogLink) {
        return toAjax(blogLinkService.updateBlogLink(blogLink));
    }

    /**
     * Delete links.
     */
    @PreAuthorize("@ss.hasPermi('blog:link:remove')")
    @Log(title = "Blog Link", businessType = BusinessType.DELETE)
    @DeleteMapping("/{linkIds}")
    public AjaxResult remove(@PathVariable Long[] linkIds) {
        return toAjax(blogLinkService.deleteBlogLinkByLinkIds(linkIds));
    }
}
