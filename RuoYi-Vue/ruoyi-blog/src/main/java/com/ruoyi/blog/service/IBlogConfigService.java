package com.ruoyi.blog.service;

import java.util.List;
import com.ruoyi.blog.domain.BlogConfig;

/**
 * 博客配置Service接口
 *
 * @author zfy
 * @date 2026-01-12
 */
public interface IBlogConfigService {
    /**
     * 查询博客配置
     *
     * @param configId 博客配置主键
     * @return 博客配置
     */
    public BlogConfig selectBlogConfigByConfigId(Long configId);

    /**
     * 根据配置键查询博客配置
     *
     * @param configKey 配置键
     * @return 博客配置
     */
    public BlogConfig selectBlogConfigByKey(String configKey);

    /**
     * 查询博客配置列表
     *
     * @param blogConfig 博客配置
     * @return 博客配置集合
     */
    public List<BlogConfig> selectBlogConfigList(BlogConfig blogConfig);

    /**
     * 新增博客配置
     *
     * @param blogConfig 博客配置
     * @return 结果
     */
    public int insertBlogConfig(BlogConfig blogConfig);

    /**
     * 修改博客配置
     *
     * @param blogConfig 博客配置
     * @return 结果
     */
    public int updateBlogConfig(BlogConfig blogConfig);

    /**
     * 批量删除博客配置
     *
     * @param configIds 需要删除的博客配置主键集合
     * @return 结果
     */
    public int deleteBlogConfigByConfigIds(Long[] configIds);

    /**
     * 删除博客配置信息
     *
     * @param configId 博客配置主键
     * @return 结果
     */
    public int deleteBlogConfigByConfigId(Long configId);
}
