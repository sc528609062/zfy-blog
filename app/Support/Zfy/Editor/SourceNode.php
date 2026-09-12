<?php

namespace App\Support\Zfy\Editor;

use App\Services\ContentMarkdownRenderer;
use Tiptap\Core\Node;

class SourceNode extends Node
{
    public static $name = 'zfySource';

    public function addAttributes(): array
    {
        return ['source' => ['default' => '', 'rendered' => false]];
    }

    public function renderHTML($node, $attributes = []): array
    {
        return ['content' => app(ContentMarkdownRenderer::class)->renderForViewer((string) ($node->attrs->source ?? ''), $this->options['content'] ?? null, $this->options['viewer'] ?? null)];
    }
}
