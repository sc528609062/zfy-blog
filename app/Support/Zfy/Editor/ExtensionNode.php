<?php

namespace App\Support\Zfy\Editor;

use App\Services\ContentHtmlSanitizer;
use App\Support\Zfy\ExtensionRegistry;
use Illuminate\Support\Facades\Validator;
use Tiptap\Core\Node;

class ExtensionNode extends Node
{
    public static $name = 'zfyExtension';

    public function addAttributes(): array
    {
        return ['key' => ['default' => '', 'rendered' => false], 'values' => ['default' => '{}', 'rendered' => false]];
    }

    public function renderHTML($node, $attributes = []): array
    {
        $definition = app(ExtensionRegistry::class)->get('block', (string) ($node->attrs->key ?? ''));
        if (! $definition) {
            return ['content' => '<p>此内容组件暂不可用</p>'];
        }
        $values = json_decode((string) ($node->attrs->values ?? '{}'), true, 32, JSON_THROW_ON_ERROR);
        $values = Validator::make($values, $definition['rules'] ?? [])->validate();

        return ['content' => app(ContentHtmlSanitizer::class)->clean(($definition['render'])($values, ['user' => $this->options['viewer'] ?? null, 'content' => $this->options['content'] ?? null]))];
    }
}
