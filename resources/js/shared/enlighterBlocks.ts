export function getEnlighterBlock(target: HTMLElement | null): HTMLElement | null {
    return target?.closest<HTMLElement>('.wp-block-zibllblock-enlighter') || null;
}

export function toggleEnlighterRaw(block: HTMLElement): void {
    block.querySelector<HTMLElement>('.enlighter-default')?.classList.toggle('enlighter-show-rawcode');
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
    const win = window.open('', '_blank', 'width=980,height=760,scrollbars=yes,resizable=yes');

    if (!win) {
        return;
    }

    const title = block.getAttribute('data-enlighter-title') || document.title || '代码预览';
    const htmlClass = document.documentElement.className;
    const bodyClass = document.body.className;

    win.document.open();
    win.document.write(`<!doctype html>
<html lang="zh-CN" class="${escapeHtml(htmlClass)}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>${escapeHtml(title)}</title>
    <style>
        :root {
            color-scheme: light dark;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            min-height: 100%;
        }

        body {
            margin: 0;
            padding: 24px;
            background: #f6f8fc;
            color: #30343b;
            font: 14px/1.7 Consolas, Monaco, "Courier New", monospace;
            letter-spacing: 0;
        }

        html.dark-theme body,
        body.dark-theme {
            background: #111827;
            color: #f8f8f2;
        }

        pre {
            margin: 0;
            padding: 18px 20px;
            border: 1px solid #e2e6ec;
            border-radius: 6px;
            background: #fff;
            color: inherit;
            white-space: pre;
            overflow: auto;
            tab-size: 4;
            box-shadow: 0 10px 26px rgba(16, 24, 40, .04);
        }

        html.dark-theme pre,
        body.dark-theme pre {
            border-color: #323a4f;
            background: #1f2433;
        }

        @media (max-width: 640px) {
            body {
                padding: 12px;
            }

            pre {
                padding: 14px;
            }
        }
    </style>
</head>
<body class="${escapeHtml(bodyClass)}">
    <pre>${escapeHtml(text)}</pre>
</body>
</html>`);
    win.document.close();
    win.focus();
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
