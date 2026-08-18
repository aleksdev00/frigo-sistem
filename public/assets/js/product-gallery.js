document.querySelectorAll('[data-gallery]').forEach((gallery) => {
    const main = gallery.querySelector('[data-gallery-main]');
    if (!main) return;

    gallery.querySelectorAll('[data-gallery-thumb]').forEach((thumbnail) => {
        thumbnail.addEventListener('click', () => {
            if (thumbnail.classList.contains('is-active')) return;

            main.src = thumbnail.dataset.src || main.src;
            main.alt = thumbnail.dataset.alt || '';
            if (thumbnail.dataset.width) main.width = Number(thumbnail.dataset.width); else main.removeAttribute('width');
            if (thumbnail.dataset.height) main.height = Number(thumbnail.dataset.height); else main.removeAttribute('height');
            main.classList.remove('is-changing');
            void main.offsetWidth;
            main.classList.add('is-changing');

            gallery.querySelectorAll('[data-gallery-thumb]').forEach((item) => {
                const active = item === thumbnail;
                item.classList.toggle('is-active', active);
                item.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
        });
    });
});
