<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountSecurityController extends Controller
{
    public function notice()
    {
        return redirect('/user/settings')->with('status', '请验证账户邮箱。');
    }

    public function send(Request $request)
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->user()->sendEmailVerificationNotification();
        }

        return $request->expectsJson() ? response()->json(['code' => 0, 'message' => '验证邮件已加入发送队列', 'data' => null]) : back()->with('status', '验证邮件已加入发送队列。');
    }

    public function verify(EmailVerificationRequest $request)
    {
        $request->fulfill();
        zfy_emit('zfy_user_verified', $request->user());

        return redirect('/user/settings')->with('status', '邮箱验证完成。');
    }

    public function revokeSession(Request $request, string $session)
    {
        abort_unless(config('session.driver') === 'database', 422, '当前会话驱动不支持设备管理。');
        abort_if($request->hasSession() && $session === $request->session()->getId(), 422, '当前设备请使用退出登录。');
        DB::table('sessions')->where('user_id', $request->user()->id)->where('id', $session)->delete();

        return $request->expectsJson() ? response()->json(['code' => 0, 'message' => '设备已退出', 'data' => null]) : back()->with('status', '设备已退出。');
    }
}
