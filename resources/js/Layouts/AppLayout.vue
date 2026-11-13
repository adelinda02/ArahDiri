<script setup>
// Cangkang halaman ArahDiri v14: splash, header (brand, nav, cari, akun, tema), konten, footer, toast, navigasi bawah.
import { computed, ref, watch, onMounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import Go from '../Components/Go.vue';
import Icon from '../Components/Icon.vue';
import TopSearch from '../Components/TopSearch.vue';
import SplashScreen from '../Components/SplashScreen.vue';
import { VIEW_URL, homeUrl } from '../lib/routes';
import { toast } from '../lib/toast';

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
const view = computed(() => page.props.view ?? '');
const home = computed(() => homeUrl(user.value));

// Navigasi bawah disembunyikan di halaman masuk/daftar/awal (sama dengan v14).
const hideBottomNav = computed(() => ['login', 'daftar', 'landing', 'onboarding'].includes(view.value));
const isAuthView = computed(() => ['login', 'daftar'].includes(view.value));
const exploreViews = ['jurusan', 'kampus', 'karier', 'cari', 'smk', 'referensi'];

function current(v) {
    if (v === 'mulai') return ['beranda', 'landing'].includes(view.value);
    return view.value === v;
}

// Tema: atribut data-theme pada <html>, disimpan di localStorage 'arahdiri-theme'.
const isDark = ref(false);
function readTheme() {
    const t = document.documentElement.getAttribute('data-theme');
    isDark.value = t === 'dark' || (!t && matchMedia('(prefers-color-scheme: dark)').matches);
}
function toggleTheme() {
    const next = isDark.value ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    try { localStorage.setItem('arahdiri-theme', next); } catch (e) { /* mode privat */ }
    readTheme();
}

// Menu Eksplorasi ditutup setiap pindah halaman.
const explore = ref(null);
const mainKey = ref(0);
router.on('navigate', () => {
    if (explore.value) explore.value.open = false;
    mainKey.value++;
});

// Pesan kilat dari server ditampilkan sebagai toast v14.
watch(() => page.props.flash, (f) => { if (f?.status) toast(f.status); else if (f?.error) toast(f.error); }, { deep: true });
onMounted(() => {
    readTheme();
    const f = page.props.flash;
    if (f?.status || f?.error) setTimeout(() => toast(f.status || f.error), 1600);
});
</script>

<template>
    <SplashScreen />

    <div class="glass-bg-mesh" aria-hidden="true">
        <div class="glass-orb orb-1"></div>
        <div class="glass-orb orb-2"></div>
        <div class="glass-orb orb-3"></div>
        <div class="glass-orb orb-4"></div>
    </div>

    <header class="top">
        <div class="wrap top-row">
            <Go :href="home" class="brand" aria-label="ArahDiri, ke halaman awal" title="ArahDiri - Beranda">
                <span class="brand-icon" id="brand-compass-box"><img src="/brand-logo.png" alt="" class="star-brand-image"></span>
                <span class="brand-text">
                    <span class="brand-name">ArahDiri</span>
                    <span class="brand-sub">Panduan Penjurusan &amp; Karier</span>
                </span>
            </Go>

            <nav class="ad-nav" aria-label="Navigasi utama">
                <Go :href="home" :aria-current="current('mulai') ? 'page' : null">Beranda</Go>
                <details ref="explore" class="ad-explore">
                    <summary>Eksplorasi <span aria-hidden="true">⌄</span></summary>
                    <div class="ad-dropdown">
                        <Go :href="VIEW_URL.jurusan" :aria-current="current('jurusan') ? 'page' : null"><b>Jurusan</b><small>Temukan bidang studi</small></Go>
                        <Go :href="VIEW_URL.karier" :aria-current="current('karier') ? 'page' : null"><b>Karier</b><small>Jelajahi profesi</small></Go>
                        <Go :href="VIEW_URL.kampus" :aria-current="current('kampus') ? 'page' : null"><b>Kampus</b><small>Kenali tempat belajar</small></Go>
                        <Go href="/program-smk" :aria-current="current('smk') ? 'page' : null"><b>Program SMK</b><small>Spektrum keahlian SMK</small></Go>
                    </div>
                </details>
                <Go :href="VIEW_URL.tes" :aria-current="current('tes') ? 'page' : null">Tes Minat</Go>
            </nav>

            <TopSearch />

            <div class="top-actions">
                <Go v-if="user" :href="VIEW_URL.profil" class="pill auth-pill" id="authpill" :title="`Buka Profil Pengguna (${user.name})`">
                    <Icon name="user" /><span><b>{{ user.name }}</b></span>
                </Go>
                <Go v-else :href="VIEW_URL.login" class="pill auth-pill" id="authpill" title="Halaman Masuk & Pendaftaran Akun">
                    <Icon name="login" /><span><b>Masuk / Daftar</b></span>
                </Go>
                <button type="button" class="theme-btn" id="themebtn" :aria-label="isDark ? 'Beralih ke mode terang' : 'Beralih ke mode gelap'" :title="isDark ? 'Mode terang' : 'Mode gelap'" @click="toggleTheme">
                    <Icon :name="isDark ? 'sun' : 'moon'" :size="16" />
                </button>
            </div>
        </div>
    </header>

    <main id="app" :key="mainKey" tabindex="-1">
        <slot />
        <p v-if="isAuthView" class="ed-local-note">Akun dan progres baru tersimpan pribadi di server. Akun demo lama tidak otomatis menjadi akun terdaftar.</p>
    </main>

    <footer class="wrap foot">
        <p>ArahDiri adalah alat bantu awal, bukan pengganti guru BK, konselor, atau orang tua. Daftar kampus menggunakan Katalog Nasional PT &amp; Prodi, akreditasi dari direktori BAN-PT, dan profesi dari KBJI 2026. Syarat, kuota, dan jadwal berubah setiap tahun, jadi selalu cek situs resmi sebelum mendaftar. <Go href="/referensi" class="v2-link">Panduan &amp; referensi</Go> · <Go href="/privasi" class="v2-link">Kebijakan privasi</Go></p>
    </footer>
    <div class="toast" id="toast" role="status" aria-live="polite"></div>

    <nav class="bottom-nav-bar" id="bottom-nav-bar" aria-label="Navigasi Bawah" :style="{ display: hideBottomNav ? 'none' : 'block' }">
        <div class="bottom-nav-inner">
            <Go :href="home" class="bottom-nav-item" :class="{ active: current('mulai') }" title="Kembali ke Halaman Utama (Beranda)" id="bnav-home">
                <div class="bnav-icon-wrap"><Icon name="home" /></div>
                <span class="bnav-label">Beranda</span>
            </Go>
            <Go :href="VIEW_URL.jurusan" class="bottom-nav-item" :class="{ active: exploreViews.includes(view) }" title="Akses Fitur Pencarian & Penjelajahan" id="bnav-explore">
                <div class="bnav-icon-wrap"><Icon name="compass" /></div>
                <span class="bnav-label">Eksplor</span>
            </Go>
            <Go :href="VIEW_URL.favorit" class="bottom-nav-item" :class="{ active: current('favorit') }" title="Daftar Destinasi & Item yang Disimpan" id="bnav-fav">
                <div class="bnav-icon-wrap">
                    <Icon name="heart" />
                    <span v-if="user?.favorites_count" class="bnav-badge" id="bnav-fav-badge">{{ user.favorites_count }}</span>
                </div>
                <span class="bnav-label">Favorit</span>
            </Go>
            <Go :href="user ? VIEW_URL.profil : VIEW_URL.login" class="bottom-nav-item" :class="{ active: ['profil', 'riwayat', 'langkah', 'hasil'].includes(view) }" title="Halaman Akun & Profil Aktif" id="bnav-account">
                <div class="bnav-icon-wrap"><Icon name="account" /></div>
                <span class="bnav-label">Akun</span>
            </Go>
        </div>
    </nav>
</template>
