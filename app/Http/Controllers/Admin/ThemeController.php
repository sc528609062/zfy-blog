<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Theme\ThemeManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ThemeController extends Controller
{
    public function __construct(protected ThemeManager $themes) {}

    public function index(): View
    {
        return view('admin.themes.index', [
            'themes' => $this->themes->all(),
            'active' => $this->themes->activeSlug(),
        ]);
    }

    public function activate(string $slug): RedirectResponse
    {
        try {
            $this->themes->activate($slug);
            return back()->with('status', "已切换到主题 {$slug}");
        } catch (\Throwable $e) {
            return back()->withErrors(['theme' => $e->getMessage()]);
        }
    }

    public function preview(string $slug): View
    {
        $theme = $this->themes->find($slug);
        abort_unless($theme, 404);
        return view('admin.themes.preview', ['theme' => $theme]);
    }
}
