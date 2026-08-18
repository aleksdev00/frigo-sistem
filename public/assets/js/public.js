const toggle = document.querySelector('[data-nav-toggle]');
const navigation = document.querySelector('[data-navigation]');

if (toggle && navigation) {
    const closeMenu = () => {
        toggle.setAttribute('aria-expanded', 'false');
        toggle.querySelector('.sr-only').textContent = 'Otvori glavni meni';
    };

    toggle.addEventListener('click', () => {
        const expanded = toggle.getAttribute('aria-expanded') === 'true';
        toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
        toggle.querySelector('.sr-only').textContent = expanded ? 'Otvori glavni meni' : 'Zatvori glavni meni';
    });

    navigation.addEventListener('click', (event) => {
        if (event.target.closest('a')) closeMenu();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
            closeMenu();
            toggle.focus();
        }
    });

    document.addEventListener('click', (event) => {
        if (!navigation.contains(event.target) && !toggle.contains(event.target)) closeMenu();
    });
}

const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const motionGroups = [
    ['.home-section:not(.home-why)', ':scope > .home-section__heading, :scope > .eyebrow, :scope > h2, :scope .product-card, :scope .service-cards > article, :scope .brand-list > a, :scope > ol > li'],
    ['.home-why', ':scope > .home-why__copy, :scope > .home-why__visual'],
    ['.home-cta', ':scope > .public-container > div, :scope > .public-container > .button'],
    ['.catalog-shell', ':scope > .page-intro, :scope > .catalog-filters, :scope > .catalog-results-heading, :scope > .product-grid .product-card, :scope > .empty-state, :scope > .pagination'],
    ['.product-detail .content-section', ':scope > .section-heading, :scope > .prose, :scope .specification-list > div, :scope .product-card'],
    ['.product-detail > .cta-panel', ':scope > div, :scope > .button'],
];

const revealElements = [];
motionGroups.forEach(([groupSelector, itemSelector]) => {
    document.querySelectorAll(groupSelector).forEach((group) => {
        const items = group.querySelectorAll(itemSelector);
        if (!items.length) return;

        group.classList.add('motion-group');
        items.forEach((item, index) => {
            item.classList.add('motion-item');
            item.style.setProperty('--motion-order', String(index));
        });
        revealElements.push(group);
    });
});

if (revealElements.length && 'IntersectionObserver' in window && !reducedMotion) {
    const revealObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;

            entry.target.classList.add('is-revealed');
            observer.unobserve(entry.target);
            window.setTimeout(() => {
                entry.target.querySelectorAll('.motion-item').forEach((item) => {
                    item.classList.remove('motion-item');
                    item.style.removeProperty('--motion-order');
                });
                entry.target.classList.add('motion-complete');
            }, 800);
        });
    }, {
        rootMargin: '0px 0px -8% 0px',
        threshold: 0.08,
    });

    revealElements.forEach((element) => revealObserver.observe(element));
    document.documentElement.classList.add('reveal-ready');
}

const header = document.querySelector('.site-header');
if (header) {
    const updateHeader = () => header.classList.toggle('is-scrolled', window.scrollY > 12);
    updateHeader();
    window.addEventListener('scroll', updateHeader, { passive: true });
}
