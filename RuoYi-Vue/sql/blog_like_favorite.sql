-- 文章点赞表
CREATE TABLE IF NOT EXISTS `blog_article_like` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `article_id` bigint(20) NOT NULL COMMENT '文章ID',
  `user_id` bigint(20) DEFAULT NULL COMMENT '用户ID(登录用户)',
  `user_ip` varchar(50) DEFAULT NULL COMMENT '用户IP(未登录用户)',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_article_user_ip` (`article_id`, `user_id`, `user_ip`),
  KEY `idx_article_id` (`article_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='文章点赞表';

-- 文章收藏表
CREATE TABLE IF NOT EXISTS `blog_article_favorite` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `article_id` bigint(20) NOT NULL COMMENT '文章ID',
  `user_id` bigint(20) DEFAULT NULL COMMENT '用户ID(登录用户)',
  `user_ip` varchar(50) DEFAULT NULL COMMENT '用户IP(未登录用户)',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_article_user_ip` (`article_id`, `user_id`, `user_ip`),
  KEY `idx_article_id` (`article_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='文章收藏表';
