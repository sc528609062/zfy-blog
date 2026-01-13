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
import com.ruoyi.blog.domain.BlogTopic;
import com.ruoyi.blog.service.IBlogTopicService;
import com.ruoyi.common.utils.poi.ExcelUtil;
import com.ruoyi.common.core.page.TableDataInfo;

/**
 * 博客专题Controller
 *
 * @author zfy
 * @date 2026-01-12
 */
@RestController
@RequestMapping("/blog/topic")
public class BlogTopicController extends BaseController {
    @Autowired
    private IBlogTopicService blogTopicService;

    /**
     * 查询博客专题列表
     */
    @PreAuthorize("@ss.hasPermi('blog:topic:list')")
    @GetMapping("/list")
    public TableDataInfo list(BlogTopic blogTopic) {
        startPage();
        List<BlogTopic> list = blogTopicService.selectBlogTopicList(blogTopic);
        return getDataTable(list);
    }

    /**
     * 导出博客专题列表
     */
    @PreAuthorize("@ss.hasPermi('blog:topic:export')")
    @Log(title = "博客专题", businessType = BusinessType.EXPORT)
    @PostMapping("/export")
    public void export(HttpServletResponse response, BlogTopic blogTopic) {
        List<BlogTopic> list = blogTopicService.selectBlogTopicList(blogTopic);
        ExcelUtil<BlogTopic> util = new ExcelUtil<BlogTopic>(BlogTopic.class);
        util.exportExcel(response, list, "博客专题数据");
    }

    /**
     * 获取博客专题详细信息
     */
    @PreAuthorize("@ss.hasPermi('blog:topic:query')")
    @GetMapping(value = "/{topicId}")
    public AjaxResult getInfo(@PathVariable("topicId") Long topicId) {
        return success(blogTopicService.selectBlogTopicByTopicId(topicId));
    }

    /**
     * 前台获取博客专题详细信息（匿名访问）
     */
    @Anonymous
    @GetMapping(value = "/public/{topicId}")
    public AjaxResult getPublicInfo(@PathVariable("topicId") Long topicId) {
        return success(blogTopicService.selectBlogTopicByTopicId(topicId));
    }

    /**
     * 前台获取博客专题列表（匿名访问）
     */
    @Anonymous
    @GetMapping("/public/list")
    public TableDataInfo publicList(BlogTopic blogTopic) {
        startPage();
        List<BlogTopic> list = blogTopicService.selectBlogTopicList(blogTopic);
        return getDataTable(list);
    }

    /**
     * 新增博客专题
     */
    @PreAuthorize("@ss.hasPermi('blog:topic:add')")
    @Log(title = "博客专题", businessType = BusinessType.INSERT)
    @PostMapping
    public AjaxResult add(@RequestBody BlogTopic blogTopic) {
        return toAjax(blogTopicService.insertBlogTopic(blogTopic));
    }

    /**
     * 修改博客专题
     */
    @PreAuthorize("@ss.hasPermi('blog:topic:edit')")
    @Log(title = "博客专题", businessType = BusinessType.UPDATE)
    @PutMapping
    public AjaxResult edit(@RequestBody BlogTopic blogTopic) {
        return toAjax(blogTopicService.updateBlogTopic(blogTopic));
    }

    /**
     * 删除博客专题
     */
    @PreAuthorize("@ss.hasPermi('blog:topic:remove')")
    @Log(title = "博客专题", businessType = BusinessType.DELETE)
    @DeleteMapping("/{topicIds}")
    public AjaxResult remove(@PathVariable Long[] topicIds) {
        return toAjax(blogTopicService.deleteBlogTopicByTopicIds(topicIds));
    }

    /**
     * 同步专题文章数量
     */
    @PreAuthorize("@ss.hasPermi('blog:topic:edit')")
    @Log(title = "博客专题", businessType = BusinessType.UPDATE)
    @GetMapping("/syncCount")
    public AjaxResult syncCount() {
        blogTopicService.syncTopicArticleCount();
        return success("同步成功");
    }
}
