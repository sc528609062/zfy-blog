<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Link;
use App\Models\LinkCheck;
use App\Services\LinkChecker;
use Illuminate\Http\Request;

class AdminLinkController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()?->can('manage links'), 403);

        return response()->json(['links' => Link::orderBy('name')->get(['id', 'name', 'url']), 'data' => LinkCheck::with('link:id,name,url')->latest()->paginate(20)]);
    }

    public function check(Request $request, Link $link, LinkChecker $checker)
    {
        abort_unless($request->user()?->can('manage links'), 403);

        return response()->json(['data' => $checker->check($link), 'message' => '检测完成']);
    }

    public function redirect(Link $link)
    {
        abort_unless($link->status === 'active' && filter_var($link->url, FILTER_VALIDATE_URL) && in_array(parse_url($link->url, PHP_URL_SCHEME), ['http', 'https'], true), 404);

        return redirect()->away($link->url)->header('Referrer-Policy', 'no-referrer');
    }
}
