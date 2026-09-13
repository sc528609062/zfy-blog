<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Refund;
use App\Services\CommerceOperations;
use App\Services\ExternalRefundService;
use App\Services\OrderCancellation;
use App\Support\AdminPagination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminCommerceController extends Controller
{
    public function index(Request $request, string $section)
    {
        abort_unless($request->user()?->can('manage commerce'), 403);
        $request->validate(['q' => ['nullable', 'string', 'max:120'], 'status' => ['nullable', 'string', 'max:40']]);
        $query = match ($section) {
            'orders' => Order::with(['items', 'user:id,name']),
            'refunds' => Refund::query(),
            'commissions' => DB::table('author_earnings'),
            'withdrawals' => DB::table('author_withdrawals'),
            'points-exchanges' => DB::table('points_exchange_orders'),
            default => abort(404),
        };
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('q')) {
            $term = $request->string('q')->trim()->toString();
            $query->where(function ($query) use ($section, $term) {
                $query->where('id', ctype_digit($term) ? $term : 0);
                if ($section === 'orders') {
                    $query->orWhere('order_no', 'like', '%'.$term.'%')
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', '%'.$term.'%'));
                }
                if (ctype_digit($term)) {
                    $query->orWhere(in_array($section, ['commissions', 'withdrawals'], true) ? 'author_id' : 'user_id', $term);
                }
            });
        }
        $rows = AdminPagination::paginate($query->latest('id'), $request);

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
