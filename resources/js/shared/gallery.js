export function mountGallery() {
    const links = [...document.querySelectorAll('[data-gallery-image]')];
    if (!links.length) return;
    const dialog = document.createElement('dialog');
    dialog.className = 'zfy-gallery-dialog';
    const image = document.createElement('img');
    const caption = document.createElement('p');
    const tools = document.createElement('div');
    let index = 0;
    const show = next => {
        index = (next + links.length) % links.length;
        image.src = links[index].href;
        image.alt = links[index].dataset.caption || '';
        caption.textContent = image.alt;
    };
    for (const [label, symbol, handler] of [['上一张', '←', () => show(index - 1)], ['下一张', '→', () => show(index + 1)], ['关闭', '×', () => dialog.close()]]) {
        const button = document.createElement('button');
        button.type = 'button';
        button.setAttribute('aria-label', label);
        button.title = label;
        button.textContent = symbol;
        button.addEventListener('click', handler);
        tools.append(button);
    }
    dialog.append(tools, image, caption);
    document.body.append(dialog);
    dialog.addEventListener('keydown', event => {
        if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
            event.preventDefault();
            show(index + (event.key === 'ArrowLeft' ? -1 : 1));
        }
    });
    links.forEach((link, i) => link.addEventListener('click', event => {
        event.preventDefault();
        show(i);
        dialog.showModal();
    }));
}
