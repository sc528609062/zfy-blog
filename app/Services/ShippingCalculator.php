<?php

namespace App\Services;

use App\Models\ShippingTemplate;
use Illuminate\Validation\ValidationException;

class ShippingCalculator
{
    public function calculate(array $items, ?string $region = null): string
    {
        $groups = [];
        foreach ($items as $item) {
            $product = $item['product'];
            if ($product->type !== 'physical') {
                continue;
            }
            $templateId = data_get($product->metadata, 'shipping_template_id');
            $key = $templateId ? 'template-'.$templateId : 'product-'.$product->id;
            $groups[$key] ??= ['template_id' => $templateId, 'fee' => (string) data_get($product->metadata, 'shipping_fee', '0.00'), 'quantity' => 0, 'amount' => '0.00'];
            $groups[$key]['quantity'] += $item['quantity'];
            $groups[$key]['amount'] = bcadd($groups[$key]['amount'], bcmul((string) $item['price'], (string) $item['quantity'], 2), 2);
        }
        $total = '0.00';
        foreach ($groups as $group) {
            if ($group['template_id']) {
                $template = ShippingTemplate::find($group['template_id']);
                if (! $template) {
                    throw ValidationException::withMessages(['shipping' => '商品运费模板不存在。']);
                }
                foreach ($template->regions ?? [] as $override) {
                    if ($region && str_starts_with($region, $override['prefix'])) {
                        if ($override['excluded'] ?? false) {
                            throw ValidationException::withMessages(['shipping' => '该商品暂不配送至所选地区。']);
                        }
                        $template->fill(array_intersect_key($override, array_flip(['base_fee', 'base_quantity', 'additional_fee', 'free_threshold'])));
                        break;
                    }
                }
                $fee = $template->free_threshold !== null && bccomp($group['amount'], $template->free_threshold, 2) >= 0 ? '0.00' : bcadd($template->base_fee, bcmul($template->additional_fee, (string) max(0, $group['quantity'] - $template->base_quantity), 2), 2);
            } else {
                $fee = $group['fee'];
                if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $fee)) {
                    throw ValidationException::withMessages(['shipping' => '商品运费配置无效。']);
                }
            }
            $total = bcadd($total, $fee, 2);
        }

        return $total;
    }
}
