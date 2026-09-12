<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PrivateMessage;
use App\Services\PrivateMessageService;
use Illuminate\Http\Request;

class PrivateMessageController extends Controller
{
    public function index(Request $request)
    {
        $messages = PrivateMessage::with(['sender:id,name,username', 'recipient:id,name,username'])->where(fn ($query) => $query->where('sender_id', $request->user()->id)->orWhere(fn ($query) => $query->where('recipient_id', $request->user()->id)->where('status', 'sent')))->latest('id')->paginate(20);

        return response()->json(['code' => 0, 'message' => 'ok', 'data' => $messages]);
    }

    public function store(Request $request, PrivateMessageService $messages)
    {
        $data = $request->validate(['recipient_id' => ['required', 'integer', 'exists:users,id'], 'body' => ['required', 'string', 'max:4000'], 'request_id' => ['required', 'uuid']]);
        $message = $messages->send($request->user(), $data);

        return $request->expectsJson() ? response()->json(['code' => 0, 'message' => 'ok', 'data' => $message]) : back()->with('status', $message->status === 'pending' ? '私信等待审核。' : '私信已发送。');
    }

    public function read(Request $request, PrivateMessage $message)
    {
        abort_unless($message->recipient_id === $request->user()->id && $message->status === 'sent', 404);
        $message->update(['read_at' => $message->read_at ?? now()]);

        return $request->expectsJson() ? response()->json(['code' => 0, 'message' => 'ok', 'data' => null]) : back();
    }
}
