export function mountCartQuote() {
    const form = document.querySelector('.zfy-cart-checkout');
    if (!form) return;
    const output = form.querySelector('.zfy-cart-quote');
    const submit = form.querySelector('button[type="submit"], button.a-primary');
    let request = null;
    const refresh = async () => {
        request?.abort();
        request = new AbortController();
        submit.disabled = true;
        try {
            const params = new URLSearchParams({ coupon: form.querySelector('[name=coupon]').value, address_id: form.querySelector('[name=address_id]').value });
            const response = await fetch('/cart/quote?' + params, { signal: request.signal, headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            const result = await response.json();
            if (!response.ok) throw new Error(Object.values(result.errors || {}).flat().join(' ') || result.message);
            const quote = result.data;
            output.textContent = `商品 ¥${quote.subtotal} + 运费 ¥${quote.shipping_fee} - 优惠 ¥${quote.discount} = 应付 ¥${quote.total}`;
            submit.disabled = false;
        } catch (error) {
            if (error.name !== 'AbortError') output.textContent = error.message;
        }
    };
    form.querySelector('.zfy-cart-quote-refresh').addEventListener('click', refresh);
    form.querySelector('[name=coupon]').addEventListener('change', refresh);
    form.querySelector('[name=address_id]').addEventListener('change', refresh);
    void refresh();
}
