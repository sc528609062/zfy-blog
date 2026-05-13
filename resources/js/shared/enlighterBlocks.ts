export function getEnlighterBlock(target: HTMLElement | null): HTMLElement | null {
    return target?.closest<HTMLElement>('.wp-block-zibllblock-enlighter') || null;
}

export function toggleEnlighterRaw(block: HTMLElement): void {
    block.querySelector<HTMLElement>('.enlighter-default')?.classList.toggle('is-raw-visible');
}

export async function copyEnlighterCode(block: HTMLElement): Promise<boolean> {
    const text = readEnlighterCode(block);

    if (!text.trim()) {
        return false;
    }

    await navigator.clipboard.writeText(text);
    return true;
}

export function openEnlighterWindow(block: HTMLElement): void {
    const text = readEnlighterCode(block);
    const win = window.open('', '_blank', 'noopener,noreferrer,width=980,height=760');

    if (!win) {
        return;
    }

    const escaped = escapeHtml(text);
    const title = block.getAttribute('data-enlighter-title') || '代码预览';

    win.document.open();
    win.document.write(`<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>${escapeHtml(title)}</title>
    <style>
        :root { color-scheme: light; }
        body { margin: 0; padding: 24px; background: #f6f8fc; color: #111; font: 14px/1.7 Consolas, Monaco, 'Courier New', monospace; }
        pre { margin: 0; padding: 18px 20px; border: 2px solid #111; border-radius: 8px; background: #fff; white-space: pre-wrap; word-break: break-word; }
    </style>
</head>
<body>
    <pre>${escaped}</pre>
</body>
</html>`);
    win.document.close();
}

export function pulseEnlighterButton(button: HTMLElement, activeClass = 'is-copied'): void {
    button.classList.add(activeClass);
    window.setTimeout(() => button.classList.remove(activeClass), 1200);
}

function readEnlighterCode(block: HTMLElement): string {
    return block.querySelector<HTMLElement>('.enlighter-origin')?.textContent
        || block.querySelector<HTMLElement>('.enlighter-raw')?.textContent
        || '';
}

function escapeHtml(value: string): string {
    return value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
