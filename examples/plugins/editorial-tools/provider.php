<?php

namespace Examples\EditorialTools;

use App\Models\Content;
use App\Models\Plugin;
use App\Support\Zfy\PluginLifecycle;
use Illuminate\Support\ServiceProvider;

class Provider extends ServiceProvider implements PluginLifecycle
{
    public function boot(): void
    {
        zfy_register_block('editorial-callout', [
            'label' => 'Editorial callout', 'fields' => [['key' => 'text', 'label' => 'Text', 'type' => 'textarea']],
            'defaults' => ['text' => 'Editorial note'], 'rules' => ['text' => ['required', 'string', 'max:1000']],
            'render' => fn ($values) => '<aside><p>'.e($values['text']).'</p></aside>',
        ]);
        zfy_register_api('editorial-settings', [
            'methods' => ['GET', 'PUT'], 'permission' => 'manage contents', 'ability' => 'profile',
            'rules' => ['PUT' => ['notice' => ['required', 'string', 'max:1000']]],
            'handler' => function ($request, $data) {
                $plugin = Plugin::where('slug', 'editorial-tools')->firstOrFail();
                if ($request->isMethod('PUT')) {
                    $plugin->settings()->updateOrCreate(['key' => 'notice'], ['value' => ['raw' => $data['notice']]]);
                }

                return ['notice' => data_get($plugin->settings()->where('key', 'notice')->first()?->value, 'raw', 'Editorial note')];
            },
        ]);
        zfy_register_shortcode('editorial-note', function (array $attributes, string $body): string {
            return '<aside><strong>'.e($attributes['title'] ?? 'Editorial note').'</strong><p>'.e($body).'</p></aside>';
        });
        zfy_on('zfy_content_published', function (Content $content): void {
            logger()->info('Editorial publication', ['content_id' => $content->id]);
        }, 20, 1);
    }

    public function activate(): void {}

    public function deactivate(): void {}

    public function upgrade(string $fromVersion): void {}

    public function uninstall(bool $deleteData): void {}
}
