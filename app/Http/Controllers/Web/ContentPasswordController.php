<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Services\ContentPasswordAccess;
use Illuminate\Http\Request;

class ContentPasswordController extends Controller
{
    public function __invoke(Request $request, Content $content, ContentPasswordAccess $access)
    {
        abort_unless($content->shouldBeSearchable(), 404);
        $access->grant($content, $request);

        return $request->expectsJson() ? response()->json(['code' => 0, 'message' => '内容已解锁', 'data' => null]) : back()->with('status', '内容已解锁');
    }
}
