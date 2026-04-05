/**
 * Chef Jon Chung — Public Site JS
 */

document.addEventListener('DOMContentLoaded', () => {
    // ── Mobile Menu Toggle ─────────────────
    const menuBtn = document.getElementById('mobileMenuBtn');
    const nav = document.getElementById('siteNav');

    if (menuBtn && nav) {
        menuBtn.addEventListener('click', () => {
            nav.classList.toggle('open');
            menuBtn.classList.toggle('active');
        });

        // Close menu when clicking a link
        nav.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                nav.classList.remove('open');
                menuBtn.classList.remove('active');
            });
        });
    }

    // ── Ingredient checkbox persistence ────
    // Save checked state during page session
    const checkboxes = document.querySelectorAll('.ingredient-check input[type="checkbox"]');
    checkboxes.forEach((cb, i) => {
        const key = 'ingredient-' + i;
        const saved = sessionStorage.getItem(key);
        if (saved === 'true') cb.checked = true;

        cb.addEventListener('change', () => {
            sessionStorage.setItem(key, cb.checked);
        });
    });
});
