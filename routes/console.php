<?php

use App\Models\Content;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\CommerceOperations;
use App\Services\DatabaseBackup;
use App\Services\ExtensionOutbox;
use App\Services\ExternalRefundService;
use App\Services\OrderCancellation;
use App\Services\Payment\PaymentManager;
use App\Services\Payment\RefundableGateway;
use App\Services\ThemeManager;
use App\Services\Updates\UpdateChecker;
use App\Support\Zfy\UpdateTaskBarrier;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Schedule::command('zfy:update')->everyMinute()->withoutOverlapping(60);
Schedule::command('zfy:extensions-update')->everyMinute()->withoutOverlapping(60);
Schedule::command('zfy:orders-expire')->everyMinute()->withoutOverlapping();
Schedule::command('zfy:events-deliver')->everyMinute()->withoutOverlapping();
Schedule::command('zfy:contents-publish')->everyMinute()->withoutOverlapping();
Schedule::command('zfy:payments-reconcile')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('zfy:earnings-settle')->hourly()->withoutOverlapping();
Schedule::command('zfy:updates-check')->daily()->withoutOverlapping();
$guarded = static fn (Closure $callback) => function () use ($callback) {
    return app(UpdateTaskBarrier::class)->run(fn () => $callback->call($this));
};
Artisan::command('zfy:payments-reconcile', $guarded(function () {
    if (is_file(storage_path('app/private/updates/writes-paused'))) {
        return 0;
    }
    foreach (Payment::where('status', 'pending')->where('created_at', '>=', now()->subDays(7))->oldest()->limit(100)->get() as $payment) {
        try {
            app(PaymentManager::class)->queryPayment($payment);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
    foreach (Refund::whereIn('status', ['pending', 'processing'])->where(fn ($query) => $query->where('status', 'processing')->orWhere('metadata->late_payment', true))->oldest()->limit(100)->get() as $refund) {
        try {
            $order = Order::find($refund->order_id);
            if ($order && app(PaymentManager::class)->gateway($order->pay_channel) instanceof RefundableGateway) {
                app(ExternalRefundService::class)->process($refund->id);
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    return 0;
}));
Artisan::command('zfy:contents-publish', $guarded(function () {
    if (is_file(storage_path('app/private/updates/writes-paused'))) {
        return 0;
    }
    Content::where('status', 'scheduled')->where('published_at', '<=', now())->orderBy('id')->chunkById(100, function ($contents) {
        foreach ($contents as $content) {
            try {
                DB::transaction(function () use ($content) {
                    $content = Content::whereKey($content->id)->lockForUpdate()->first();
                    if (! $content || $content->status !== 'scheduled' || $content->published_at->isFuture()) {
                        return;
                    }
                    zfy_validate('zfy_content_saving', ['status' => 'published'], $content, null);
                    $content->update(['status' => 'published']);
                    zfy_after_commit('zfy_content_saved', $content, ['status' => 'published'], null);
                    zfy_after_commit('zfy_content_published', $content, ['status' => 'published'], null);
                });
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    });

    return 0;
}));
Artisan::command('zfy:events-deliver', $guarded(function () {
    $this->info((string) app(ExtensionOutbox::class)->deliver());
}));
Artisan::command('zfy:earnings-settle', $guarded(function () {
    $this->info((string) app(CommerceOperations::class)->settleDueEarnings());
}));
Artisan::command('zfy:updates-check', $guarded(function () {
    $this->info(json_encode(app(UpdateChecker::class)->check(), JSON_UNESCAPED_UNICODE));
}));

Artisan::command('zfy:orders-expire', $guarded(function () {
    DB::table('download_grants')->where('expires_at', '<', now()->subDay())->delete();
    Order::where('status', 'pending')->where('expires_at', '<', now())->orderBy('id')->chunkById(100, function ($orders) {
        foreach ($orders as $order) {
            try {
                app(OrderCancellation::class)->cancel($order, 'closed');
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    });
}));

Artisan::command('zfy:update-health', function () {
    DB::select('SELECT 1');
    $theme = app(ThemeManager::class)->active();
    if (! view()->exists($theme['view']) || ! is_file(public_path('build/manifest.json'))) {
        return 1;
    }
    $this->info('Application health checks passed.');

    return 0;
});

Artisan::command('zfy:backup', function (DatabaseBackup $backup) {
    $this->info('Backup created: '.$backup->create());
})->purpose('Back up the configured database');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
