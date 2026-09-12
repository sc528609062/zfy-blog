<?php

namespace App\Services;

use App\Models\Content;
use App\Models\User;
use App\Support\Zfy\Editor\ExtensionNode;
use App\Support\Zfy\Editor\Indent;
use App\Support\Zfy\Editor\SourceNode;
use App\Support\Zfy\ExtensionRegistry;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tiptap\Editor;
use Tiptap\Extensions\Color;
use Tiptap\Extensions\StarterKit;
use Tiptap\Extensions\TextAlign;
use Tiptap\Marks\Link;
use Tiptap\Marks\TextStyle;
use Tiptap\Marks\Underline;
use Tiptap\Nodes\Image;
use Tiptap\Nodes\Table;
use Tiptap\Nodes\TableCell;
use Tiptap\Nodes\TableHeader;
use Tiptap\Nodes\TableRow;
use Tiptap\Nodes\TaskItem;
use Tiptap\Nodes\TaskList;

class ContentDocumentService
{
    public function editor(?Content $content = null, ?User $viewer = null): Editor
    {
        return new Editor(['extensions' => [new StarterKit, new Link, new Underline, new TextStyle, new Color, new TextAlign(['types' => ['heading', 'paragraph']]), new Indent, new Image, new Table, new TableRow, new TableCell, new TableHeader, new TaskList, new TaskItem, new SourceNode(['content' => $content, 'viewer' => $viewer]), new ExtensionNode(['content' => $content, 'viewer' => $viewer])]]);
    }

    public function normalize(array $block, string $markdown): array
    {
        if (strlen($markdown) > 200000 || (isset($block['original_markdown']) && (! is_string($block['original_markdown']) || strlen($block['original_markdown']) > 200000))) {
            $this->fail('Original source is too large or invalid.');
        }
        $mode = $block['mode'] ?? 'markdown';
        if (! in_array($mode, ['markdown', 'blocks', 'richtext'], true)) {
            $this->fail('Unknown editor mode.');
        }
        if ($mode === 'markdown') {
            $document = $this->fromMarkdown($markdown);
        } else {
            $document = $block['document'] ?? [];
            $this->validate($document);
        }

        return array_replace($block, ['version' => 3, 'editor' => 'zfy-document', 'mode' => $mode, 'document' => $document, 'original_markdown' => $block['original_markdown'] ?? $markdown]);
    }

    public function fromMarkdown(string $markdown): array
    {
        if (preg_match('/\A```zfy-document\r?\n([\s\S]*)\r?\n```\s*\z/', $markdown, $match)) {
            try {
                $document = json_decode($match[1], true, 64, JSON_THROW_ON_ERROR);
            } catch (\Throwable) {
                $this->fail('Invalid preserved document.');
            }
            $this->validate($document);

            return $document;
        }
        if ($markdown !== '' && ! preg_match('/[{}<>]|\$\$|^\|/m', $markdown)) {
            try {
                $html = Str::markdown($markdown, ['html_input' => 'escape', 'allow_unsafe_links' => false]);
                $document = json_decode($this->editor()->setContent($html)->getJSON(), true, 64, JSON_THROW_ON_ERROR);
                $this->validate($document);

                return $document;
            } catch (\Throwable) { /* Keep unsupported source intact for later editing. */
            }
        }

        // Preserve custom syntax that cannot be represented by the installed editor extensions.
        return ['type' => 'doc', 'content' => $markdown === '' ? [['type' => 'paragraph']] : [['type' => 'zfySource', 'attrs' => ['source' => $markdown]]]];
    }

    public function render(array $document, ?Content $content = null, ?User $viewer = null): string
    {
        $this->validate($document);
        $document = $this->visibleDocument($document, $content, $viewer);

        return app(ContentHtmlSanitizer::class)->clean($this->editor($content, $viewer)->setContent($document)->getHTML());
    }

    private function visibleDocument(array $node, ?Content $content, ?User $viewer): array
    {
        $children = [];
        foreach ($node['content'] ?? [] as $child) {
            if ($child['type'] === 'zfyRestricted') {
                $allowed = app(ContentVisibility::class)->allows($child['attrs']['rule'], $content, $viewer);
                if ($allowed) {
                    $children = [...$children, ...($this->visibleDocument($child, $content, $viewer)['content'] ?? [])];
                } else {
                    $children[] = ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $child['attrs']['label'] ?? '此内容需要相应访问权限']]];
                }
            } else {
                $children[] = $this->visibleDocument($child, $content, $viewer);
            }
        }
        if (isset($node['content'])) {
            $node['content'] = $children;
        }

        return $node;
    }

    public function validate(mixed $document): void
    {
        if (! is_array($document) || ($document['type'] ?? '') !== 'doc' || ! is_array($document['content'] ?? null)) {
            $this->fail('Invalid document root.');
        }
        if (strlen(json_encode($document, JSON_THROW_ON_ERROR)) > 1000000) {
            $this->fail('Document is too large.');
        }
        $allowed = ['doc', 'paragraph', 'text', 'heading', 'blockquote', 'bulletList', 'orderedList', 'listItem', 'codeBlock', 'hardBreak', 'horizontalRule', 'image', 'table', 'tableRow', 'tableCell', 'tableHeader', 'taskList', 'taskItem', 'zfySource', 'zfyRestricted', 'zfyExtension'];
        $count = 0;
        $visit = function ($node, $depth) use (&$visit, &$count, $allowed) {
            if (! is_array($node) || ++$count > 10000 || $depth > 40 || ! in_array($node['type'] ?? '', $allowed, true)) {
                $this->fail('Unsupported or excessively nested block.');
            }
            foreach (['content', 'marks', 'attrs'] as $key) {
                if (isset($node[$key]) && ! is_array($node[$key])) {
                    $this->fail('Invalid block structure.');
                }
            }
            if (isset($node['text']) && ! is_string($node['text'])) {
                $this->fail('Invalid text.');
            }
            if (isset($node['attrs']['indent']) && (! is_int($node['attrs']['indent']) || $node['attrs']['indent'] < 0 || $node['attrs']['indent'] > 8)) {
                $this->fail('Invalid indentation.');
            }
            foreach ($node['marks'] ?? [] as $mark) {
                if (! is_array($mark) || ! in_array($mark['type'] ?? '', ['bold', 'italic', 'strike', 'code', 'link', 'underline', 'textStyle'], true)) {
                    $this->fail('Unsupported text mark.');
                }
                if ($mark['type'] === 'textStyle' && isset($mark['attrs']['color']) && (! is_string($mark['attrs']['color']) || ! preg_match('/^#[a-fA-F0-9]{3,8}$/', $mark['attrs']['color']))) {
                    $this->fail('Invalid text color.');
                }
            }
            if ($node['type'] === 'zfyRestricted' && (! in_array($node['attrs']['rule'] ?? '', ['member', 'vip', 'purchased', 'comment', 'password'], true) || (isset($node['attrs']['label']) && (! is_string($node['attrs']['label']) || strlen($node['attrs']['label']) > 500)))) {
                $this->fail('Invalid visibility block.');
            }
            if (($node['type'] ?? '') === 'zfySource' && (! is_string($node['attrs']['source'] ?? null) || strlen($node['attrs']['source']) > 200000)) {
                $this->fail('Invalid source block.');
            }
            if ($node['type'] === 'zfyExtension') {
                if (! is_string($node['attrs']['key'] ?? null) || ! preg_match('/^[a-z][a-z0-9_-]{0,79}$/', $node['attrs']['key']) || ! is_string($node['attrs']['values'] ?? null) || strlen($node['attrs']['values']) > 20000) {
                    $this->fail('Invalid extension block.');
                }
                try {
                    $values = json_decode($node['attrs']['values'], true, 32, JSON_THROW_ON_ERROR);
                } catch (\Throwable) {
                    $this->fail('Invalid extension values.');
                }
                if (! is_array($values)) {
                    $this->fail('Invalid extension values.');
                }
                if ($definition = app(ExtensionRegistry::class)->get('block', $node['attrs']['key'])) {
                    Validator::make($values, $definition['rules'] ?? [])->validate();
                }
            }
            foreach ($node['content'] ?? [] as $child) {
                $visit($child, $depth + 1);
            }
        };
        $visit($document, 0);
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['block_json' => $message]);
    }
}
