<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function overview(Request $request): View
    {
        $user = $request->user();
        $user->loadMissing(['wallet', 'pointsAccount', 'activeVip.level']);
        return view('frontend.user.overview', [
            'user' => $user,
        ]);
    }
}
