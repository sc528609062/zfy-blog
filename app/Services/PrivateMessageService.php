<?php

namespace App\Services;

use App\Jobs\DeliverSiteNotification;
use App\Models\PrivateMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PrivateMessageService
{
    public function send(User $sender, array $data): PrivateMessage
    {
        abort_if($sender->is_banned || ! app(SiteSettings::class)->get('community.messages_enabled', true), 403);

        return DB::transaction(function () use ($sender, $data) {
            $sender = User::whereKey($sender->id)->lockForUpdate()->firstOrFail();
            if ($existing = PrivateMessage::where('sender_id', $sender->id)->where('request_id', $data['request_id'])->first()) {
                abort_unless($existing->recipient_id === (int) $data['recipient_id'] && $existing->body === $data['body'], 422, '请求标识已用于其他私信。');

                return $existing;
            }
            $recipient = User::whereKey($data['recipient_id'])->where('is_banned', false)->firstOrFail();
            abort_if($recipient->id === $sender->id, 422, '不能给自己发送私信。');
            abort_if(PrivateMessage::where('sender_id', $sender->id)->where('created_at', '>=', today())->count() >= 100, 429);
            $review = app(ContentModeration::class)->check($data['body']);
            zfy_validate('zfy_message_sending', $sender, $recipient, $data['body']);
            $message = PrivateMessage::create([...$data, 'sender_id' => $sender->id, 'status' => $review ? 'pending' : 'sent']);
            if (! $review) {
                $this->notify($message);
            }
            zfy_after_commit('zfy_message_sent', $message);

            return $message;
        });
    }

    public function moderate(PrivateMessage $message, string $status): void
    {
        DB::transaction(function () use ($message, $status) {
            $message = PrivateMessage::whereKey($message->id)->lockForUpdate()->firstOrFail();
            abort_unless($message->status === 'pending' && in_array($status, ['sent', 'rejected'], true), 422, '仅可处理待审核私信。');
            $message->update(['status' => $status]);
            if ($status === 'sent') {
                $this->notify($message);
            }
        });
    }

    private function notify(PrivateMessage $message): void
    {
        DeliverSiteNotification::dispatch('message:'.$message->id, $message->recipient_id, 'message', '收到私信', '请到用户中心查看新私信。');
    }
}
