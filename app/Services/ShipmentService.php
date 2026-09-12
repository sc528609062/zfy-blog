<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShipmentService
{
    public function update(Shipment $shipment, array $data): void
    {
        DB::transaction(function () use ($shipment, $data) {
            $order = Order::whereKey($shipment->order_id)->lockForUpdate()->firstOrFail();
            $shipment = Shipment::whereKey($shipment->id)->lockForUpdate()->firstOrFail();
            if ($order->status !== 'paid') {
                throw ValidationException::withMessages(['order' => '订单未支付或已退款。']);
            }
            $next = $data['status'];
            $allowed = ['pending' => ['pending', 'shipped'], 'shipped' => ['shipped', 'received'], 'received' => ['received']];
            if (! in_array($next, $allowed[$shipment->status] ?? [], true)) {
                throw ValidationException::withMessages(['status' => '发货状态不能倒退或跳过发货。']);
            }
            if ($next === 'shipped' && (! filled($data['carrier'] ?? $shipment->carrier) || ! filled($data['tracking_no'] ?? $shipment->tracking_no))) {
                throw ValidationException::withMessages(['tracking_no' => '发货需填写物流公司与运单号。']);
            }
            zfy_validate('zfy_shipment_saving', $shipment, $data);
            $changed = $next !== $shipment->status;
            $shipment->fill($data);
            if ($next === 'shipped') {
                $shipment->shipped_at ??= now();
            }
            if ($next === 'received') {
                $shipment->received_at ??= now();
            }
            $shipment->save();
            if ($changed) {
                zfy_after_commit('zfy_shipment_'.$next, $shipment, $order);
            }
        });
    }

    public function receive(Order $order, User $user): void
    {
        abort_unless($order->user_id === $user->id, 403);
        $this->update(Shipment::where('order_id', $order->id)->firstOrFail(), ['status' => 'received']);
    }
}
