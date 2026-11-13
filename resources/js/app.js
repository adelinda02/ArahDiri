import '../css/app.css';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import AppLayout from './Layouts/AppLayout.vue';
import { installMotion } from './lib/motion';

createInertiaApp({
    id: 'ad-root',
    title: (title) => (title ? `${title} — ArahDiri` : 'ArahDiri — Temukan arahmu'),
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        const page = pages[`./Pages/${name}.vue`];
        if (!page) throw new Error(`Halaman tidak ditemukan: ${name}`);
        // Semua halaman memakai cangkang v14 (header, footer, navigasi bawah).
        page.default.layout = page.default.layout ?? AppLayout;
        return page;
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) }).use(plugin).mount(el);
        installMotion();
    },
    progress: { color: '#204692' },
});
