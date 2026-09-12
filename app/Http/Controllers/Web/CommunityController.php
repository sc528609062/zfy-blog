<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\User;
use App\Models\UserRequest;
use App\Services\SiteSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommunityController extends Controller
{
    public function reaction(Request $request, Content $content)
    {
        abort_unless(Content::published()->whereKey($content->id)->exists(), 404);
        $data = $request->validate(['type' => ['required', 'in:like,favorite'], 'active' => ['required', 'boolean']]);
        DB::transaction(function () use ($request, $content, $data) {
            Content::whereKey($content->id)->lockForUpdate()->firstOrFail();
            $key = ['content_id' => $content->id, 'user_id' => $request->user()->id, 'type' => $data['type']];
            if ($data['active']) {
                DB::table('content_reactions')->insertOrIgnore([...$key, 'created_at' => now(), 'updated_at' => now()]);
            } else {
                DB::table('content_reactions')->where($key)->delete();
            }
            if ($data['type'] === 'like') {
                Content::whereKey($content->id)->update(['like_count' => DB::table('content_reactions')->where('content_id', $content->id)->where('type', 'like')->count()]);
            }
        });

        return $request->expectsJson() ? response()->json(['code' => 0, 'message' => 'ok', 'data' => ['active' => $data['active']]]) : back();
    }

    public function follow(Request $request, User $author)
    {
        abort_unless($author->is_author && $author->author_status === 'approved' && ! $author->is_banned && $author->id !== $request->user()->id, 422);
        $data = $request->validate(['active' => ['required', 'boolean']]);
        $key = ['author_id' => $author->id, 'user_id' => $request->user()->id];
        if ($data['active']) {
            DB::table('user_follows')->insertOrIgnore([...$key, 'created_at' => now(), 'updated_at' => now()]);
        } else {
            DB::table('user_follows')->where($key)->delete();
        }

        return $request->expectsJson() ? response()->json(['code' => 0, 'message' => 'ok', 'data' => ['active' => (bool) $data['active']]]) : back();
    }

    public function request(Request $request)
    {
        $data = $request->validate(['type' => ['required', 'in:verification,author,report,appeal'], 'body' => ['required', 'string', 'max:4000'], 'content_id' => ['nullable', 'integer', 'exists:contents,id']]);
        if ($data['type'] === 'report') {
            abort_unless(Content::published()->whereKey($data['content_id'] ?? 0)->exists(), 422);
        } else {
            $data['content_id'] = null;
        }
        DB::transaction(function () use ($request, $data) {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            if (($data['type'] === 'author' && $user->is_author && $user->author_status === 'approved') || ($data['type'] === 'appeal' && ! $user->is_banned)) {
                throw ValidationException::withMessages(['type' => '当前账号无需提交该申请。']);
            }
            if (UserRequest::where('user_id', $user->id)->where('type', $data['type'])->where('content_id', $data['content_id'] ?? null)->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages(['type' => '已有相同申请等待处理。']);
            }
            UserRequest::create([...$data, 'user_id' => $user->id, 'status' => 'pending']);
            if ($data['type'] === 'author') {
                $user->update(['author_status' => 'pending']);
            }
        });

        return $request->expectsJson() ? response()->json(['code' => 0, 'message' => '申请已提交', 'data' => null]) : back()->with('status', '申请已提交。');
    }

    public function readNotification(Request $request, int $notification)
    {
        abort_unless(DB::table('notifications')->where('id', $notification)->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $request->user()->id))->exists(), 404);
        DB::table('notification_reads')->insertOrIgnore(['notification_id' => $notification, 'user_id' => $request->user()->id, 'read_at' => now(), 'created_at' => now(), 'updated_at' => now()]);

        return $request->expectsJson() ? response()->json(['code' => 0, 'message' => 'ok', 'data' => null]) : back();
    }

    public function checkin(Request $request, SiteSettings $settings)
    {
        abort_unless($settings->get('community.checkin_enabled', true), 403);
        $result = DB::transaction(function () use ($request, $settings) {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $existing = DB::table('daily_checkins')->where('user_id', $user->id)->where('date', today()->toDateString())->first();
            if ($existing) {
                return ['points' => $existing->points, 'already_checked_in' => true];
            }
            $points = (int) $settings->get('community.checkin_points', 5);
            $account = $user->pointsAccount()->lockForUpdate()->firstOrCreate([], ['points' => 0]);
            $account->increment('points', $points);
            DB::table('daily_checkins')->insert(['user_id' => $user->id, 'date' => today()->toDateString(), 'points' => $points, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('points_transactions')->insert(['points_account_id' => $account->id, 'type' => 'checkin', 'points' => $points, 'balance_after' => $account->fresh()->points, 'remark' => '每日签到', 'created_at' => now(), 'updated_at' => now()]);

            return ['points' => $points, 'already_checked_in' => false];
        });

        return $request->expectsJson() ? response()->json(['code' => 0, 'message' => 'ok', 'data' => $result]) : back()->with('status', $result['already_checked_in'] ? '今日已签到。' : '签到成功，获得 '.$result['points'].' 积分。');
    }
}
