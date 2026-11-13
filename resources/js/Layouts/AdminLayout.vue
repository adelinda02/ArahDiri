<script setup>
// Cangkang panel admin v14 (admin.html): sidebar gelap + konten utama.
import { computed, watch, onMounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth.user);
const view = computed(() => page.url.split('?')[0]);
// Item bertanda true khusus admin/owner; editor konten hanya melihat sisanya.
const ALL = [
    ['/admin', 'Ringkasan'],
    ['Pengecekan rekomendasi'], ['/admin/simulator', 'Simulator'], ['/admin/laporan', 'Laporan siswa'], ['/admin/aturan', 'Aturan rekomendasi', true],
    ['Data & konten'], ['/admin/katalog', 'Katalog'], ['/admin/konten', 'Konten'], ['/admin/akreditasi', 'Akreditasi', true], ['/admin/hasil', 'Data hasil tes', true],
    ['Pengelolaan', null, true], ['/admin/pengguna', 'Pengguna', true], ['/admin/akun', 'Akun admin', true], ['/admin/audit', 'Catatan aktivitas', true],
];
const NAV = computed(() => ALL.filter((n) => !n[2] || user.value.role !== 'editor').map((n) => (n[1] === null ? [n[0]] : n.slice(0, 2))));
const ROLE = { owner: 'Pemilik', admin: 'Administrator', editor: 'Editor konten' };
const newReports = computed(() => page.props.admin?.reports_new ?? 0);
let timer;
function toast(msg) {
    const t = document.getElementById('toast');
    if (!t || !msg) return;
    t.textContent = msg;
    t.style.display = 'block';
    clearTimeout(timer);
    timer = setTimeout(() => (t.style.display = 'none'), 3600);
}
watch(() => page.props.flash, (f) => toast(f?.status || f?.error), { deep: true });
onMounted(() => toast(page.props.flash?.status || page.props.flash?.error));
</script>

<template>
    <div class="admin-app">
        <div class="shell">
            <aside class="sidebar">
                <Link class="brand" href="/admin"><img src="/brand-logo.png" alt="">ArahDiri</Link>
                <div class="eyebrow">Ruang administrasi</div>
                <nav>
                    <template v-for="n in NAV" :key="n[0]">
                        <span v-if="n.length === 1" class="nav-group">{{ n[0] }}</span>
                        <Link v-else as="button" :href="n[0]" :class="{ active: view === n[0] }">{{ n[1] }}<b v-if="n[0] === '/admin/laporan' && newReports" class="nav-count">{{ newReports }}</b></Link>
                    </template>
                </nav>
                <footer>
                    <span>{{ user.email }}<br>{{ ROLE[user.role] }}</span>
                    <a href="/" target="_blank" rel="noopener">Buka website ↗</a>
                    <button type="button" @click="router.post('/keluar')">Keluar</button>
                </footer>
            </aside>
            <main class="main"><slot /></main>
        </div>
        <div id="toast" role="status"></div>
    </div>
</template>

<style scoped>
.nav-group { display: block; margin: 14px 12px 4px; font-size: 10.5px; letter-spacing: .1em; text-transform: uppercase; opacity: .55; }
.nav-count { margin-left: 8px; background: #ef4444; color: #fff; border-radius: 999px; padding: 1px 7px; font-size: 11px; }
</style>
