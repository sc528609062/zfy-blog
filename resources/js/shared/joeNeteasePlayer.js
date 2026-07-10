const APLAYER_SCRIPT_ID = 'zfy-aplayer-script';
const APLAYER_STYLE_ID = 'zfy-aplayer-style';

let aplayerLoader;

export function mountJoeNeteasePlayers(root = document) {
    const scope = root || document;
    const elements = [...scope.querySelectorAll('joe-mlist, joe-mp3, joe-music')]
        .filter((element) => element instanceof HTMLElement && element.dataset.zfyMounted !== 'true');

    elements.forEach((element) => {
        element.dataset.zfyMounted = 'true';
        void (element.tagName.toLowerCase() === 'joe-mp3'
            ? mountJoeMp3Player(element)
            : mountJoeNeteasePlayer(element));
    });

    return () => {
        elements.forEach((element) => {
            element.__zfyAPlayer?.destroy?.();
            delete element.__zfyAPlayer;
        });
    };
}

async function mountJoeMp3Player(element) {
    const url = (element.getAttribute('url') || '').trim();
    const name = (element.getAttribute('name') || '').trim() || '音频名称';
    const cover = (element.getAttribute('cover') || '').trim();
    const theme = (element.getAttribute('theme') || '#1989fa').trim();
    const autoplay = element.hasAttribute('autoplay');

    if (!url) {
        renderError(element, '音频地址未填写');
        return;
    }

    const container = document.createElement('span');
    container.className = '_content';
    container.style.display = 'block';
    element.replaceChildren(container);

    try {
        const APlayer = await loadAPlayer();
        element.__zfyAPlayer = new APlayer({
            container,
            theme,
            autoplay,
            audio: [{
                url,
                name,
                cover,
            }],
        });
    } catch (error) {
        console.error('本地音频播放器加载失败:', error);
        renderError(element, error?.message || '音频播放器加载失败');
    }
}

async function mountJoeNeteasePlayer(element) {
    const id = (element.getAttribute('id') || '').trim();
    const type = element.tagName.toLowerCase() === 'joe-mlist' ? 'playlist' : 'song';
    const color = element.getAttribute('color') || '#1989fa';
    const autoplay = element.hasAttribute('autoplay');

    if (!id) {
        element.innerHTML = '<span class="zfy-netease-error">网易云ID未填写</span>';
        return;
    }

    element.innerHTML = '<div class="zfy-netease-player"><div class="_content"></div></div>';
    const playerShell = directChildByClass(element, 'zfy-netease-player');
    const container = directChildByClass(playerShell, '_content');

    try {
        const response = await fetch(`/api/v1/netease/${type}/${encodeURIComponent(id)}`, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const json = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(json?.message || `HTTP ${response.status}`);
        }

        const audio = json?.data?.audio || [];

        if (!Array.isArray(audio) || audio.length === 0) {
            throw new Error('网易云未返回可播放音频。');
        }

        const APlayer = await loadAPlayer();
        element.__zfyAPlayer = new APlayer({
            container,
            lrcType: 3,
            theme: color,
            autoplay,
            audio,
        });
    } catch (error) {
        console.error('网易云音乐本地播放器加载失败:', error);
        renderError(element, error?.message || '网易云音乐加载失败');
    }
}

function renderError(element, message) {
    const error = document.createElement('span');
    error.className = 'zfy-netease-error';
    error.textContent = message;
    element.replaceChildren(error);
}

function directChildByClass(element, className) {
    for (const child of element.children) {
        if (child.classList?.contains(className)) {
            return child;
        }
    }

    return element;
}

function loadAPlayer() {
    if (window.APlayer) {
        ensureAPlayerStyle();
        return Promise.resolve(window.APlayer);
    }

    aplayerLoader ||= new Promise((resolve, reject) => {
        ensureAPlayerStyle();

        const existingScript = document.getElementById(APLAYER_SCRIPT_ID);
        if (existingScript) {
            existingScript.addEventListener('load', () => resolve(window.APlayer), { once: true });
            existingScript.addEventListener('error', reject, { once: true });
            return;
        }

        const script = document.createElement('script');
        script.id = APLAYER_SCRIPT_ID;
        script.src = '/vendor/aplayer/APlayer.min.js';
        script.defer = true;
        script.onload = () => resolve(window.APlayer);
        script.onerror = reject;
        document.head.appendChild(script);
    });

    return aplayerLoader;
}

function ensureAPlayerStyle() {
    if (document.getElementById(APLAYER_STYLE_ID)) {
        return;
    }

    const link = document.createElement('link');
    link.id = APLAYER_STYLE_ID;
    link.rel = 'stylesheet';
    link.href = '/vendor/aplayer/APlayer.min.css';
    document.head.appendChild(link);
}
