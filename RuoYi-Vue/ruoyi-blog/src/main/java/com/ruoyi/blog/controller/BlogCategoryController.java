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
import com.ruoyi.blog.domain.BlogCategory;
import com.ruoyi.blog.service.IBlogCategoryService;
import com.ruoyi.common.utils.poi.ExcelUtil;
import com.ruoyi.common.core.page.TableDataInfo;

/**
 * 博客分类Controller
 *
 * @author zfy
 * @date 2026-01-12
 */
@RestController
@RequestMapping("/blog/category")
public class BlogCategoryController extends BaseController {
    @Autowired
    private IBlogCategoryService blogCategoryService;

    /**
     * 查询博客分类列表（无需权限）
     */
    @Anonymous
    @GetMapping("/list")
    public TableDataInfo list(BlogCategory blogCategory) {
        startPage();
        List<BlogCategory> list = blogCategoryService.selectBlogCategoryList(blogCategory);
        return getDataTable(list);
    }

    /**
     * 获取博客分类详细信息（前台匿名访问）
     */
    @Anonymous
    @GetMapping(value = "/{categoryId}")
    public AjaxResult getInfo(@PathVariable("categoryId") Long categoryId) {
        return success(blogCategoryService.selectBlogCategoryByCategoryId(categoryId));
    }

    /**
     * 新增博客分类
     */
    @PreAuthorize("@ss.hasPermi('blog:category:add')")
    @Log(title = "博客分类", businessType = BusinessType.INSERT)
    @PostMapping
    public AjaxResult add(@RequestBody BlogCategory blogCategory) {
        return toAjax(blogCategoryService.insertBlogCategory(blogCategory));
    }

    /**
     * 修改博客分类
     */
    @PreAuthorize("@ss.hasPermi('blog:category:edit')")
    @Log(title = "博客分类", businessType = BusinessType.UPDATE)
    @PutMapping
    public AjaxResult edit(@RequestBody BlogCategory blogCategory) {
        return toAjax(blogCategoryService.updateBlogCategory(blogCategory));
    }

    /**
     * 删除博客分类
     */
    @PreAuthorize("@ss.hasPermi('blog:category:remove')")
    @Log(title = "博客分类", businessType = BusinessType.DELETE)
    @DeleteMapping("/{categoryIds}")
    public AjaxResult remove(@PathVariable Long[] categoryIds) {
        return toAjax(blogCategoryService.deleteBlogCategoryByCategoryIds(categoryIds));
    }

    /**
     * 同步所有分类的文章数量
     */
    @PreAuthorize("@ss.hasPermi('blog:category:edit')")
    @GetMapping("/syncCount")
    public AjaxResult syncCount() {
        blogCategoryService.updateAllCategoryArticleCount();
        return success("同步成功");
    }
}
