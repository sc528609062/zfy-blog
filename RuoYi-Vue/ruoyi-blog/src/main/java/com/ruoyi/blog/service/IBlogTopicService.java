package com.ruoyi.blog.service;

import java.util.List;
import com.ruoyi.blog.domain.BlogTopic;

/**
 * 博客专题Service接口
 *
 * @author zfy
 * @date 2026-01-12
 */
public interface IBlogTopicService {
    /**
     * 查询博客专题
     *
     * @param topicId 博客专题主键
     * @return 博客专题
     */
    public BlogTopic selectBlogTopicByTopicId(Long topicId);

    /**
     * 查询博客专题列表
     *
     * @param blogTopic 博客专题
     * @return 博客专题集合
     */
    public List<BlogTopic> selectBlogTopicList(BlogTopic blogTopic);

    /**
     * 新增博客专题
     *
     * @param blogTopic 博客专题
     * @return 结果
     */
    public int insertBlogTopic(BlogTopic blogTopic);

    /**
     * 修改博客专题
     *
     * @param blogTopic 博客专题
     * @return 结果
     */
    public int updateBlogTopic(BlogTopic blogTopic);

    /**
     * 批量删除博客专题
     *
     * @param topicIds 需要删除的博客专题主键集合
     * @return 结果
     */
    public int deleteBlogTopicByTopicIds(Long[] topicIds);

    /**
     * 删除博客专题信息
     *
     * @param topicId 博客专题主键
     * @return 结果
     */
    public int deleteBlogTopicByTopicId(Long topicId);

    /**
     * 同步专题文章数量
     *
     * @return 结果
     */
    public void syncTopicArticleCount();
}
