export async function adminRequest(url: string, csrf: string, method = 'GET', data?: unknown) {
    const response = await fetch(url, {
        method,
        headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
        body: data === undefined ? undefined : JSON.stringify(data),
    });
    const result = await response.json().catch(() => ({}));
    if (!response.ok) {
        throw new Error(Object.values(result.errors || {}).flat().join('\n') || result.message || '请求失败');
    }
    return result;
}
