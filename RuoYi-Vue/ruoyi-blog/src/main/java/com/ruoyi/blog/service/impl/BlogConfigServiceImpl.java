package com.ruoyi.blog.service.impl;

import java.util.List;
import com.ruoyi.common.utils.DateUtils;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import com.ruoyi.blog.mapper.BlogConfigMapper;
import com.ruoyi.blog.domain.BlogConfig;
import com.ruoyi.blog.service.IBlogConfigService;

/**
 * 博客配置Service业务层处理
 *
 * @author zfy
 * @date 2026-01-12
 */
@Service
public class BlogConfigServiceImpl implements IBlogConfigService {
    @Autowired
    private BlogConfigMapper blogConfigMapper;

    /**
     * 查询博客配置
     *
     * @param configId 博客配置主键
     * @return 博客配置
     */
    @Override
    public BlogConfig selectBlogConfigByConfigId(Long configId) {
        return blogConfigMapper.selectBlogConfigByConfigId(configId);
    }

    /**
     * 根据配置键查询博客配置
     *
     * @param configKey 配置键
     * @return 博客配置
     */
    @Override
    public BlogConfig selectBlogConfigByKey(String configKey) {
        return blogConfigMapper.selectBlogConfigByKey(configKey);
    }

    /**
     * 查询博客配置列表
     *
     * @param blogConfig 博客配置
     * @return 博客配置
     */
    @Override
    public List<BlogConfig> selectBlogConfigList(BlogConfig blogConfig) {
        return blogConfigMapper.selectBlogConfigList(blogConfig);
    }

    /**
     * 新增博客配置
     *
     * @param blogConfig 博客配置
     * @return 结果
     */
    @Override
    public int insertBlogConfig(BlogConfig blogConfig) {
        blogConfig.setCreateTime(DateUtils.getNowDate());
        return blogConfigMapper.insertBlogConfig(blogConfig);
    }

    /**
     * 修改博客配置
     *
     * @param blogConfig 博客配置
     * @return 结果
     */
    @Override
    public int updateBlogConfig(BlogConfig blogConfig) {
        blogConfig.setUpdateTime(DateUtils.getNowDate());
        return blogConfigMapper.updateBlogConfig(blogConfig);
    }

    /**
     * 批量删除博客配置
     *
     * @param configIds 需要删除的博客配置主键
     * @return 结果
     */
    @Override
    public int deleteBlogConfigByConfigIds(Long[] configIds) {
        return blogConfigMapper.deleteBlogConfigByConfigIds(configIds);
    }

    /**
     * 删除博客配置信息
     *
     * @param configId 博客配置主键
     * @return 结果
     */
    @Override
    public int deleteBlogConfigByConfigId(Long configId) {
        return blogConfigMapper.deleteBlogConfigByConfigId(configId);
    }
}
