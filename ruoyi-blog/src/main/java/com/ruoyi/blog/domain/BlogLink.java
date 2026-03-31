package com.ruoyi.blog.domain;

import com.ruoyi.common.annotation.Excel;
import com.ruoyi.common.core.domain.BaseEntity;
import org.apache.commons.lang3.builder.ToStringBuilder;
import org.apache.commons.lang3.builder.ToStringStyle;

/**
 * Blog friend-link entity.
 */
public class BlogLink extends BaseEntity {
    private static final long serialVersionUID = 1L;

    /** Link ID */
    private Long linkId;

    /** Link name */
    @Excel(name = "Link Name")
    private String linkName;

    /** Link URL */
    @Excel(name = "Link URL")
    private String linkUrl;

    /** Link logo URL */
    private String linkLogo;

    /** Link description */
    @Excel(name = "Link Description")
    private String linkDesc;

    /** Sort order */
    @Excel(name = "Sort Order")
    private Integer sortOrder;

    /** Status: 0-enabled, 1-disabled */
    @Excel(name = "Status", readConverterExp = "0=Enabled,1=Disabled")
    private String status;

    /** Delete flag: 0-exists, 2-deleted */
    private String delFlag;

    public Long getLinkId() {
        return linkId;
    }

    public void setLinkId(Long linkId) {
        this.linkId = linkId;
    }

    public String getLinkName() {
        return linkName;
    }

    public void setLinkName(String linkName) {
        this.linkName = linkName;
    }

    public String getLinkUrl() {
        return linkUrl;
    }

    public void setLinkUrl(String linkUrl) {
        this.linkUrl = linkUrl;
    }

    public String getLinkLogo() {
        return linkLogo;
    }

    public void setLinkLogo(String linkLogo) {
        this.linkLogo = linkLogo;
    }

    public String getLinkDesc() {
        return linkDesc;
    }

    public void setLinkDesc(String linkDesc) {
        this.linkDesc = linkDesc;
    }

    public Integer getSortOrder() {
        return sortOrder;
    }

    public void setSortOrder(Integer sortOrder) {
        this.sortOrder = sortOrder;
    }

    public String getStatus() {
        return status;
    }

    public void setStatus(String status) {
        this.status = status;
    }

    public String getDelFlag() {
        return delFlag;
    }

    public void setDelFlag(String delFlag) {
        this.delFlag = delFlag;
    }

    @Override
    public String toString() {
        return new ToStringBuilder(this, ToStringStyle.MULTI_LINE_STYLE)
                .append("linkId", getLinkId())
                .append("linkName", getLinkName())
                .append("linkUrl", getLinkUrl())
                .append("linkLogo", getLinkLogo())
                .append("linkDesc", getLinkDesc())
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
