<?php

namespace App\Support\Zfy;

interface PluginLifecycle
{
    public function activate(): void;

    public function deactivate(): void;

    public function upgrade(string $fromVersion): void;

    public function uninstall(bool $deleteData): void;
}
