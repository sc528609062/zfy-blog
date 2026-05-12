<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function __construct(protected SettingsService $settings) {}

    public function index(): View
    {
        return view('admin.settings.index', [
            'settings' => $this->settings->all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'site.name'        => 'required|string|max:80',
            'site.tagline'     => 'nullable|string|max:200',
            'site.copyright'   => 'nullable|string|max:200',
            'site.icp'         => 'nullable|string|max:80',
            'site.logo'        => 'nullable|string|max:255',
            'reg.allow'        => 'sometimes',
            'reg.require_email_verify' => 'sometimes',
            'comment.allow_guest' => 'sometimes',
            'comment.audit'    => 'sometimes',
        ]);

        foreach ($data as $key => $value) {
            $this->settings->set($key, $value);
        }

        return back()->with('status', '设置已保存');
    }
}
