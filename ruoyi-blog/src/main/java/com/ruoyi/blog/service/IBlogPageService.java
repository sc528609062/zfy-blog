package com.ruoyi.blog.service;

import com.ruoyi.blog.domain.BlogPage;
import java.util.List;

/**
 * Blog page service.
 */
public interface IBlogPageService {
    /**
     * Query page by ID.
     *
     * @param pageId page ID
     * @return page
     */
    BlogPage selectBlogPageByPageId(Long pageId);

    /**
     * Query page list.
     *
     * @param blogPage query conditions
     * @return page list
     */
    List<BlogPage> selectBlogPageList(BlogPage blogPage);

    /**
     * Insert page.
     *
     * @param blogPage page
     * @return affected rows
     */
    int insertBlogPage(BlogPage blogPage);

    /**
     * Update page.
     *
     * @param blogPage page
     * @return affected rows
     */
    int updateBlogPage(BlogPage blogPage);

    /**
     * Soft delete pages by IDs.
     *
     * @param pageIds page IDs
     * @return affected rows
     */
    int deleteBlogPageByPageIds(Long[] pageIds);

    /**
     * Soft delete page by ID.
     *
     * @param pageId page ID
     * @return affected rows
     */
    int deleteBlogPageByPageId(Long pageId);
}
