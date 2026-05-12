<?php

namespace App\Actions\Content;

use App\Models\Content;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Mews\Purifier\Facades\Purifier;

/**
 * 创建/更新内容。
 *
 * 输入：
 *   $data = [
 *     type, status, title, slug, excerpt, cover_url, category_id,
 *     visibility, access_password, price, points_price,
 *     comment_policy, download_policy,
 *     seo_title, seo_description, seo_keywords,
 *     block_json (TipTap 源数据，权威),
 *     rendered_html (前端预渲染),
 *     markdown_cache,
 *     tag_names (string[] 简单标签，自动 firstOrCreate),
 *   ];
 *
 * 行为：
 *   - HTML 清洗（防 XSS）
 *   - slug 唯一
 *   - 写 plain_text（用于搜索）
 *   - 同步 tags
 */
class SaveContent
{
    public function execute(array $data, User $author, ?Content $content = null): Content
    {
        return DB::transaction(function () use ($data, $author, $content) {
            $content ??= new Content();
            $content->type = $data['type'] ?? $content->type ?? Content::TYPE_POST;
            $content->status = $data['status'] ?? $content->status ?? Content::STATUS_DRAFT;
            $content->title = $data['title'] ?? $content->title ?? '无标题';
            if (! empty($data['slug'])) {
                $content->slug = $data['slug'];
            }
            $content->excerpt = $data['excerpt'] ?? $content->excerpt;
            $content->cover_url = $data['cover_url'] ?? $content->cover_url;
            $content->category_id = $data['category_id'] ?? $content->category_id;

            $content->visibility = $data['visibility'] ?? $content->visibility ?? Content::VISIBILITY_PUBLIC;
            $content->access_password = $data['access_password'] ?? $content->access_password;
            $content->price = $data['price'] ?? $content->price ?? 0;
            $content->points_price = $data['points_price'] ?? $content->points_price ?? 0;

            $content->comment_policy = $data['comment_policy'] ?? $content->comment_policy ?? 'open';
            $content->download_policy = $data['download_policy'] ?? $content->download_policy ?? 'open';

            $content->seo_title = $data['seo_title'] ?? $content->seo_title;
            $content->seo_description = $data['seo_description'] ?? $content->seo_description;
            $content->seo_keywords = $data['seo_keywords'] ?? $content->seo_keywords;

            $content->block_json = $data['block_json'] ?? $content->block_json;
            $content->markdown_cache = $data['markdown_cache'] ?? $content->markdown_cache;

            $rawHtml = $data['rendered_html'] ?? $content->rendered_html ?? '';
            $content->rendered_html = $rawHtml ? Purifier::clean($rawHtml) : null;

            $content->author_id ??= $author->id;
            if (! $content->published_at && $content->status === Content::STATUS_PUBLISHED) {
                $content->published_at = now();
            }
            $content->save();

            // 同步标签（按名字）
            if (isset($data['tag_names']) && is_array($data['tag_names'])) {
                $tagIds = [];
                foreach ($data['tag_names'] as $name) {
                    $name = trim($name);
                    if ($name === '') {
                        continue;
                    }
                    $tag = \App\Models\Tag::firstOrCreate(
                        ['name' => $name],
                        ['slug' => \Illuminate\Support\Str::slug($name) ?: 't-' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(6))]
                    );
                    $tagIds[] = $tag->id;
                }
                $content->tags()->sync($tagIds);
            }

            return $content->refresh();
        });
    }
}
