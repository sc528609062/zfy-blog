package com.ruoyi.blog.domain;

import com.ruoyi.common.annotation.Excel;
import com.ruoyi.common.core.domain.BaseEntity;
import org.apache.commons.lang3.builder.ToStringBuilder;
import org.apache.commons.lang3.builder.ToStringStyle;

/**
 * 博客专题对象 blog_topic
 *
 * @author zfy
 * @date 2026-01-12
 */
public class BlogTopic extends BaseEntity {
    private static final long serialVersionUID = 1L;

    /** 专题ID */
    private Long topicId;

    /** 专题标题 */
    @Excel(name = "专题标题")
    private String topicTitle;

    /** 专题封面 */
    @Excel(name = "专题封面")
    private String topicCover;

    /** 专题简介 */
    @Excel(name = "专题简介")
    private String topicSummary;

    /** 专题描述 */
    @Excel(name = "专题描述")
    private String topicDesc;

    /** 文章数量 */
    @Excel(name = "文章数量")
    private Integer articleCount;

    /** 显示顺序 */
    @Excel(name = "显示顺序")
    private Integer sortOrder;

    /** 状态（0正常 1停用） */
    @Excel(name = "状态", readConverterExp = "0=正常,1=停用")
    private String status;

    /** 删除标志（0代表存在 2代表删除） */
    private String delFlag;

    public void setTopicId(Long topicId) {
        this.topicId = topicId;
    }

    public Long getTopicId() {
        return topicId;
    }

    public void setTopicTitle(String topicTitle) {
        this.topicTitle = topicTitle;
    }

    public String getTopicTitle() {
        return topicTitle;
    }

    public void setTopicCover(String topicCover) {
        this.topicCover = topicCover;
    }

    public String getTopicCover() {
        return topicCover;
    }

    public void setTopicSummary(String topicSummary) {
        this.topicSummary = topicSummary;
    }

    public String getTopicSummary() {
        return topicSummary;
    }

    public void setTopicDesc(String topicDesc) {
        this.topicDesc = topicDesc;
    }

    public String getTopicDesc() {
        return topicDesc;
    }

    public void setArticleCount(Integer articleCount) {
        this.articleCount = articleCount;
    }

    public Integer getArticleCount() {
        return articleCount;
    }

    public void setSortOrder(Integer sortOrder) {
        this.sortOrder = sortOrder;
    }

    public Integer getSortOrder() {
        return sortOrder;
    }

    public void setStatus(String status) {
        this.status = status;
    }

    public String getStatus() {
        return status;
    }

    public void setDelFlag(String delFlag) {
        this.delFlag = delFlag;
    }

    public String getDelFlag() {
        return delFlag;
    }

    @Override
    public String toString() {
        return new ToStringBuilder(this, ToStringStyle.MULTI_LINE_STYLE)
                .append("topicId", getTopicId())
                .append("topicTitle", getTopicTitle())
                .append("topicCover", getTopicCover())
                .append("topicSummary", getTopicSummary())
                .append("topicDesc", getTopicDesc())
                .append("articleCount", getArticleCount())
                .append("sortOrder", getSortOrder())
                .append("status", getStatus())
                .append("createBy", getCreateBy())
                .append("createTime", getCreateTime())
                .append("updateBy", getUpdateBy())
                .append("updateTime", getUpdateTime())
                .append("delFlag", getDelFlag())
                .toString();
    }
}
