<?php

namespace App\Support\Zfy;

class ThemeRegistry
{
    /**
     * @var array<string, mixed>
     */
    private array $supports = [];

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $navAreas = [];

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $widgetAreas = [];

    public function support(string $feature, array $options = []): void
    {
        $this->supports[$feature] = $options === [] ? true : $options;
    }

    public function navArea(string $key, array $definition): void
    {
        $this->navAreas[$key] = array_replace([
            'key' => $key,
            'label' => $key,
            'description' => '',
        ], $definition);
    }

    public function widgetArea(string $key, array $definition): void
    {
        $this->widgetAreas[$key] = array_replace([
            'key' => $key,
            'label' => $key,
            'description' => '',
        ], $definition);
    }

    public function payload(): array
    {
        return [
            'supports' => $this->supports,
            'nav_areas' => array_values($this->navAreas),
            'widget_areas' => array_values($this->widgetAreas),
        ];
    }
}
