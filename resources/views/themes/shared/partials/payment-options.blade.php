@unless($externalOnly ?? false)
    <option value="balance">余额</option>
    <option value="points">积分</option>
@endunless
@foreach(app(\App\Services\Payment\PaymentManager::class)->available() as $code => $label)
    <option value="{{ $code }}">{{ $label }}</option>
@endforeach
