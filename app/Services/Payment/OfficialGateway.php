<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Validation\ValidationException;
use Yansongda\Artful\Exception\InvalidResponseException;
use Yansongda\Pay\Exception\Exception as PayException;
use Yansongda\Pay\Pay;
use Yansongda\Supports\Collection;

abstract class OfficialGateway extends AbstractGateway implements AmountRefundQueryGateway, ClosableGateway, ConfigurableGateway, VerifiedNotificationGateway
{
    abstract protected function provider(): string;

    public function assertConfigured(): void
    {
        $name = $this->provider();
        $config = config('payments.'.$name, []);
        $required = $name === 'alipay' ? ['app_id', 'app_secret_cert', 'alipay_public_cert_path'] : ['mch_id', 'mch_secret_key', 'mch_secret_cert', 'mch_public_cert_path', 'mp_app_id'];
        foreach ($required as $key) {
            if (! filled($config[$key] ?? null)) {
                throw ValidationException::withMessages(['gateway' => $this->displayName().'商户配置不完整。']);
            }
        }
    }

    protected function sdk(): mixed
    {
        $this->assertConfigured();
        $name = $this->provider();
        $config = config('payments.'.$name, []);
        $config['notify_url'] = route('payments.notify', $this->code());
        $config['return_url'] = url('/user/orders');
        Pay::config([$name => ['default' => $config], 'logger' => ['enable' => false], 'http' => ['timeout' => 15, 'connect_timeout' => 5]]);

        return $name === 'alipay' ? Pay::alipay() : Pay::wechat();
    }

    public function createPayment(Order $order): Payment
    {
        $payload = ['out_trade_no' => $order->order_no];
        if ($this->provider() === 'alipay') {
            $payload += ['total_amount' => (string) $order->total_amount, 'subject' => $order->items()->first()?->title ?? $order->order_no];
        } else {
            $payload += ['description' => $order->items()->first()?->title ?? $order->order_no, 'amount' => ['total' => (int) bcmul((string) $order->total_amount, '100', 0), 'currency' => 'CNY']];
        }
        $response = $this->sdk()->scan($payload)->all();
        $qr = $response[$this->provider() === 'alipay' ? 'qr_code' : 'code_url'] ?? null;
        if (! is_string($qr) || $qr === '') {
            throw ValidationException::withMessages(['gateway' => '支付平台未返回付款二维码。']);
        }

        return $this->rememberPayment($order, $payload, ['qr_code' => $qr]);
    }

    public function notification(array $payload): ?array
    {
        try {
            $data = $this->sdk()->callback($payload)->all();

            return $this->normalize($data);
        } catch (\Throwable $exception) {
            logger()->warning('Payment notification rejected', ['gateway' => $this->code(), 'exception' => $exception::class]);

            return null;
        }
    }

    public function verifyNotify(array $payload): bool
    {
        return $this->notification($payload) !== null;
    }

    private function normalize(array $data): ?array
    {
        if ($this->provider() === 'alipay') {
            if (! in_array($data['trade_status'] ?? '', ['TRADE_SUCCESS', 'TRADE_FINISHED'], true) || (isset($data['app_id']) && $data['app_id'] !== config('payments.alipay.app_id'))) {
                return null;
            }

            return ['out_trade_no' => $data['out_trade_no'] ?? '', 'trade_no' => $data['trade_no'] ?? '', 'money' => $data['total_amount'] ?? ''];
        }
        $data = $data['resource'] ?? $data;
        if (($data['trade_state'] ?? '') !== 'SUCCESS' || ($data['mchid'] ?? '') !== config('payments.wechat.mch_id') || ($data['appid'] ?? '') !== config('payments.wechat.mp_app_id') || ($data['amount']['currency'] ?? '') !== 'CNY') {
            return null;
        }

        return ['out_trade_no' => $data['out_trade_no'] ?? '', 'trade_no' => $data['transaction_id'] ?? '', 'money' => bcdiv((string) ($data['amount']['total'] ?? -1), '100', 2)];
    }

    public function query(Payment $payment): array
    {
        $response = $this->sdk()->query(['out_trade_no' => $payment->order->order_no])->all();
        $normalized = $this->normalize($response);
        if ($normalized) {
            app(PaymentManager::class)->completeVerified($this->code(), $normalized);
        }

        return ['status' => $payment->fresh()->status, 'gateway' => $this->code(), 'source' => 'gateway'];
    }

    public function close(Payment $payment): array
    {
        $response = $this->sdk()->close(['out_trade_no' => $payment->order->order_no])->all();
        if ($this->provider() === 'alipay' && (string) ($response['code'] ?? '') !== '10000') {
            throw ValidationException::withMessages(['payment' => '支付宝尚未确认关单。']);
        }

        return $response;
    }

    public function refund(Payment $payment, string $refundNo, string $amount): array
    {
        $payload = ['out_trade_no' => $payment->order->order_no];
        $payload += $this->provider() === 'alipay'
            ? ['out_request_no' => $refundNo, 'refund_amount' => $amount]
            : ['out_refund_no' => $refundNo, 'amount' => ['refund' => (int) bcmul($amount, '100', 0), 'total' => (int) bcmul((string) $payment->amount, '100', 0), 'currency' => 'CNY']];
        $response = $this->sdk()->refund($payload)->all();

        return $this->refundResult($response, $payment, $refundNo, $amount, false);
    }

    public function queryRefund(Payment $payment, string $refundNo): array
    {
        return $this->queryRefundAmount($payment, $refundNo, (string) $payment->amount);
    }

    public function queryRefundAmount(Payment $payment, string $refundNo, string $amount): array
    {
        $payload = $this->provider() === 'alipay' ? ['out_trade_no' => $payment->order->order_no, 'out_request_no' => $refundNo] : ['out_refund_no' => $refundNo];

        try {
            $response = $this->sdk()->query($payload + ['_action' => 'refund'])->all();
        } catch (InvalidResponseException $exception) {
            $response = $exception->response instanceof Collection ? $exception->response->all() : $exception->response;
            $code = $this->provider() === 'alipay' ? PayException::RESPONSE_BUSINESS_CODE_WRONG : PayException::RESPONSE_CODE_WRONG;
            if (! is_array($response) || $exception->getCode() !== $code) {
                throw $exception;
            }
            $response = $response['alipay_trade_fastpay_refund_query_response'] ?? $response;
            if (! is_array($response) || ! $this->refundMissing($response)) {
                throw $exception;
            }

            // A definitive channel response permits retrying only the original refund number.
            return ['status' => 'not_found', 'reference' => $refundNo];
        }

        return $this->refundResult($response, $payment, $refundNo, $amount, true);
    }

    private function refundMissing(array $response): bool
    {
        return $this->provider() === 'alipay'
            ? (string) ($response['code'] ?? '') === '40004' && ($response['sub_code'] ?? '') === 'ACQ.REFUND_NOT_EXIST'
            : ($response['code'] ?? '') === 'RESOURCE_NOT_EXISTS';
    }

    private function refundResult(array $response, Payment $payment, string $refundNo, string $amount, bool $query): array
    {
        if ($query && $this->refundMissing($response)) {
            return ['status' => 'not_found', 'reference' => $refundNo];
        }
        if (($response['out_trade_no'] ?? '') !== $payment->order->order_no) {
            throw ValidationException::withMessages(['refund' => '退款返回的商户订单不匹配。']);
        }
        if ($this->provider() === 'alipay') {
            if ((string) ($response['code'] ?? '') !== '10000' || ($response['trade_no'] ?? '') !== $payment->trade_no) {
                throw ValidationException::withMessages(['refund' => '支付宝退款未确认。']);
            }
            if ($query && ($response['out_request_no'] ?? '') !== $refundNo) {
                throw ValidationException::withMessages(['refund' => '支付宝退款请求不匹配。']);
            }
            $returnedAmount = $response[$query ? 'refund_amount' : 'refund_fee'] ?? '';
            $this->assertRefundAmount($returnedAmount, $amount);
            $complete = $query ? ($response['refund_status'] ?? '') === 'REFUND_SUCCESS' : in_array($response['fund_change'] ?? '', ['Y', 'N'], true);

            return ['status' => $complete ? 'refunded' : 'processing', 'reference' => $response['trade_no'], 'response' => $response];
        }
        if (($response['out_refund_no'] ?? '') !== $refundNo || ! filled($response['refund_id'] ?? null) || ($response['amount']['currency'] ?? '') !== 'CNY') {
            throw ValidationException::withMessages(['refund' => '微信退款标识不匹配。']);
        }
        $cents = $response['amount']['refund'] ?? null;
        if (! is_int($cents) || $cents < 0) {
            throw ValidationException::withMessages(['refund' => '微信退款金额无效。']);
        }
        $this->assertRefundAmount(bcdiv((string) $cents, '100', 2), $amount);
        $status = $response['status'] ?? '';
        if (! in_array($status, ['SUCCESS', 'PROCESSING'], true)) {
            throw ValidationException::withMessages(['refund' => '微信退款未确认。']);
        }

        return ['status' => $status === 'SUCCESS' ? 'refunded' : 'processing', 'reference' => $response['refund_id'] ?? '', 'response' => $response];
    }

    private function assertRefundAmount(mixed $actual, string $expected): void
    {
        if ((! is_string($actual) && ! is_numeric($actual)) || ! preg_match('/^\d+(?:\.\d{1,2})?$/', (string) $actual) || bccomp((string) $actual, $expected, 2) !== 0) {
            throw ValidationException::withMessages(['refund' => '渠道确认的退款金额不匹配。']);
        }
    }
}
