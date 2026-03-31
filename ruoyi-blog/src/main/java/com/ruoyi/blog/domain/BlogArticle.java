package com.ruoyi.blog.domain;

import com.ruoyi.common.annotation.Excel;
import com.ruoyi.common.core.domain.BaseEntity;
import org.apache.commons.lang3.builder.ToStringBuilder;
import org.apache.commons.lang3.builder.ToStringStyle;

import java.util.Date;
import java.util.List;

/**
 * Blog article entity.
 */
public class BlogArticle extends BaseEntity {
    private static final long serialVersionUID = 1L;

    private Long articleId;

    @Excel(name = "Article Title")
    private String articleTitle;

    private String articleContent;

    private String articleSummary;

    @Excel(name = "Article Cover")
    private String articleCover;

    @Excel(name = "Category ID")
    private Long categoryId;

    @Excel(name = "Topic ID")
    private Long topicId;

    private Long authorId;

    @Excel(name = "Author Name")
    private String authorName;

    @Excel(name = "View Count")
    private Long viewCount;

    @Excel(name = "Like Count")
    private Integer likeCount;

    @Excel(name = "Favorite Count")
    private Integer favoriteCount;

    @Excel(name = "Comment Count")
    private Integer commentCount;

    @Excel(name = "Is Top")
    private String isTop;

    @Excel(name = "Is Recommend")
    private String isRecommend;

    @Excel(name = "Is Original")
    private String isOriginal;

    @Excel(name = "Source Url")
    private String sourceUrl;

    @Excel(name = "Article Status")
    private String articleStatus;

    private Long[] tagIds;

    private List<BlogTag> tags;

    private Date publishTime;

    private String delFlag;

    @Excel(name = "Category Name")
    private String categoryName;

    @Excel(name = "Topic Name")
    private String topicName;

    @Excel(name = "Editor Type")
    private String editorType;

    public Long getArticleId() {
        return articleId;
    }

    public void setArticleId(Long articleId) {
        this.articleId = articleId;
    }

    public String getArticleTitle() {
        return articleTitle;
    }

    public void setArticleTitle(String articleTitle) {
        this.articleTitle = articleTitle;
    }

    public String getArticleContent() {
        return articleContent;
    }

    public void setArticleContent(String articleContent) {
        this.articleContent = articleContent;
    }

    public String getArticleSummary() {
        return articleSummary;
    }

    public void setArticleSummary(String articleSummary) {
        this.articleSummary = articleSummary;
    }

    public String getArticleCover() {
        return articleCover;
    }

    public void setArticleCover(String articleCover) {
        this.articleCover = articleCover;
    }

    public Long getCategoryId() {
        return categoryId;
    }

    public void setCategoryId(Long categoryId) {
        this.categoryId = categoryId;
    }

    public Long getTopicId() {
        return topicId;
    }

    public void setTopicId(Long topicId) {
        this.topicId = topicId;
    }

    public Long getAuthorId() {
        return authorId;
    }

    public void setAuthorId(Long authorId) {
        this.authorId = authorId;
    }

    public String getAuthorName() {
        return authorName;
    }

    public void setAuthorName(String authorName) {
        this.authorName = authorName;
    }

    public Long getViewCount() {
        return viewCount;
    }

    public void setViewCount(Long viewCount) {
        this.viewCount = viewCount;
    }

    public Integer getLikeCount() {
        return likeCount;
    }

    public void setLikeCount(Integer likeCount) {
        this.likeCount = likeCount;
    }

    public Integer getFavoriteCount() {
        return favoriteCount;
    }

    public void setFavoriteCount(Integer favoriteCount) {
        this.favoriteCount = favoriteCount;
    }

    public Integer getCommentCount() {
        return commentCount;
    }

    public void setCommentCount(Integer commentCount) {
        this.commentCount = commentCount;
    }

    public String getIsTop() {
        return isTop;
    }

    public void setIsTop(String isTop) {
        this.isTop = isTop;
    }

    public String getIsRecommend() {
        return isRecommend;
    }

    public void setIsRecommend(String isRecommend) {
        this.isRecommend = isRecommend;
    }

    public String getIsOriginal() {
        return isOriginal;
    }

    public void setIsOriginal(String isOriginal) {
        this.isOriginal = isOriginal;
    }

    public String getSourceUrl() {
        return sourceUrl;
    }

    public void setSourceUrl(String sourceUrl) {
        this.sourceUrl = sourceUrl;
    }

    public String getArticleStatus() {
        return articleStatus;
    }

    public void setArticleStatus(String articleStatus) {
        this.articleStatus = articleStatus;
    }

    public Long[] getTagIds() {
        return tagIds;
    }

    public void setTagIds(Long[] tagIds) {
        this.tagIds = tagIds;
    }

    public List<BlogTag> getTags() {
        return tags;
    }

    public void setTags(List<BlogTag> tags) {
        this.tags = tags;
    }

    public Date getPublishTime() {
        return publishTime;
    }

    public void setPublishTime(Date publishTime) {
        this.publishTime = publishTime;
    }

    public String getDelFlag() {
        return delFlag;
    }

    public void setDelFlag(String delFlag) {
        this.delFlag = delFlag;
    }

    public String getCategoryName() {
        return categoryName;
    }

    public void setCategoryName(String categoryName) {
        this.categoryName = categoryName;
    }

    public String getTopicName() {
        return topicName;
    }

    public void setTopicName(String topicName) {
        this.topicName = topicName;
    }

    public String getEditorType() {
        return editorType;
    }

    public void setEditorType(String editorType) {
        this.editorType = editorType;
    }

    @Override
    public String toString() {
        return new ToStringBuilder(this, ToStringStyle.MULTI_LINE_STYLE)
            .append("articleId", articleId)
            .append("articleTitle", articleTitle)
            .append("articleContent", articleContent)
            .append("articleSummary", articleSummary)
            .append("articleCover", articleCover)
            .append("categoryId", categoryId)
            .append("topicId", topicId)
            .append("authorId", authorId)
            .append("authorName", authorName)
            .append("viewCount", viewCount)
            .append("likeCount", likeCount)
            .append("favoriteCount", favoriteCount)
            .append("commentCount", commentCount)
            .append("isTop", isTop)
            .append("isRecommend", isRecommend)
            .append("isOriginal", isOriginal)
            .append("sourceUrl", sourceUrl)
            .append("articleStatus", articleStatus)
            .append("tagIds", tagIds)
            .append("publishTime", publishTime)
            .append("createBy", getCreateBy())
            .append("createTime", getCreateTime())
            .append("updateBy", getUpdateBy())
            .append("updateTime", getUpdateTime())
            .append("delFlag", delFlag)
            .toString();
    }
}