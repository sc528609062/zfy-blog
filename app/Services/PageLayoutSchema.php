<?php

namespace App\Services;

use App\Support\Zfy\ExtensionRegistry;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PageLayoutSchema
{
    public function validate(mixed $schema): array
    {
        Validator::make(['schema' => $schema], ['schema' => ['required', 'array'], 'schema.blocks' => ['present', 'array', 'max:100']])->validate();
        $count = 0;
        $visit = function (array $blocks, int $depth) use (&$visit, &$count): void {
            foreach ($blocks as $block) {
                if (++$count > 100 || $depth > 4 || ! is_array($block)) {
                    throw ValidationException::withMessages(['schema' => '布局最多 100 个区块，最多嵌套四层。']);
                }
                Validator::make($block, [
                    'type' => ['required', Rule::in(['container', 'hero', 'content-feed', 'rank', 'vip', 'html', ...array_keys(app(ExtensionRegistry::class)->all('block'))])],
                    'title' => ['nullable', 'string', 'max:180'],
                    'subtitle' => ['nullable', 'string', 'max:500'],
                    'html' => ['nullable', 'string', 'max:50000'],
                    'columns' => ['sometimes', 'integer', 'between:1,6'],
                    'mobile_columns' => ['sometimes', 'integer', 'between:1,2'],
                    'gap' => ['sometimes', 'integer', 'between:0,64'],
                    'visibility' => ['sometimes', 'in:all,desktop,mobile'],
                    'access' => ['sometimes', 'in:public,member,vip,purchased,comment,password'],
                    'children' => ($block['type'] ?? '') === 'container' ? ['present', 'array', 'max:100'] : ['prohibited'],
                ])->validate();
                if ($definition = app(ExtensionRegistry::class)->get('block', $block['type'])) {
                    Validator::make($block, $definition['rules'] ?? [])->validate();
                }
                if ($block['type'] === 'container') {
                    $visit($block['children'], $depth + 1);
                }
            }
        };
        $visit($schema['blocks'], 1);

        return [...$schema, 'version' => 1];
    }
}
