-- 手动更新所有分类的文章数量
UPDATE blog_category c
LEFT JOIN (
    SELECT category_id, COUNT(*) as count
    FROM blog_article
    WHERE del_flag = '0' AND article_status = '1'
    GROUP BY category_id
) a ON c.category_id = a.category_id
SET c.article_count = IFNULL(a.count, 0)
WHERE c.del_flag = '0';
