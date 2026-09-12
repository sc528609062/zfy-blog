<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function quote(Request $request, CartService $cart)
    {
        $data = $request->validate(['coupon' => ['nullable', 'string', 'max:80'], 'address_id' => ['nullable', 'integer']]);

        return response()->json(['code' => 0, 'message' => 'ok', 'data' => $cart->quote($request->user(), $data['coupon'] ?? null, $data['address_id'] ?? null)]);
    }

    public function index(Request $request, CartService $cart)
    {
        return $this->respond($request, ['items' => $cart->items($request->user()), 'addresses' => DB::table('user_addresses')->where('user_id', $request->user()->id)->orderByDesc('is_default')->get()]);
    }

    public function add(Request $request, Product $product, CartService $cart)
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'between:1,100'], 'variant_id' => ['nullable', 'integer']]);
        $cart->add($request->user(), $product, $data['quantity'], $data['variant_id'] ?? null);

        return $this->respond($request, null, '已加入购物车');
    }

    public function update(Request $request, int $item)
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'between:1,100']]);
        $query = DB::table('cart_items')->where('user_id', $request->user()->id)->where('id', $item);
        abort_unless($query->exists(), 404);
        $query->update(['quantity' => $data['quantity'], 'updated_at' => now()]);

        return $this->respond($request, null, '数量已更新');
    }

    public function remove(Request $request, int $item)
    {
        DB::table('cart_items')->where('user_id', $request->user()->id)->where('id', $item)->delete();

        return $this->respond($request, null, '已移除商品');
    }

    public function checkout(Request $request, CartService $cart)
    {
        $data = $request->validate(['request_key' => ['required', 'uuid'], 'gateway' => ['required', 'in:balance,points,epay,alipay_official,wechat_official,hupijiao_v3'], 'address_id' => ['nullable', 'integer'], 'coupon' => ['nullable', 'string', 'max:80']]);
        $order = $cart->checkout($request->user(), $data);
        if ($request->expectsJson()) {
            return $this->respond($request, $order->load('items'), '订单已创建');
        }

        return redirect('/user/orders')->with('status', '订单已创建，请完成付款。');
    }

    public function saveAddress(Request $request, ?int $address = null)
    {
        $data = $request->validate(['recipient' => ['required', 'string', 'max:100'], 'phone' => ['required', 'string', 'max:30'], 'region' => ['required', 'string', 'max:200'], 'address' => ['required', 'string', 'max:500'], 'postal_code' => ['nullable', 'string', 'max:20'], 'is_default' => ['sometimes', 'boolean']]);
        $id = DB::transaction(function () use ($request, $address, $data) {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $query = DB::table('user_addresses')->where('user_id', $request->user()->id);
            if ($address) {
                abort_unless((clone $query)->where('id', $address)->exists(), 404);
            } else {
                abort_if((clone $query)->count() >= 30, 422, '最多保存 30 个地址。');
            }
            if ($data['is_default'] ?? false) {
                (clone $query)->update(['is_default' => false]);
            }
            if ($address) {
                $query->where('id', $address)->update([...$data, 'updated_at' => now()]);

                return $address;
            }

            return DB::table('user_addresses')->insertGetId([...$data, 'user_id' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);
        });

        return $this->respond($request, ['id' => $id], '地址已保存');
    }

    public function removeAddress(Request $request, int $address)
    {
        DB::table('user_addresses')->where('user_id', $request->user()->id)->where('id', $address)->delete();

        return $this->respond($request, null, '地址已删除');
    }

    private function respond(Request $request, mixed $data, string $message = 'ok')
    {
        return $request->expectsJson() ? response()->json(['code' => 0, 'message' => $message, 'data' => $data]) : redirect('/cart')->with('status', $message);
    }
}
