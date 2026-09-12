<?php

namespace Examples\NativeJournal;

use App\Support\Zfy\ThemeLifecycle;
use Illuminate\Support\ServiceProvider;

class Provider extends ServiceProvider implements ThemeLifecycle
{
    public function boot(): void
    {
        zfy_theme_support('native-journal', ['version' => 1]);
    }

    public function activate(): void {}

    public function deactivate(): void {}

    public function upgrade(string $fromVersion): void {}

    public function uninstall(bool $deleteData): void {}
}
