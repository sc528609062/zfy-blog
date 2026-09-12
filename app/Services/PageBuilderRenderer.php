<?php

namespace App\Services;

use App\Support\Zfy\ExtensionRegistry;
use Illuminate\Support\Facades\Validator;

class PageBuilderRenderer
{
    public function render(array $schema, array $data = []): string
    {
        $schema = app(PageLayoutSchema::class)->validate($schema);
        if (! array_key_exists('user', $data)) {
            $data['user'] = auth()->user();
        }

        return $this->renderBlocks($schema['blocks'], $data);
    }

    private function renderBlocks(array $blocks, array $data): string
    {
        return collect($blocks)->map(function (array $block) use ($data) {
            $access = $block['access'] ?? 'public';
            if ($access !== 'public' && ! app(ContentVisibility::class)->allows($access, $data['content'] ?? null, array_key_exists('user', $data) ? $data['user'] : auth()->user())) {
                return '';
            }
            $type = $block['type'] ?? 'html';
            $title = e($block['title'] ?? '');
            $definition = app(ExtensionRegistry::class)->get('block', $type);
            if ($definition && is_callable($definition['render'] ?? null)) {
                Validator::make($block, $definition['rules'] ?? [])->validate();
                $html = app(ContentHtmlSanitizer::class)->clean(($definition['render'])($block, $data));
            } else {
                $columns = max(1, min(6, (int) ($block['columns'] ?? 3)));
                $mobileColumns = max(1, min(2, (int) ($block['mobile_columns'] ?? 1)));
                $gridStyle = '--builder-columns:'.$columns.';--builder-mobile-columns:'.$mobileColumns.';--builder-gap:'.(int) ($block['gap'] ?? 20).'px';
                $html = match ($type) {
                    'container' => '<section class="builder-section">'.($title !== '' ? '<h2>'.$title.'</h2>' : '').'<div class="builder-grid" style="'.$gridStyle.'">'.$this->renderBlocks($block['children'], $data).'</div></section>',
                    'hero' => '<section class="builder-hero"><h1>'.$title.'</h1><p>'.e($block['subtitle'] ?? '').'</p></section>',
                    'content-feed', 'rank' => '<section class="builder-section"><h2>'.$title.'</h2><div class="builder-grid" style="--builder-columns:'.$columns.';--builder-mobile-columns:'.$mobileColumns.'">'.collect($data[$type === 'rank' ? 'rankings' : 'contents'] ?? [])->take(6)->map(fn ($item) => '<article><a href="'.e(route('contents.show', $item->slug)).'">'.e($item->title).'</a></article>')->implode('').'</div></section>',
                    'vip' => '<section class="builder-vip"><h2>'.$title.'</h2><a href="'.e(route('vip')).'">会员中心</a></section>',
                    'html' => '<section class="builder-section"><h2>'.$title.'</h2>'.app(ContentMarkdownRenderer::class)->render((string) ($block['html'] ?? ''), true).'</section>',
                    default => '',
                };
            }
            $visibility = in_array($block['visibility'] ?? '', ['desktop', 'mobile'], true) ? ' builder-only-'.$block['visibility'] : '';

            return '<div class="builder-module'.$visibility.'">'.$html.'</div>';
        })->implode("\n");
    }
}
