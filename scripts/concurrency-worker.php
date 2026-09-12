<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CommerceOperations;
use App\Services\OrderService;
use App\Services\ProductOrderService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Validation\ValidationException;

require dirname(__DIR__).'/vendor/autoload.php';
if (getenv('DB_DATABASE') !== 'zfy_isolated_test' || getenv('DB_PORT') !== '13307') {
    throw new RuntimeException('Isolated test database required.');
}
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$action = $argv[1];
$id = (int) $argv[2];
$user = User::findOrFail((int) $argv[3]);
try {
    if ($action === 'reserve') {
        app(ProductOrderService::class)->create(Product::findOrFail($id), $user, ['quantity' => 1, 'gateway' => 'balance']);
    } elseif ($action === 'pay') {
        app(OrderService::class)->payWithBalance(Order::findOrFail($id), $user);
    } elseif ($action === 'pay-mixed') {
        app(OrderService::class)->payWithBalance(Order::findOrFail($id), $user, 50);
    } elseif ($action === 'refund') {
        app(CommerceOperations::class)->handleRefund($id, 'refunded');
    } elseif ($action === 'withdraw') {
        app(CommerceOperations::class)->requestWithdrawal($user, '30.00', 'alipay', 'test-account');
    } else {
        throw new RuntimeException('Unknown action.');
    }
    echo "completed\n";
} catch (ValidationException) {
    echo "rejected\n";
    exit(3);
}
