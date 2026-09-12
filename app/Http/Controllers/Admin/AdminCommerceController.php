<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Refund;
use App\Services\CommerceOperations;
use App\Services\ExternalRefundService;
use App\Services\OrderCancellation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminCommerceController extends Controller
{
    public function index(Request $request, string $section)
    {
        abort_unless($request->user()?->can('manage commerce'), 403);
        $rows = match ($section) {
            'orders' => Order::with(['items', 'user:id,name'])->latest()->paginate(20),
            'refunds' => Refund::latest()->paginate(20),
            'commissions' => DB::table('author_earnings')->latest()->paginate(20),
            'withdrawals' => DB::table('author_withdrawals')->latest()->paginate(20),
            'points-exchanges' => DB::table('points_exchange_orders')->latest()->paginate(20),
            default => abort(404),
        };

        return response()->json(['data' => $rows]);
    }

    public function action(Request $request, string $section, int $id, CommerceOperations $operations)
    {
        abort_unless($request->user()?->can('manage commerce'), 403);
        $data = $request->validate(['status' => ['required', 'string'], 'reference' => ['nullable', 'string', 'max:500']]);
        if ($section === 'withdrawals') {
            $request->validate(['status' => ['in:paid,rejected']]);
            $operations->handleWithdrawal($id, $data['status'], $data['reference'] ?? null);
        } elseif ($section === 'refunds') {
            $request->validate(['status' => ['in:refunded,rejected,returned']]);
            $refund = Refund::findOrFail($id);
            $order = Order::findOrFail($refund->order_id);
            if ($data['status'] === 'returned') {
                $operations->receiveReturn($id, $data['reference'] ?? '');
            } elseif ($data['status'] === 'refunded' && ! in_array($order->pay_channel, ['balance', 'points'], true)) {
                app(ExternalRefundService::class)->process($id, $data['reference'] ?? null);
            } else {
                $operations->handleRefund($id, $data['status']);
            }
        } elseif ($section === 'orders') {
            $request->validate(['status' => ['in:cancelled']]);
            app(OrderCancellation::class)->cancel(Order::findOrFail($id));
        } elseif ($section === 'points-exchanges') {
            $request->validate(['status' => ['in:fulfilled']]);
            DB::table('points_exchange_orders')->where('id', $id)->where('status', 'pending')->update(['status' => 'fulfilled', 'updated_at' => now()]);
        } else {
            abort(404);
        }

        return response()->json(['message' => '已处理']);
    }
}
