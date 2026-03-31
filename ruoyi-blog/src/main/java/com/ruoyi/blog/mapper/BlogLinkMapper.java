package com.ruoyi.blog.mapper;

import com.ruoyi.blog.domain.BlogLink;
import java.util.List;

/**
 * Blog link mapper.
 */
public interface BlogLinkMapper {
    /**
     * Query link by ID.
     *
     * @param linkId link ID
     * @return link
     */
    BlogLink selectBlogLinkByLinkId(Long linkId);

    /**
     * Query link list.
     *
     * @param blogLink query conditions
     * @return link list
     */
    List<BlogLink> selectBlogLinkList(BlogLink blogLink);

    /**
     * Insert link.
     *
     * @param blogLink link
     * @return affected rows
     */
    int insertBlogLink(BlogLink blogLink);

    /**
     * Update link.
     *
     * @param blogLink link
     * @return affected rows
     */
    int updateBlogLink(BlogLink blogLink);

    /**
     * Soft delete link by ID.
     *
     * @param linkId link ID
     * @return affected rows
     */
    int deleteBlogLinkByLinkId(Long linkId);

    /**
     * Soft delete links by IDs.
     *
     * @param linkIds link IDs
     * @return affected rows
     */
    int deleteBlogLinkByLinkIds(Long[] linkIds);
}
