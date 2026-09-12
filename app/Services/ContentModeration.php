<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class ContentModeration
{
    public function check(string $text, string $field = 'body'): bool
    {
        $settings = app(SiteSettings::class);
        $words = preg_split('/[\r\n,]+/u', (string) $settings->get('discussion.sensitive_words', '')) ?: [];
        $text = mb_strtolower(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        foreach (array_filter(array_map('trim', $words)) as $word) {
            if (! str_contains($text, mb_strtolower($word))) {
                continue;
            }
            if ($settings->get('discussion.sensitive_action', 'review') === 'reject') {
                throw ValidationException::withMessages([$field => '内容包含站点限制词，请修改后再提交。']);
            }

            return true;
        }

        return false;
    }
}
