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
