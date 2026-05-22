const DEFAULT_TIME_FORMAT = 'YYYY-MM-DD HH:mm:ss';

function pad(value: number): string {
    return String(value).padStart(2, '0');
}

function formatZfyTime(date: Date, format: string): string {
    const tokens: Record<string, string> = {
        YYYY: String(date.getFullYear()),
        MM: pad(date.getMonth() + 1),
        DD: pad(date.getDate()),
        HH: pad(date.getHours()),
        mm: pad(date.getMinutes()),
        ss: pad(date.getSeconds()),
    };

    return Object.entries(tokens).reduce((value, [token, replacement]) => value.replaceAll(token, replacement), format || DEFAULT_TIME_FORMAT);
}

function startZfyTime(element: HTMLElement): () => void {
    const format = element.dataset.zfyTimeFormat || DEFAULT_TIME_FORMAT;
    const update = () => {
        element.textContent = formatZfyTime(new Date(), format);
    };

    update();
    const timer = window.setInterval(update, 1000);

    return () => window.clearInterval(timer);
}

export function mountZfyTimes(root: ParentNode = document): () => void {
    const cleanups = Array.from(root.querySelectorAll<HTMLElement>('[data-zfy-time-format]')).map(startZfyTime);

    return () => {
        cleanups.forEach((cleanup) => cleanup());
    };
}
