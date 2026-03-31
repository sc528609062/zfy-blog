package com.ruoyi.blog.domain;

import com.ruoyi.common.annotation.Excel;
import com.ruoyi.common.core.domain.BaseEntity;
import org.apache.commons.lang3.builder.ToStringBuilder;
import org.apache.commons.lang3.builder.ToStringStyle;
import java.util.List;

/**
 * Blog comment entity.
 */
public class BlogComment extends BaseEntity {
    private static final long serialVersionUID = 1L;

    /** Comment ID */
    private Long commentId;

    /** Article ID */
    @Excel(name = "Article ID")
    private Long articleId;

    /** Article title (joined for management page) */
    @Excel(name = "Article Title")
    private String articleTitle;

    /** Parent comment ID */
    private Long parentId;

    /** Comment content */
    @Excel(name = "Comment Content")
    private String commentContent;

    /** User ID */
    private Long userId;

    /** Username */
    @Excel(name = "Username")
    private String userName;

    /** User email */
    private String userEmail;

    /** User avatar */
    private String userAvatar;

    /** Like count */
    @Excel(name = "Like Count")
    private Integer likeCount;

    /** Status: 0-pending, 1-approved, 2-rejected */
    @Excel(name = "Status", readConverterExp = "0=Pending,1=Approved,2=Rejected")
    private String status;

    /** IP address */
    private String ipAddress;

    /** Delete flag: 0-exists, 2-deleted */
    private String delFlag;

    /** Child comments for tree rendering */
    private List<BlogComment> children;

    public Long getCommentId() {
        return commentId;
    }

    public void setCommentId(Long commentId) {
        this.commentId = commentId;
    }

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

    public Long getParentId() {
        return parentId;
    }

    public void setParentId(Long parentId) {
        this.parentId = parentId;
    }

    public String getCommentContent() {
        return commentContent;
    }

    public void setCommentContent(String commentContent) {
        this.commentContent = commentContent;
    }

    public Long getUserId() {
        return userId;
    }

    public void setUserId(Long userId) {
        this.userId = userId;
    }

    public String getUserName() {
        return userName;
    }

    public void setUserName(String userName) {
        this.userName = userName;
    }

    public String getUserEmail() {
        return userEmail;
    }

    public void setUserEmail(String userEmail) {
        this.userEmail = userEmail;
    }

    public String getUserAvatar() {
        return userAvatar;
    }

    public void setUserAvatar(String userAvatar) {
        this.userAvatar = userAvatar;
    }

    public Integer getLikeCount() {
        return likeCount;
    }

    public void setLikeCount(Integer likeCount) {
        this.likeCount = likeCount;
    }

    public String getStatus() {
        return status;
    }

    public void setStatus(String status) {
        this.status = status;
    }

    public String getIpAddress() {
        return ipAddress;
    }

    public void setIpAddress(String ipAddress) {
        this.ipAddress = ipAddress;
    }

    public String getDelFlag() {
        return delFlag;
    }

    public void setDelFlag(String delFlag) {
        this.delFlag = delFlag;
    }

    public List<BlogComment> getChildren() {
        return children;
    }

    public void setChildren(List<BlogComment> children) {
        this.children = children;
    }

    @Override
    public String toString() {
        return new ToStringBuilder(this, ToStringStyle.MULTI_LINE_STYLE)
                .append("commentId", getCommentId())
                .append("articleId", getArticleId())
                .append("articleTitle", getArticleTitle())
                .append("parentId", getParentId())
                .append("commentContent", getCommentContent())
                .append("userId", getUserId())
                .append("userName", getUserName())
                .append("userEmail", getUserEmail())
                .append("userAvatar", getUserAvatar())
                .append("likeCount", getLikeCount())
                .append("status", getStatus())
                .append("ipAddress", getIpAddress())
                .append("createTime", getCreateTime())
                .append("delFlag", getDelFlag())
                .append("children", getChildren())
                .toString();
    }
}
