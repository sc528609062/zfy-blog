<?php

namespace App\Support\Zfy\Editor;

use Tiptap\Core\Extension;

class Indent extends Extension
{
    public static $name = 'zfyIndent';

    public function addGlobalAttributes(): array
    {
        return [['types' => ['paragraph', 'heading'], 'attributes' => ['indent' => [
            'default' => 0,
            'parseHTML' => fn ($node) => max(0, min(8, (int) $node->getAttribute('data-indent'))),
            'renderHTML' => fn ($attributes) => ($attributes->indent ?? 0) > 0 ? ['style' => 'margin-left: '.((int) $attributes->indent * 2).'em'] : null,
        ]]]];
    }
}
