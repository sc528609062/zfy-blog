<?php

namespace App\Services;

class PageBuilderRenderer
{
    public function render(array $schema, array $data = []): string
    {
        return collect($schema['blocks'] ?? [])->map(function (array $block) use ($data) {
            $type = $block['type'] ?? 'html';
            $title = e($block['title'] ?? '');

            return match ($type) {
                'hero' => '<section class="builder-hero"><h1>'.$title.'</h1><p>'.e($block['subtitle'] ?? '').'</p></section>',
                'content-feed' => '<section class="builder-section"><h2>'.$title.'</h2><div class="builder-grid">'.collect($data['contents'] ?? [])->take(6)->map(fn ($item) => '<article>'.e($item->title).'</article>')->implode('').'</div></section>',
                'vip' => '<section class="builder-vip"><h2>'.$title.'</h2><p>开通 VIP，解锁专属资源与折扣。</p></section>',
                default => '<section class="builder-section"><h2>'.$title.'</h2>'.($block['html'] ?? '').'</section>',
            };
        })->implode("\n");
    }
}
