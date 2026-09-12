<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VipLevel;
use App\Services\OrderService;
use App\Services\Payment\PaymentManager;
use App\Services\PluginInstaller;
use Database\Seeders\CoreInstallSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PharData;
use Tests\TestCase;

class ExtensionAndGatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_epay_checks_signature_amount_and_repeated_notify(): void
    {
        $this->seed(CoreInstallSeeder::class);
        config(['payments.epay' => ['url' => 'https://payments.example.test', 'pid' => '123', 'key' => 'test-secret', 'type' => 'alipay']]);
        $user = User::factory()->create();
        $order = app(OrderService::class)->createForVip(VipLevel::first(), $user, 'monthly', 'epay');
        $manager = app(PaymentManager::class);
        $payment = $manager->createPayment($order, 'epay');
        $this->assertStringStartsWith('https://payments.example.test/submit.php?', $payment->response_payload['checkout_url']);
        $payload = ['pid' => '123', 'out_trade_no' => $order->order_no, 'trade_no' => 'gateway-001', 'money' => number_format($order->total_amount, 2, '.', ''), 'trade_status' => 'TRADE_SUCCESS'];
        ksort($payload);
        $payload['sign'] = md5(implode('&', array_map(fn ($key) => $key.'='.$payload[$key], array_keys($payload))).'test-secret');
        $this->assertNull($manager->completeByNotify('epay', [...$payload, 'money' => '0.01']));
        $this->assertNotNull($manager->completeByNotify('epay', $payload));
        $expiry = $user->vip()->first()->expires_at;
        $this->assertNotNull($manager->completeByNotify('epay', $payload));
        $this->assertTrue($expiry->equalTo($user->vip()->first()->expires_at));
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_local_plugin_package_install_preserves_existing_files(): void
    {
        $slug = 'test-plugin-'.Str::lower(Str::random(10));
        $path = sys_get_temp_dir().'/'.$slug.'.zip';
        try {
            $archive = new PharData($path);
            $archive->addFromString($slug.'/plugin.json', json_encode(['name' => 'Test Plugin', 'slug' => $slug, 'version' => '1.0.0', 'compatible' => '^1.0', 'permissions' => [], 'events' => []]));
            unset($archive);
            $upload = new UploadedFile($path, 'plugin.zip', 'application/zip', null, true);
            $plugin = app(PluginInstaller::class)->install($upload);
            $this->assertFalse((bool) $plugin->enabled);
            $this->assertFileExists(base_path('plugins/'.$slug.'/plugin.json'));
            $this->expectException(ValidationException::class);
            app(PluginInstaller::class)->install($upload);
        } finally {
            File::deleteDirectory(base_path('plugins/'.$slug));
            File::delete($path);
        }
    }
}
