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
import com.ruoyi.blog.domain.BlogTag;
import com.ruoyi.blog.service.IBlogTagService;
import com.ruoyi.common.utils.poi.ExcelUtil;
import com.ruoyi.common.core.page.TableDataInfo;

/**
 * 博客标签Controller
 *
 * @author zfy
 * @date 2026-01-12
 */
@RestController
@RequestMapping("/blog/tag")
public class BlogTagController extends BaseController {
    @Autowired
    private IBlogTagService blogTagService;

    /**
     * 查询博客标签列表（无需权限）
     */
    @Anonymous
    @GetMapping("/list")
    public TableDataInfo list(BlogTag blogTag) {
        startPage();
        List<BlogTag> list = blogTagService.selectBlogTagList(blogTag);
        return getDataTable(list);
    }

    /**
     * 获取博客标签详细信息（前台匿名访问）
     */
    @Anonymous
    @GetMapping(value = "/{tagId}")
    public AjaxResult getInfo(@PathVariable("tagId") Long tagId) {
        return success(blogTagService.selectBlogTagByTagId(tagId));
    }

    /**
     * 新增博客标签
     */
    @PreAuthorize("@ss.hasPermi('blog:tag:add')")
    @Log(title = "博客标签", businessType = BusinessType.INSERT)
    @PostMapping
    public AjaxResult add(@RequestBody BlogTag blogTag) {
        return toAjax(blogTagService.insertBlogTag(blogTag));
    }

    /**
     * 修改博客标签
     */
    @PreAuthorize("@ss.hasPermi('blog:tag:edit')")
    @Log(title = "博客标签", businessType = BusinessType.UPDATE)
    @PutMapping
    public AjaxResult edit(@RequestBody BlogTag blogTag) {
        return toAjax(blogTagService.updateBlogTag(blogTag));
    }

    /**
     * 删除博客标签
     */
    @PreAuthorize("@ss.hasPermi('blog:tag:remove')")
    @Log(title = "博客标签", businessType = BusinessType.DELETE)
    @DeleteMapping("/{tagIds}")
    public AjaxResult remove(@PathVariable Long[] tagIds) {
        return toAjax(blogTagService.deleteBlogTagByTagIds(tagIds));
    }
}
