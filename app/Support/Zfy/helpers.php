<?php

use App\Services\ExtensionOutbox;
use App\Services\Payment\PaymentGateway;
use App\Services\ThemePackageLoader;
use App\Support\Zfy\AdminRegistry;
use App\Support\Zfy\ExtensionRegistry;
use App\Support\Zfy\FilterBus;
use App\Support\Zfy\HookBus;
use App\Support\Zfy\SettingsRegistry;
use App\Support\Zfy\ThemeRegistry;
use Illuminate\Support\Facades\DB;

function zfy_consume_event(string $consumer, string $eventKey, callable $operation): bool
{
    return app(ExtensionOutbox::class)->consume($consumer, $eventKey, $operation);
}

function zfy_register_api(string $key, array $definition): void
{
    $methods = $definition['methods'] ?? ['GET'];
    if (! is_array($methods) || $methods === [] || array_diff($methods, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE']) || ! is_callable($definition['handler'] ?? null)) {
        throw new InvalidArgumentException('API methods and handler are required.');
    }
    $public = ($definition['public'] ?? false) === true;
    if ($public && array_diff($methods, ['GET'])) {
        throw new InvalidArgumentException('Public extension APIs must be read-only.');
    }
    if (! $public && (! is_string($definition['permission'] ?? null) || ! filled($definition['permission']))) {
        throw new InvalidArgumentException('Private extension APIs require a permission.');
    }
    $ability = $definition['ability'] ?? 'read';
    if (! in_array($ability, ['read', 'orders', 'download', 'comment', 'profile'], true)) {
        throw new InvalidArgumentException('Invalid API token ability.');
    }
    app(ExtensionRegistry::class)->register('api', $key, [...$definition, 'methods' => $methods, 'public' => $public, 'ability' => $ability]);
}

function zfy_register_content_type(string $key, array $definition): void
{
    app(ExtensionRegistry::class)->register('content_type', $key, $definition);
}

function zfy_register_block(string $key, array $definition): void
{
    if (! is_callable($definition['render'] ?? null)) {
        throw new InvalidArgumentException('Block renderer is required.');
    }
    app(ExtensionRegistry::class)->register('block', $key, $definition);
}

function zfy_register_widget(string $key, array $definition): void
{
    app(ExtensionRegistry::class)->register('widget', $key, $definition);
}

function zfy_register_shortcode(string $key, callable $renderer): void
{
    app(ExtensionRegistry::class)->register('shortcode', $key, ['render' => $renderer]);
}

function zfy_register_payment(string $key, string $driver): void
{
    if (! is_subclass_of($driver, PaymentGateway::class)) {
        throw new InvalidArgumentException('Invalid payment driver.');
    }
    app(ExtensionRegistry::class)->register('payment', $key, ['driver' => $driver]);
}

function zfy_asset(string $type, string $slug, string $path): string
{
    return route('extensions.asset', compact('type', 'slug', 'path'));
}

function zfy_template(string $slug, array $candidates): ?string
{
    return app(ThemePackageLoader::class)->template($slug, $candidates);
}

function zfy_once(string $hook, callable $callback, int $priority = 10, ?int $acceptedArgs = null): string
{
    return app(HookBus::class)->once($hook, $callback, $priority, $acceptedArgs);
}

function zfy_off(string $hook, callable|string $callback, ?int $priority = null): bool
{
    return app(HookBus::class)->remove($hook, $callback, $priority);
}

function zfy_remove_filter(string $hook, callable|string $callback, ?int $priority = null): bool
{
    return app(FilterBus::class)->remove($hook, $callback, $priority);
}

function zfy_filter_once(string $hook, callable $callback, int $priority = 10, ?int $acceptedArgs = null): string
{
    return app(FilterBus::class)->once($hook, $callback, $priority, $acceptedArgs);
}

function zfy_current_hook(): ?string
{
    return app(HookBus::class)->current();
}

function zfy_current_filter(): ?string
{
    return app(FilterBus::class)->current();
}

function zfy_did(string $hook): int
{
    return app(HookBus::class)->did($hook);
}

function zfy_validate(string $hook, mixed ...$args): void
{
    app(HookBus::class)->emitStrict($hook, ...$args);
}

function zfy_apply_strict(string $hook, mixed $value, mixed ...$args): mixed
{
    return app(FilterBus::class)->applyStrict($hook, $value, ...$args);
}

function zfy_after_commit(string $hook, mixed ...$args): void
{
    DB::afterCommit(fn () => zfy_emit($hook, ...$args));
}

if (! function_exists('zfy_on')) {
    function zfy_on(string $hook, callable $callback, int $priority = 10, ?int $acceptedArgs = null): string
    {
        return app(HookBus::class)->on($hook, $callback, $priority, $acceptedArgs);
    }
}

if (! function_exists('zfy_emit')) {
    function zfy_emit(string $hook, mixed ...$args): void
    {
        app(HookBus::class)->emit($hook, ...$args);
    }
}

if (! function_exists('zfy_filter')) {
    function zfy_filter(string $hook, callable $callback, int $priority = 10, ?int $acceptedArgs = null): string
    {
        return app(FilterBus::class)->filter($hook, $callback, $priority, $acceptedArgs);
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
