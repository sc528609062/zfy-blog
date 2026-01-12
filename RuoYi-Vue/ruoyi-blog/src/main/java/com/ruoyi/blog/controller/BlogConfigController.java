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
import com.ruoyi.common.annotation.Log;
import com.ruoyi.common.core.controller.BaseController;
import com.ruoyi.common.core.domain.AjaxResult;
import com.ruoyi.common.enums.BusinessType;
import com.ruoyi.blog.domain.BlogConfig;
import com.ruoyi.blog.service.IBlogConfigService;
import com.ruoyi.common.utils.poi.ExcelUtil;
import com.ruoyi.common.core.page.TableDataInfo;

/**
 * 博客配置Controller
 *
 * @author zfy
 * @date 2026-01-12
 */
@RestController
@RequestMapping("/blog/config")
public class BlogConfigController extends BaseController {
    @Autowired
    private IBlogConfigService blogConfigService;

    /**
     * 查询博客配置（根据configKey）
     */
    @GetMapping("/key/{configKey}")
    public AjaxResult getConfig(@PathVariable("configKey") String configKey) {
        BlogConfig config = blogConfigService.selectBlogConfigByKey(configKey);
        return success(config);
    }

    /**
     * 查询博客配置列表
     */
    @PreAuthorize("@ss.hasPermi('blog:config:list')")
    @GetMapping("/list")
    public TableDataInfo list(BlogConfig blogConfig) {
        startPage();
        List<BlogConfig> list = blogConfigService.selectBlogConfigList(blogConfig);
        return getDataTable(list);
    }

    /**
     * 获取博客配置详细信息（根据ID）
     */
    @PreAuthorize("@ss.hasPermi('blog:config:query')")
    @GetMapping(value = "/{configId}")
    public AjaxResult getInfo(@PathVariable("configId") Long configId) {
        return success(blogConfigService.selectBlogConfigByConfigId(configId));
    }

    /**
     * 新增博客配置
     */
    @PreAuthorize("@ss.hasPermi('blog:config:add')")
    @Log(title = "博客配置", businessType = BusinessType.INSERT)
    @PostMapping
    public AjaxResult add(@RequestBody BlogConfig blogConfig) {
        return toAjax(blogConfigService.insertBlogConfig(blogConfig));
    }

    /**
     * 修改博客配置
     */
    @PreAuthorize("@ss.hasPermi('blog:config:edit')")
    @Log(title = "博客配置", businessType = BusinessType.UPDATE)
    @PutMapping
    public AjaxResult edit(@RequestBody BlogConfig blogConfig) {
        return toAjax(blogConfigService.updateBlogConfig(blogConfig));
    }

    /**
     * 删除博客配置
     */
    @PreAuthorize("@ss.hasPermi('blog:config:remove')")
    @Log(title = "博客配置", businessType = BusinessType.DELETE)
    @DeleteMapping("/{configIds}")
    public AjaxResult remove(@PathVariable Long[] configIds) {
        return toAjax(blogConfigService.deleteBlogConfigByConfigIds(configIds));
    }
}
