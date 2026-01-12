package com.ruoyi.blog.domain;

import com.ruoyi.common.annotation.Excel;
import com.ruoyi.common.core.domain.BaseEntity;
import org.apache.commons.lang3.builder.ToStringBuilder;
import org.apache.commons.lang3.builder.ToStringStyle;

/**
 * 博客文章对象 blog_article
 *
 * @author zfy
 * @date 2026-01-12
 */
public class BlogArticle extends BaseEntity {
    private static final long serialVersionUID = 1L;

    /** 文章ID */
    private Long articleId;

    /** 文章标题 */
    @Excel(name = "文章标题")
    private String articleTitle;

    /** 文章内容 */
    private String articleContent;

    /** 文章摘要 */
    private String articleSummary;

    /** 封面图片 */
    @Excel(name = "封面图片")
    private String articleCover;

    /** 分类ID */
    @Excel(name = "分类ID")
    private Long categoryId;

    /** 作者ID */
    private Long authorId;

    /** 作者名称 */
    @Excel(name = "作者名称")
    private String authorName;

    /** 浏览量 */
    @Excel(name = "浏览量")
    private Long viewCount;

    /** 点赞数 */
    @Excel(name = "点赞数")
    private Integer likeCount;

    /** 评论数 */
    @Excel(name = "评论数")
    private Integer commentCount;

    /** 是否置顶（0否 1是） */
    @Excel(name = "是否置顶", readConverterExp = "0=否,1=是")
    private String isTop;

    /** 是否推荐（0否 1是） */
    @Excel(name = "是否推荐", readConverterExp = "0=否,1=是")
    private String isRecommend;

    /** 是否原创（0转载 1原创） */
    @Excel(name = "是否原创", readConverterExp = "0=转载,1=原创")
    private String isOriginal;

    /** 转载来源URL */
    @Excel(name = "转载来源URL")
    private String sourceUrl;

    /** 文章状态（0草稿 1发布 2下架） */
    @Excel(name = "文章状态", readConverterExp = "0=草稿,1=发布,2=下架")
    private String articleStatus;

    /** 发布时间 */
    private java.util.Date publishTime;

    /** 删除标志（0代表存在 2代表删除） */
    private String delFlag;

    /** 分类名称 */
    @Excel(name = "分类名称")
    private String categoryName;

    public void setArticleId(Long articleId) {
        this.articleId = articleId;
    }

    public Long getArticleId() {
        return articleId;
    }

    public void setArticleTitle(String articleTitle) {
        this.articleTitle = articleTitle;
    }

    public String getArticleTitle() {
        return articleTitle;
    }

    public void setArticleContent(String articleContent) {
        this.articleContent = articleContent;
    }

    public String getArticleContent() {
        return articleContent;
    }

    public void setArticleSummary(String articleSummary) {
        this.articleSummary = articleSummary;
    }

    public String getArticleSummary() {
        return articleSummary;
    }

    public void setArticleCover(String articleCover) {
        this.articleCover = articleCover;
    }

    public String getArticleCover() {
        return articleCover;
    }

    public void setCategoryId(Long categoryId) {
        this.categoryId = categoryId;
    }

    public Long getCategoryId() {
        return categoryId;
    }

    public void setAuthorId(Long authorId) {
        this.authorId = authorId;
    }

    public Long getAuthorId() {
        return authorId;
    }

    public void setAuthorName(String authorName) {
        this.authorName = authorName;
    }

    public String getAuthorName() {
        return authorName;
    }

    public void setViewCount(Long viewCount) {
        this.viewCount = viewCount;
    }

    public Long getViewCount() {
        return viewCount;
    }

    public void setLikeCount(Integer likeCount) {
        this.likeCount = likeCount;
    }

    public Integer getLikeCount() {
        return likeCount;
    }

    public void setCommentCount(Integer commentCount) {
        this.commentCount = commentCount;
    }

    public Integer getCommentCount() {
        return commentCount;
    }

    public void setIsTop(String isTop) {
        this.isTop = isTop;
    }

    public String getIsTop() {
        return isTop;
    }

    public void setIsRecommend(String isRecommend) {
        this.isRecommend = isRecommend;
    }

    public String getIsRecommend() {
        return isRecommend;
    }

    public void setIsOriginal(String isOriginal) {
        this.isOriginal = isOriginal;
    }

    public String getIsOriginal() {
        return isOriginal;
    }

    public void setSourceUrl(String sourceUrl) {
        this.sourceUrl = sourceUrl;
    }

    public String getSourceUrl() {
        return sourceUrl;
    }

    public void setArticleStatus(String articleStatus) {
        this.articleStatus = articleStatus;
    }

    public String getArticleStatus() {
        return articleStatus;
    }

    public void setPublishTime(java.util.Date publishTime) {
        this.publishTime = publishTime;
    }

    public java.util.Date getPublishTime() {
        return publishTime;
    }

    public void setDelFlag(String delFlag) {
        this.delFlag = delFlag;
    }

    public String getDelFlag() {
        return delFlag;
    }

    public void setCategoryName(String categoryName) {
        this.categoryName = categoryName;
    }

    public String getCategoryName() {
        return categoryName;
    }

    @Override
    public String toString() {
        return new ToStringBuilder(this, ToStringStyle.MULTI_LINE_STYLE)
                .append("articleId", getArticleId())
                .append("articleTitle", getArticleTitle())
                .append("articleContent", getArticleContent())
                .append("articleSummary", getArticleSummary())
                .append("articleCover", getArticleCover())
                .append("categoryId", getCategoryId())
                .append("authorId", getAuthorId())
                .append("authorName", getAuthorName())
                .append("viewCount", getViewCount())
                .append("likeCount", getLikeCount())
                .append("commentCount", getCommentCount())
                .append("isTop", getIsTop())
                .append("isRecommend", getIsRecommend())
                .append("isOriginal", getIsOriginal())
                .append("sourceUrl", getSourceUrl())
                .append("articleStatus", getArticleStatus())
                .append("publishTime", getPublishTime())
                .append("createBy", getCreateBy())
                .append("createTime", getCreateTime())
                .append("updateBy", getUpdateBy())
                .append("updateTime", getUpdateTime())
                .append("delFlag", getDelFlag())
                .toString();
    }
}
