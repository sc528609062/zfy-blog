<?php

namespace App\Services;

use App\Support\Zfy\HookBus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ExtensionOutbox
{
    public function consume(string $consumer, string $key, callable $operation): bool
    {
        if (! preg_match('/^[a-z][a-z0-9_.-]{0,79}$/', $consumer) || $key === '' || strlen($key) > 160) {
            throw new \InvalidArgumentException('Invalid consumer or event key.');
        }

        return DB::transaction(function () use ($consumer, $key, $operation) {
            $inserted = DB::table('extension_event_receipts')->insertOrIgnore(['consumer' => $consumer, 'event_key' => $key, 'consumed_at' => now()]);
            if (! $inserted) {
                return false;
            }
            $operation();

            return true;
        });
    }

    public function record(string $key, string $hook, array $payload): void
    {
        DB::table('extension_events')->insertOrIgnore(['event_key' => $key, 'hook' => $hook, 'payload' => json_encode($payload, JSON_THROW_ON_ERROR), 'created_at' => now(), 'updated_at' => now()]);
    }

    public function deliver(int $limit = 100): int
    {
        $delivered = 0;
        foreach (DB::table('extension_events')->whereNull('delivered_at')->orderBy('id')->limit($limit)->get() as $event) {
            Cache::store('file')->lock('zfy-event-'.$event->id, 120)->get(function () use ($event, &$delivered) {
                if (DB::table('extension_events')->where('id', $event->id)->whereNotNull('delivered_at')->exists()) {
                    return;
                }
                DB::table('extension_events')->where('id', $event->id)->increment('attempts');
                try {
                    app(HookBus::class)->emitStrict($event->hook, json_decode($event->payload, true), $event->event_key);
                    DB::table('extension_events')->where('id', $event->id)->update(['delivered_at' => now(), 'updated_at' => now()]);
                    $delivered++;
                } catch (\Throwable $exception) {
                    report($exception);
                }
            });
        }

        return $delivered;
    }
}
