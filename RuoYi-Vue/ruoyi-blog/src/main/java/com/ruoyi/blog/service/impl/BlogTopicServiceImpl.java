package com.ruoyi.blog.service.impl;

import java.util.List;
import com.ruoyi.common.utils.DateUtils;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import com.ruoyi.blog.mapper.BlogTopicMapper;
import com.ruoyi.blog.domain.BlogTopic;
import com.ruoyi.blog.service.IBlogTopicService;

/**
 * 博客专题Service业务层处理
 *
 * @author zfy
 * @date 2026-01-12
 */
@Service
public class BlogTopicServiceImpl implements IBlogTopicService {
    @Autowired
    private BlogTopicMapper blogTopicMapper;

    /**
     * 查询博客专题
     *
     * @param topicId 博客专题主键
     * @return 博客专题
     */
    @Override
    public BlogTopic selectBlogTopicByTopicId(Long topicId) {
        return blogTopicMapper.selectBlogTopicByTopicId(topicId);
    }

    /**
     * 查询博客专题列表
     *
     * @param blogTopic 博客专题
     * @return 博客专题
     */
    @Override
    public List<BlogTopic> selectBlogTopicList(BlogTopic blogTopic) {
        return blogTopicMapper.selectBlogTopicList(blogTopic);
    }

    /**
     * 新增博客专题
     *
     * @param blogTopic 博客专题
     * @return 结果
     */
    @Override
    public int insertBlogTopic(BlogTopic blogTopic) {
        blogTopic.setCreateTime(DateUtils.getNowDate());
        return blogTopicMapper.insertBlogTopic(blogTopic);
    }

    /**
     * 修改博客专题
     *
     * @param blogTopic 博客专题
     * @return 结果
     */
    @Override
    public int updateBlogTopic(BlogTopic blogTopic) {
        blogTopic.setUpdateTime(DateUtils.getNowDate());
        return blogTopicMapper.updateBlogTopic(blogTopic);
    }

    /**
     * 批量删除博客专题
     *
     * @param topicIds 需要删除的博客专题主键
     * @return 结果
     */
    @Override
    public int deleteBlogTopicByTopicIds(Long[] topicIds) {
        return blogTopicMapper.deleteBlogTopicByTopicIds(topicIds);
    }

    /**
     * 删除博客专题信息
     *
     * @param topicId 博客专题主键
     * @return 结果
     */
    @Override
    public int deleteBlogTopicByTopicId(Long topicId) {
        return blogTopicMapper.deleteBlogTopicByTopicId(topicId);
    }
}
