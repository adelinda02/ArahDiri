<script setup>
// Profil v14 (vProfil): kartu profil, kartu hasil RIASEC, Pusat Pengaturan Akun + modal-modalnya.
import { computed, ref, onMounted } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import Go from '../Components/Go.vue';
import { AVATARS } from '../lib/avatars';
import { ADMIN_EMAIL } from '../lib/contact';

const props = defineProps({ account: Object, result: Object, levels: Object, avatars: Array });
const NAMA = { R: 'Realistis', I: 'Investigatif', A: 'Artistik', S: 'Sosial', E: 'Enterprising', C: 'Konvensional' };
const isAdmin = computed(() => usePage().props.auth.user?.is_admin);
const top3 = computed(() => (props.result?.code ?? '').split(''));
const modal = ref(null); // settings | edit | privacy | about | terms | logout | delete
const isDark = ref(document.documentElement.getAttribute('data-theme') === 'dark');

function open(m) { modal.value = m; document.body.style.overflow = 'hidden'; }
function close() { modal.value = null; document.body.style.overflow = ''; }
// Masuk dengan kata sandi sementara dari admin → langsung buka Pengaturan Akun untuk menggantinya.
onMounted(() => { if (usePage().props.auth.user?.must_change_password) open('settings'); });
function setTheme(next) {
    document.documentElement.setAttribute('data-theme', next);
    try { localStorage.setItem('arahdiri-theme', next); } catch (e) { /* mode privat */ }
    isDark.value = next === 'dark';
}
// Pengaturan: bagian mana yang sedang dibuka (null | 'pw' | 'others'). Wajib ganti sandi → langsung terbuka.
const openSec = ref(usePage().props.auth.user?.must_change_password ? 'pw' : null);
function toggleSec(s) { openSec.value = openSec.value === s ? null : s; }

const edit = useForm({
    name: props.account.name, email: props.account.email, phone: props.account.phone, level: props.account.level,
    school: props.account.school ?? '', bio: props.account.bio ?? '', avatar: props.account.avatar,
});
const saveProfile = () => edit.put('/profil', { preserveScroll: true, onSuccess: close });

const pw = useForm({ current_password: '', password: '', password_confirmation: '' });
const savePassword = () => pw.put('/profil/kata-sandi', { preserveScroll: true, onSuccess: () => { pw.reset(); openSec.value = null; close(); } });
const strength = computed(() => {
    const v = pw.password;
    let s = 0;
    if (v.length >= 10) s++;
    if (/[A-Z]/.test(v) && /[a-z]/.test(v)) s++;
    if (/\d/.test(v)) s++;
    if (/[^A-Za-z0-9]/.test(v)) s++;
    return v ? s : 0;
});
const show = ref({});

const others = useForm({ password: '' });
const logoutOthers = () => others.post('/profil/keluar-semua', { preserveScroll: true, onSuccess: () => { others.reset(); openSec.value = null; close(); } });
const del = useForm({ password: '' });
const deleteAccount = () => del.delete('/profil');
const logout = () => router.post('/keluar');

const pen = '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>';
const eye = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
const ICONS = {
    settings: '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
    edit: '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/><path d="M17 11l2 2 4-4"/>',
    privacy: '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><circle cx="12" cy="11" r="2"/>',
    about: '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
    terms: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>',
    logout: '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
    history: '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l3 2"/>',
};
const MENU = [
    ['settings', 'Menu Pengaturan', 'Mode tampilan, ganti kata sandi, dan keluar dari semua perangkat.'],
    ['privacy', 'Menu Kebijakan Privasi', 'Data apa yang disimpan ArahDiri dan untuk apa.'],
    ['about', 'Menu Tentang Kami', 'Memuat informasi serta latar belakang dari aplikasi ArahDiri.'],
    ['terms', 'Menu Ketentuan Layanan', 'Berisi aturan dan syarat penggunaan aplikasi.'],
    ['logout', 'Menu Keluar', 'Berfungsi untuk melakukan logout dari akun ArahDiri.'],
];
</script>

<template>
    <Head title="Profil" />
    <div class="wrap profile-page-wrap">
        <div class="profile-header-card">
            <div class="profile-header-main">
                <div class="profile-avatar-container">
                    <div class="profile-avatar-ring" v-html="AVATARS[account.avatar] || AVATARS.avatar1"></div>
                    <button type="button" class="profile-avatar-badge" title="Ubah karakter profil" @click="open('edit')"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" v-html="pen"></svg></button>
                </div>
                <div class="profile-info-content">
                    <div class="profile-title-row">
                        <h2 class="profile-user-name">{{ account.name }}</h2>
                        <span class="profile-status-pill" title="Akun terdaftar"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12" /></svg>Akun Terdaftar</span>
                    </div>
                    <p class="profile-user-email"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" /><polyline points="22,6 12,13 2,6" /></svg><span>{{ account.email }}</span></p>
                    <div class="profile-meta-tags">
                        <span class="profile-meta-chip">Jenjang: <b>{{ account.level_label }}</b></span>
                        <span v-if="account.current_program || account.school" class="profile-meta-chip"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z" /><path d="M6 12v5c3 3 9 3 12 0v-5" /></svg>{{ account.current_program || account.school }}</span>
                    </div>
                    <p class="profile-bio-text">"{{ account.bio || 'Masa Depanmu, Pilihanmu, Arahmu!' }}"</p>
                </div>
            </div>
            <div class="profile-header-actions">
                <button type="button" class="btn profile-edit-btn" @click="open('edit')"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" v-html="pen"></svg><span>Edit Profil</span></button>
            </div>
        </div>

        <div class="profile-riasec-card">
            <template v-if="result">
                <div class="profile-riasec-header">
                    <div class="profile-riasec-badge">
                        <span class="tag" style="background:var(--accent-soft);color:var(--accent);font-weight:700"> Hasil Asesmen Holland</span>
                        <h3>Kode Minat: <span class="profile-riasec-code">{{ result.code }}</span></h3>
                    </div>
                    <div class="profile-riasec-top3"><span v-for="t in top3" :key="t" class="riasec-top-chip"><b>{{ t }}</b> {{ NAMA[t] }}</span></div>
                </div>
                <p class="profile-riasec-desc">Kecenderungan minat dominanmu adalah <b>{{ top3.map((t) => NAMA[t]).join(', ') }}</b> ({{ result.code }}). Rekomendasi sesuai jenjangmu sudah tersimpan.</p>
                <div class="profile-riasec-actions">
                    <Go href="/hasil" class="btn btn-primary" style="font-size:.88rem;padding:9px 18px"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="18" y1="20" x2="18" y2="10" /><line x1="12" y1="20" x2="12" y2="4" /><line x1="6" y1="20" x2="6" y2="14" /></svg><span>Buka Detail Lengkap Rekomendasi &amp; Radar Skor →</span></Go>
                    <Go href="/tes?ulang=1" class="btn btn-outline" style="font-size:.88rem;padding:9px 16px">Ulangi Tes</Go>
                </div>
            </template>
            <template v-else>
                <p class="profile-riasec-desc">Kamu belum menyelesaikan tes minat.</p>
                <div class="profile-riasec-actions"><Go href="/tes" class="btn btn-primary">Mulai Tes RIASEC</Go></div>
            </template>
        </div>

        <section class="profile-options-section">
            <div class="profile-section-heading">
                <h3 class="profile-section-title">Pusat Pengaturan Akun</h3>
                <p class="profile-section-subtitle">Sesuaikan preferensi aplikasi, lihat riwayat tes, tinjau kebijakan privasi, informasi kami, serta ketentuan layanan ArahDiri.</p>
            </div>
            <div class="profile-menu-grid">
                <a v-if="isAdmin" href="/admin" class="profile-menu-item" style="text-decoration:none;color:inherit">
                    <div class="profile-menu-icon-box ic-settings"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="3" width="7" height="9" rx="1" /><rect x="14" y="3" width="7" height="5" rx="1" /><rect x="14" y="12" width="7" height="9" rx="1" /><rect x="3" y="16" width="7" height="5" rx="1" /></svg></div>
                    <div class="profile-menu-text"><h4 class="profile-menu-name">Panel Admin</h4><p class="profile-menu-desc">Ringkasan, data hasil, akreditasi, katalog, dan konten.</p></div>
                    <div class="profile-menu-arrow"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6" /></svg></div>
                </a>
                <Link href="/riwayat" class="profile-menu-item" style="text-decoration:none;color:inherit">
                    <div class="profile-menu-icon-box ic-history"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" v-html="ICONS.history"></svg></div>
                    <div class="profile-menu-text"><h4 class="profile-menu-name">Menu Riwayat Tes</h4><p class="profile-menu-desc">Lihat dan buka kembali hasil tes sebelumnya.</p></div>
                    <div class="profile-menu-arrow"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6" /></svg></div>
                </Link>
                <article v-for="m in MENU" :key="m[0]" class="profile-menu-item" :class="{ 'item-logout': m[0] === 'logout' }" role="button" tabindex="0" @click="open(m[0])" @keydown.enter="open(m[0])">
                    <div class="profile-menu-icon-box" :class="'ic-' + m[0]"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" v-html="ICONS[m[0]]"></svg></div>
                    <div class="profile-menu-text"><h4 class="profile-menu-name">{{ m[1] }}</h4><p class="profile-menu-desc">{{ m[2] }}</p></div>
                    <div class="profile-menu-arrow"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6" /></svg></div>
                </article>
            </div>
        </section>
    </div>

    <Teleport to="body">
        <div v-if="modal" class="career-modal-backdrop open" id="profile-modal" role="dialog" aria-modal="true" aria-labelledby="profile-modal-title" style="display:flex" @click.self="close" @keydown.esc="close">
            <div class="career-modal-dialog profile-modal-dialog">
                <button type="button" class="career-modal-close" aria-label="Tutup jendela" @click="close">&#10005;</button>
                <div id="profile-modal-content">
                    <div class="pmodal-header">
                        <div class="pmodal-icon-badge" :class="'ic-' + (modal === 'delete' ? 'logout' : modal)"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" v-html="ICONS[modal === 'delete' ? 'logout' : modal]"></svg></div>
                        <div>
                            <h3 id="profile-modal-title" class="pmodal-title">{{ { settings: 'Pengaturan Akun', edit: 'Edit Profil Pengguna', privacy: 'Kebijakan Privasi', about: 'Tentang Kami', terms: 'Ketentuan Layanan', logout: 'Konfirmasi Keluar', delete: 'Hapus Akun' }[modal] }}</h3>
                            <p class="pmodal-sub">{{ { settings: 'Tampilan, kata sandi, dan keamanan akun.', edit: 'Perbarui informasi profil dan karakter avatar.', privacy: 'Data apa yang kami simpan dan untuk apa.', about: 'Informasi serta latar belakang aplikasi ArahDiri.', terms: 'Aturan dan syarat penggunaan aplikasi ArahDiri.', logout: 'Keluar dari akun ArahDiri.', delete: 'Menghapus akun beserta seluruh hasil tes.' }[modal] }}</p>
                        </div>
                    </div>

                    <!-- Pengaturan -->
                    <template v-if="modal === 'settings'">
                        <div class="pmodal-body pset">
                            <!-- Tampilan -->
                            <section class="pset-group">
                                <h4 class="pset-label">Tampilan</h4>
                                <div class="pset-card">
                                    <div class="pset-row">
                                        <div class="pset-info"><span class="pset-title">Mode tampilan</span><span class="pset-desc">Pilih tema yang paling nyaman di mata.</span></div>
                                        <div class="pset-seg" role="radiogroup" aria-label="Mode tampilan">
                                            <button type="button" role="radio" :aria-checked="!isDark" :class="{ on: !isDark }" @click="setTheme('light')">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="4" /><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" /></svg>Terang
                                            </button>
                                            <button type="button" role="radio" :aria-checked="isDark" :class="{ on: isDark }" @click="setTheme('dark')">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" /></svg>Gelap
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <!-- Keamanan -->
                            <section class="pset-group">
                                <h4 class="pset-label">Keamanan</h4>
                                <div class="pset-card">
                                    <div class="pset-row">
                                        <span class="pset-ic"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2" /><path d="M7 11V7a5 5 0 0 1 10 0v4" /></svg></span>
                                        <div class="pset-info"><span class="pset-title">Kata sandi</span><span class="pset-desc pset-dots" aria-hidden="true">• • • • • • • •</span></div>
                                        <button type="button" class="pset-btn" :aria-expanded="openSec === 'pw'" aria-controls="pset-pw" @click="toggleSec('pw')">{{ openSec === 'pw' ? 'Batal' : 'Ubah' }}</button>
                                    </div>
                                    <form v-if="openSec === 'pw'" id="pset-pw" class="pset-panel" @submit.prevent="savePassword">
                                        <p class="pform-hint">Setelah diganti, perangkat lain yang sedang masuk otomatis keluar.</p>
                                        <div v-for="f in [['current_password', 'Kata Sandi Saat Ini', 'current-password'], ['password', 'Kata Sandi Baru', 'new-password'], ['password_confirmation', 'Konfirmasi Kata Sandi Baru', 'new-password']]" :key="f[0]" class="pform-group">
                                            <label class="pform-label" :for="'pw-' + f[0]">{{ f[1] }}</label>
                                            <div class="password-input-wrap">
                                                <input :id="'pw-' + f[0]" v-model="pw[f[0]]" :type="show[f[0]] ? 'text' : 'password'" class="pform-input" :autocomplete="f[2]" :placeholder="f[0] === 'password' ? 'Minimal 10 karakter baru' : ''">
                                                <button type="button" class="pwd-eye-btn" aria-label="Lihat kata sandi" @click="show[f[0]] = !show[f[0]]"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" v-html="eye"></svg></button>
                                            </div>
                                            <template v-if="f[0] === 'password'">
                                                <div class="pwd-strength-bar"><span class="pwd-strength-fill" :style="{ width: strength * 25 + '%' }"></span></div>
                                                <div class="pwd-strength-hint">Kekuatan: {{ ['Masukkan minimal 10 karakter', 'Lemah', 'Cukup', 'Kuat', 'Sangat kuat'][strength] }}</div>
                                            </template>
                                            <span v-if="pw.errors[f[0]]" class="field-error">{{ pw.errors[f[0]] }}</span>
                                        </div>
                                        <div class="pset-actions"><button type="submit" class="btn btn-primary pset-submit" :disabled="pw.processing">Perbarui Kata Sandi</button></div>
                                    </form>

                                    <div class="pset-row">
                                        <span class="pset-ic"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="2" y="3" width="20" height="14" rx="2" /><path d="M8 21h8M12 17v4" /></svg></span>
                                        <div class="pset-info"><span class="pset-title">Keluar dari perangkat lain</span><span class="pset-desc">Akhiri sesi di HP atau komputer lain, perangkat ini tetap masuk.</span></div>
                                        <button type="button" class="pset-btn" :aria-expanded="openSec === 'others'" aria-controls="pset-others" @click="toggleSec('others')">{{ openSec === 'others' ? 'Batal' : 'Keluarkan' }}</button>
                                    </div>
                                    <form v-if="openSec === 'others'" id="pset-others" class="pset-panel" @submit.prevent="logoutOthers">
                                        <div class="pform-group"><label class="pform-label" for="pw-others">Masukkan kata sandi saat ini untuk konfirmasi</label><input id="pw-others" v-model="others.password" type="password" class="pform-input" autocomplete="current-password"><span v-if="others.errors.password" class="field-error">{{ others.errors.password }}</span></div>
                                        <div class="pset-actions"><button type="submit" class="btn btn-primary pset-submit" :disabled="others.processing">Keluarkan Perangkat Lain</button></div>
                                    </form>
                                </div>
                            </section>

                            <!-- Zona berbahaya -->
                            <section class="pset-group">
                                <h4 class="pset-label pset-label--danger">Zona berbahaya</h4>
                                <div class="pset-card pset-danger">
                                    <div class="pset-row">
                                        <span class="pset-ic"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" /></svg></span>
                                        <div class="pset-info"><span class="pset-title">Hapus akun</span><span class="pset-desc">Akun dan seluruh hasil tes dihapus permanen.</span></div>
                                        <button type="button" class="pset-btn pset-btn--danger" @click="modal = 'delete'">Hapus akun…</button>
                                    </div>
                                </div>
                            </section>
                        </div>
                        <div class="pmodal-footer"><button type="button" class="btn btn-outline" @click="close">Tutup</button></div>
                    </template>

                    <!-- Edit profil -->
                    <template v-else-if="modal === 'edit'">
                        <form class="pmodal-body" @submit.prevent="saveProfile">
                            <div class="prof-preview">
                                <div class="prof-preview-avatar" v-html="AVATARS[edit.avatar]"></div>
                                <div style="flex:1;min-width:0">
                                    <div style="font-weight:800;font-size:1.05rem;color:var(--ink)">{{ edit.name || 'Nama Pengguna' }}</div>
                                    <div style="font-size:0.82rem;color:var(--muted);margin-top:2px">{{ edit.school || 'Sekolah / Kampus' }}</div>
                                    <div style="font-size:0.75rem;color:var(--accent);font-weight:700;margin-top:4px">● Pratinjau Tampilan Profil</div>
                                </div>
                            </div>
                            <div class="pform-group">
                                <label class="pform-label">Pilih Karakter Avatar</label>
                                <p class="pform-hint">Demi privasi, ArahDiri tidak menyimpan foto pribadi.</p>
                                <div class="avatar-pick-grid">
                                    <button v-for="a in avatars" :key="a" type="button" class="avatar-pick-item" :class="{ active: edit.avatar === a }" title="Pilih avatar ini" @click="edit.avatar = a" v-html="AVATARS[a]"></button>
                                </div>
                            </div>
                            <div v-for="f in [['name', 'Nama Tampilan', 'text', 'Nama Lengkap Anda'], ['email', 'Alamat Email', 'email', 'nama@email.com'], ['phone', 'Nomor HP / WhatsApp', 'tel', '08xxxxxxxxxx'], ['school', 'Asal Sekolah / Kampus', 'text', 'SMP / SMA / SMK / Universitas…'], ['bio', 'Bio & Kalimat Inspirasi', 'text', 'Motto atau cita-cita Anda…']]" :key="f[0]" class="pform-group">
                                <label class="pform-label" :for="'prof-' + f[0]">{{ f[1] }}</label>
                                <input :id="'prof-' + f[0]" v-model="edit[f[0]]" :type="f[2]" class="pform-input" :placeholder="f[3]">
                                <span v-if="edit.errors[f[0]]" class="field-error">{{ edit.errors[f[0]] }}</span>
                            </div>
                            <div class="pform-group">
                                <label class="pform-label" for="prof-level">Jenjang Pendidikan</label>
                                <select id="prof-level" v-model="edit.level" class="pform-input" :disabled="!isAdmin"><option v-for="(l, k) in levels" :key="k" :value="k">{{ l }}</option></select>
                                <p v-if="isAdmin && edit.level !== account.level" class="pform-hint">Rekomendasi mengikuti jenjang saat tes. Kerjakan tes ulang setelah mengganti jenjang.</p>
                            </div>
                            <div class="pmodal-footer" style="padding:0;border:0">
                                <button type="button" class="btn btn-outline" @click="close">Batal</button>
                                <button type="submit" class="btn btn-primary" :disabled="edit.processing">Simpan Perubahan</button>
                            </div>
                        </form>
                    </template>

                    <!-- Kebijakan privasi -->
                    <template v-else-if="modal === 'privacy'">
                        <div class="pmodal-body pmodal-text">
                            <div class="c-box"><p><b>Ringkasnya:</b> ArahDiri hanya menyimpan data yang dibutuhkan untuk memberi rekomendasi, dan tidak menjualnya kepada siapa pun.</p></div>
                            <h4>1. Data yang disimpan</h4>
                            <p>Nama, nama pengguna, email, nomor HP, jenjang, asal sekolah (bila diisi), jawaban tes, hasil tes, rekomendasi, favorit, dan target pengembangan. Kami tidak meminta tanggal lahir, alamat, foto, atau dokumen identitas.</p>
                            <h4>2. Untuk apa</h4>
                            <p>Menghitung profil minat, menyusun rekomendasi sekolah, prodi, kampus, dan karier, serta menyimpan riwayatmu. Mahasiswa mengisi kampus dan prodinya sendiri tanpa verifikasi.</p>
                            <h4>3. Siapa yang bisa melihat</h4>
                            <p>Kamu sendiri. Admin hanya melihat angka gabungan (misalnya jumlah tes per jenjang). Data per orang hanya bisa dibuka pemilik aplikasi dan setiap aksesnya tercatat. Ekspor untuk penelitian memakai ID acak tanpa nama, email, atau nomor HP.</p>
                            <h4>4. Kendalimu</h4>
                            <p>Kamu bisa mengubah profil, menghapus riwayat tes, atau menghapus akun beserta seluruh hasil tes kapan saja lewat menu Pengaturan.</p>
                            <h4>5. Untuk orang tua</h4>
                            <p>Banyak pengguna ArahDiri masih di bawah umur. Orang tua atau wali dapat mendampingi anak saat membuat akun dan membaca hasil bersama guru BK.</p>
                            <p><Go href="/privasi" class="v2-link">Baca kebijakan privasi lengkap ↗</Go></p>
                        </div>
                        <div class="pmodal-footer"><button type="button" class="btn btn-primary" @click="close">Saya Mengerti</button></div>
                    </template>

                    <!-- Tentang -->
                    <template v-else-if="modal === 'about'">
                        <div class="pmodal-body pmodal-text">
                            <div style="text-align:center;padding:16px 12px;background:var(--surface-2);border-radius:14px;border:1px solid var(--line)">
                                <h3 style="font-size:1.45rem;font-weight:800;color:var(--ink);margin:0">ArahDiri</h3>
                                <p style="font-size:1.05rem;font-weight:700;color:var(--accent);margin:4px 0 0">"Masa Depanmu, Pilihanmu, <em>Arahmu!</em>"</p>
                                <span class="tag on" style="margin-top:8px">Versi 2</span>
                            </div>
                            <h4>Latar belakang</h4>
                            <p>ArahDiri membantu siswa SMP, SMA/SMK, dan mahasiswa mengenali minatnya dan menghubungkannya dengan pilihan sekolah, prodi, kampus, dan karier.</p>
                            <h4>Metode</h4>
                            <p>Tes 50 pernyataan berbasis model minat Holland (RIASEC): Realistic, Investigative, Artistic, Social, Enterprising, dan Conventional. Profil minat pekerjaan dari O*NET 31.0 dipadankan dengan jabatan KBJI 2026.</p>
                            <h4>Data</h4>
                            <p>Katalog perguruan tinggi dan prodi PDDikti, akreditasi Direktori BAN-PT, dan Spektrum Keahlian SMK. <Go href="/referensi" class="v2-link">Lihat sumber lengkap ↗</Go></p>
                            <h4>Kontak admin</h4>
                            <p>Untuk mengubah jenjang pendidikan, jika tidak bisa mereset kata sandi lewat email, atau melaporkan masalah, hubungi admin ArahDiri lewat email. Sebutkan nama pengguna, email, atau nomor HP akunmu.</p>
                            <a class="about-contact" :href="'mailto:' + ADMIN_EMAIL + '?subject=' + encodeURIComponent('Bantuan akun ArahDiri')">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" /><path d="m3 7 9 6 9-6" /></svg>
                                <span>{{ ADMIN_EMAIL }}</span>
                            </a>
                        </div>
                        <div class="pmodal-footer"><button type="button" class="btn btn-primary" @click="close">Tutup</button></div>
                    </template>

                    <!-- Ketentuan -->
                    <template v-else-if="modal === 'terms'">
                        <div class="pmodal-body pmodal-text">
                            <div class="c-box"><p>Dengan memakai ArahDiri, kamu menyetujui ketentuan berikut.</p></div>
                            <h4>1. Hasil tes adalah alat bantu</h4>
                            <p>Hasil RIASEC menggambarkan kecenderungan minat, bukan vonis atau ukuran kemampuan. Diskusikan dengan guru BK, konselor, atau orang tua sebelum memutuskan.</p>
                            <h4>2. Data kampus dan akreditasi</h4>
                            <p>Kuota, jadwal, biaya, dan syarat masuk berubah setiap tahun. Akreditasi mengikuti Direktori BAN-PT pada tanggal yang tertulis di hasil. Selalu cek situs resmi kampus.</p>
                            <h4>3. Penggunaan wajar</h4>
                            <p>Satu akun untuk satu orang. Jangan memakai akun orang lain atau mengganggu layanan.</p>
                        </div>
                        <div class="pmodal-footer"><button type="button" class="btn btn-primary" @click="close">Saya Setuju</button></div>
                    </template>

                    <!-- Keluar -->
                    <template v-else-if="modal === 'logout'">
                        <div class="pmodal-body" style="font-size:.95rem;line-height:1.6;color:var(--ink)">
                            <p style="margin:0">Apakah kamu yakin ingin keluar dari akun <b>{{ account.name }}</b> (<code>{{ account.email }}</code>)?</p>
                            <div class="c-box" style="margin-top:12px"><p style="color:var(--ink);font-size:.88rem">Hasil tes dan pilihan tersimpanmu tetap aman di akun dan bisa dibuka lagi saat masuk kembali.</p></div>
                        </div>
                        <div class="pmodal-footer">
                            <button type="button" class="btn btn-outline" @click="close">Batal</button>
                            <button type="button" class="btn" style="background:#1D70B8;color:#ffffff;border-color:#1D70B8" @click="logout">Ya, Keluar dari Akun</button>
                        </div>
                    </template>

                    <!-- Hapus akun -->
                    <template v-else-if="modal === 'delete'">
                        <form class="pmodal-body" @submit.prevent="deleteAccount">
                            <div class="c-box" style="background:rgba(239,68,68,0.06);border-color:rgba(239,68,68,0.25)"><p style="font-size:.9rem">Akun <b>{{ account.username }}</b>, seluruh hasil tes, favorit, dan target pengembanganmu akan dihapus permanen. Tindakan ini tidak bisa dibatalkan.</p></div>
                            <div class="pform-group"><label class="pform-label" for="del-pw">Masukkan kata sandi untuk konfirmasi</label><input id="del-pw" v-model="del.password" type="password" class="pform-input" autocomplete="current-password"><span v-if="del.errors.password" class="field-error">{{ del.errors.password }}</span></div>
                            <div class="pmodal-footer" style="padding:0;border:0">
                                <button type="button" class="btn btn-outline" @click="modal = 'settings'">Batal</button>
                                <button type="submit" class="btn" style="background:#b3261e;color:#fff;border-color:#b3261e" :disabled="del.processing">Hapus Akun Permanen</button>
                            </div>
                        </form>
                    </template>
                </div>
            </div>
        </div>
    </Teleport>
</template>
