<?php

use App\Support\Zfy\AdminRegistry;
use App\Support\Zfy\FilterBus;
use App\Support\Zfy\HookBus;
use App\Support\Zfy\SettingsRegistry;
use App\Support\Zfy\ThemeRegistry;

if (! function_exists('zfy_on')) {
    function zfy_on(string $hook, callable $callback, int $priority = 10): void
    {
        app(HookBus::class)->on($hook, $callback, $priority);
    }
}

if (! function_exists('zfy_emit')) {
    function zfy_emit(string $hook, mixed ...$args): void
    {
        app(HookBus::class)->emit($hook, ...$args);
    }
}

if (! function_exists('zfy_filter')) {
    function zfy_filter(string $hook, callable $callback, int $priority = 10): void
    {
        app(FilterBus::class)->filter($hook, $callback, $priority);
    }
}

if (! function_exists('zfy_apply')) {
    function zfy_apply(string $hook, mixed $value, mixed ...$args): mixed
    {
        return app(FilterBus::class)->apply($hook, $value, ...$args);
    }
}

if (! function_exists('zfy_register_admin_page')) {
    function zfy_register_admin_page(array $definition): void
    {
        app(AdminRegistry::class)->page($definition);
    }
}

if (! function_exists('zfy_register_setting')) {
    function zfy_register_setting(array $definition): void
    {
        app(SettingsRegistry::class)->setting($definition);
    }
}

if (! function_exists('zfy_theme_support')) {
    function zfy_theme_support(string $feature, array $options = []): void
    {
        app(ThemeRegistry::class)->support($feature, $options);
    }
}

if (! function_exists('zfy_register_nav_area')) {
    function zfy_register_nav_area(string $key, array $definition): void
    {
        app(ThemeRegistry::class)->navArea($key, $definition);
    }
}

if (! function_exists('zfy_register_widget_area')) {
    function zfy_register_widget_area(string $key, array $definition): void
    {
        app(ThemeRegistry::class)->widgetArea($key, $definition);
    }
}
