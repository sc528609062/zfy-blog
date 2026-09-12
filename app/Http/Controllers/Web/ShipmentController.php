<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Refund;
use App\Services\CommerceOperations;
use App\Services\ShipmentService;
use Illuminate\Http\Request;

class ShipmentController extends Controller
{
    public function returnShipment(Request $request, Refund $refund, CommerceOperations $operations)
    {
        $data = $request->validate(['carrier' => ['required', 'string', 'max:100'], 'tracking_no' => ['required', 'string', 'max:150']]);
        $operations->submitReturn($request->user(), $refund, $data['carrier'], $data['tracking_no']);

        return $request->expectsJson() ? response()->json(['code' => 0, 'message' => 'ok', 'data' => null]) : back()->with('status', '退货物流已提交。');
    }

    public function receive(Request $request, Order $order, ShipmentService $shipments)
    {
        $shipments->receive($order, $request->user());

        return $request->expectsJson() ? response()->json(['code' => 0, 'message' => 'ok', 'data' => null]) : back()->with('status', '已确认收货。');
    }
}
