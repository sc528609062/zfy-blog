-- 为现有文章设置默认编辑器类型
UPDATE blog_article SET editor_type = 'markdown' WHERE editor_type IS NULL OR editor_type = '';
