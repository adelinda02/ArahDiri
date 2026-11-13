// Port langsung motion.js v14: animasi muncul saat terlihat + gelombang klik pada tombol.
const SELECTOR = '.v2-hero-copy,.v2-hero-photo,.ed-section-head,.ed-project,.ed-step,.ed-riasec,.ed-cta,.v2-next,.v2-level,.v2-explore-grid>a,.v2-explore-grid>button,.v2-shortcuts>a,.v2-shortcuts>button,.katalog-item-card,.fav-card';

export function installMotion() {
    const reduce = matchMedia('(prefers-reduced-motion: reduce)');
    let observer;

    function decorate() {
        if (reduce.matches) return;
        if (observer) observer.disconnect();
        observer = new IntersectionObserver(
            (entries) => entries.forEach((e) => {
                if (e.isIntersecting) {
                    e.target.classList.add('v2-reveal');
                    observer.unobserve(e.target);
                }
            }),
            { threshold: 0.08 },
        );
        document.querySelectorAll(SELECTOR).forEach((el, i) => {
            el.style.setProperty('--reveal-delay', Math.min((i % 4) * 55, 165) + 'ms');
            observer.observe(el);
        });
    }

    const app = document.getElementById('ad-root');
    if (app) new MutationObserver(decorate).observe(app, { childList: true, subtree: true });
    decorate();

    document.addEventListener('click', (e) => {
        if (reduce.matches) return;
        const b = e.target.closest('.ed-button,.btn-primary,.v2-explore-grid>a,.v2-explore-grid>button');
        if (!b || b.disabled) return;
        const rect = b.getBoundingClientRect();
        const w = document.createElement('span');
        w.className = 'v2-click-wave';
        w.style.left = e.clientX - rect.left - 15 + 'px';
        w.style.top = e.clientY - rect.top - 15 + 'px';
        b.style.position = 'relative';
        b.style.overflow = 'hidden';
        b.appendChild(w);
        setTimeout(() => w.remove(), 550);
    });
}
