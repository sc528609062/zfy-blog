<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Content;
use Illuminate\View\View;

class PageController extends Controller
{
    public function show(Content $content): View
    {
        abort_unless($content->type === Content::TYPE_PAGE, 404);
        return view('page-show', ['content' => $content]);
    }
}
