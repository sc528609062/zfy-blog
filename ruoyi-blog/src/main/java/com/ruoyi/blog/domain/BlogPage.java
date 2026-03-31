package com.ruoyi.blog.domain;

import com.ruoyi.common.annotation.Excel;
import com.ruoyi.common.core.domain.BaseEntity;
import org.apache.commons.lang3.builder.ToStringBuilder;
import org.apache.commons.lang3.builder.ToStringStyle;

/**
 * Blog custom page entity.
 */
public class BlogPage extends BaseEntity {
    private static final long serialVersionUID = 1L;

    /** Page ID */
    private Long pageId;

    /** Page title */
    @Excel(name = "Page Title")
    private String pageTitle;

    /** Page slug */
    @Excel(name = "Page Slug")
    private String pageSlug;

    /** Page content */
    private String pageContent;

    /** Page cover URL */
    private String pageCover;

    /** Sort order */
    @Excel(name = "Sort Order")
    private Integer sortOrder;

    /** Show in menu: 0-no, 1-yes */
    @Excel(name = "Show In Menu", readConverterExp = "0=No,1=Yes")
    private String showInMenu;

    /** Status: 0-draft, 1-published */
    @Excel(name = "Status", readConverterExp = "0=Draft,1=Published")
    private String status;

    /** Delete flag: 0-exists, 2-deleted */
    private String delFlag;

    public Long getPageId() {
        return pageId;
    }

    public void setPageId(Long pageId) {
        this.pageId = pageId;
    }

    public String getPageTitle() {
        return pageTitle;
    }

    public void setPageTitle(String pageTitle) {
        this.pageTitle = pageTitle;
    }

    public String getPageSlug() {
        return pageSlug;
    }

    public void setPageSlug(String pageSlug) {
        this.pageSlug = pageSlug;
    }

    public String getPageContent() {
        return pageContent;
    }

    public void setPageContent(String pageContent) {
        this.pageContent = pageContent;
    }

    public String getPageCover() {
        return pageCover;
    }

    public void setPageCover(String pageCover) {
        this.pageCover = pageCover;
    }

    public Integer getSortOrder() {
        return sortOrder;
    }

    public void setSortOrder(Integer sortOrder) {
        this.sortOrder = sortOrder;
    }

    public String getShowInMenu() {
        return showInMenu;
    }

    public void setShowInMenu(String showInMenu) {
        this.showInMenu = showInMenu;
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
                .append("pageId", getPageId())
                .append("pageTitle", getPageTitle())
                .append("pageSlug", getPageSlug())
                .append("pageContent", getPageContent())
                .append("pageCover", getPageCover())
                .append("sortOrder", getSortOrder())
                .append("showInMenu", getShowInMenu())
                .append("status", getStatus())
                .append("createBy", getCreateBy())
                .append("createTime", getCreateTime())
                .append("updateBy", getUpdateBy())
                .append("updateTime", getUpdateTime())
                .append("delFlag", getDelFlag())
                .toString();
    }
}
