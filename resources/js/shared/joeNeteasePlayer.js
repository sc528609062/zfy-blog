const APLAYER_SCRIPT_ID = 'zfy-aplayer-script';
const APLAYER_STYLE_ID = 'zfy-aplayer-style';

let aplayerLoader;

export function mountJoeNeteasePlayers(root = document) {
    const scope = root || document;
    const elements = [...scope.querySelectorAll('joe-mlist, joe-music')]
        .filter((element) => element instanceof HTMLElement && element.dataset.zfyMounted !== 'true');

    elements.forEach((element) => {
        element.dataset.zfyMounted = 'true';
        void mountJoeNeteasePlayer(element);
    });

    return () => {
        elements.forEach((element) => {
            element.__zfyAPlayer?.destroy?.();
            delete element.__zfyAPlayer;
        });
    };
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

    element.innerHTML = '<span style="display: block" class="_content"></span>';
    const container = directChildByClass(element, '_content');

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
        const tracks = json?.data?.tracks || [];

        if ((!Array.isArray(audio) || audio.length === 0) && Array.isArray(tracks) && tracks.length > 0) {
            renderLocalPlaylist(element, {
                name: json?.data?.name || '本地歌单',
                tracks,
                color,
            });
            return;
        }

        if (!Array.isArray(audio) || audio.length === 0) {
            throw new Error('本地歌单没有可播放的音频文件。');
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

function renderLocalPlaylist(element, { name, tracks, color }) {
    const panel = document.createElement('div');
    panel.className = 'zfy-netease-local-list';

    const head = document.createElement('div');
    head.className = 'zfy-netease-local-list__head';

    const dot = document.createElement('span');
    dot.className = 'zfy-netease-local-list__dot';
    dot.style.backgroundColor = color;

    const title = document.createElement('strong');
    title.textContent = name;

    const hint = document.createElement('small');
    hint.textContent = '本地歌单元数据，未配置音频文件';

    head.append(dot, title, hint);

    const list = document.createElement('ol');
    list.className = 'zfy-netease-local-list__items';

    tracks.forEach((track, index) => {
        const item = document.createElement('li');

        const number = document.createElement('span');
        number.className = 'zfy-netease-local-list__index';
        number.textContent = String(index + 1);

        const song = document.createElement('span');
        song.className = 'zfy-netease-local-list__title';
        song.textContent = String(track?.name || '本地歌曲');

        const artist = document.createElement('span');
        artist.className = 'zfy-netease-local-list__artist';
        artist.textContent = String(track?.artist || '本地音乐');

        item.append(number, song, artist);
        list.appendChild(item);
    });

    panel.append(head, list);
    element.replaceChildren(panel);
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
