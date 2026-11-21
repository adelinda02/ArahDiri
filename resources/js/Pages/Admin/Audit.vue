<script setup>
import { reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import Topline from '../../Components/Admin/Topline.vue';
import Pager from '../../Components/Admin/Pager.vue';

defineOptions({ layout: AdminLayout });
const props = defineProps({ items: Object, actors: Array, actions: Array, filters: Object });
const f = reactive({ pelaku: props.filters.pelaku ?? '', aksi: props.filters.aksi ?? '', dari: props.filters.dari ?? '', sampai: props.filters.sampai ?? '' });
const apply = () => router.get('/admin/audit', Object.fromEntries(Object.entries(f).filter(([, v]) => v)), { preserveState: true });
const reset = () => { Object.keys(f).forEach((k) => (f[k] = '')); apply(); };
// Aman untuk format tanggal apa pun: tanggal yang tidak terbaca ditampilkan apa adanya, bukan membuat halaman kosong.
function when(s) {
    if (!s) return '-';
    const d = new Date(String(s).includes('T') ? String(s) : String(s).replace(' ', 'T') + 'Z');
    return Number.isNaN(d.getTime()) ? String(s) : d.toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
}
// Rincian (meta) dibuat mudah dibaca: {"dari":"smp","ke":"sma_smk"} → dari: SMP · ke: SMA/SMK
const NICE = { smp: 'SMP', sma_smk: 'SMA/SMK', mhs: 'Mahasiswa' };
function detail(m) {
    if (!m) return '';
    let o = m;
    if (typeof m === 'string') { try { o = JSON.parse(m); } catch (e) { return m; } }
    if (!o || typeof o !== 'object') return String(m);
    return Object.entries(o).map(([k, v]) => `${k.replace(/_/g, ' ')}: ${typeof v === 'object' ? JSON.stringify(v) : (NICE[v] ?? v)}`).join(' · ');
}
const LABEL = {
    admin_created: 'Admin dibuat (server)', admin_promoted: 'Akses panel admin diberikan', admin_nonaktif: 'Admin dinonaktifkan', admin_aktif: 'Admin diaktifkan', admin_cabut: 'Akses admin dicabut',
    admin_jadi_admin: 'Diubah jadi admin', admin_jadi_editor: 'Diubah jadi editor konten',
    results_exported: 'Ekspor data hasil', banpt_alias_added: 'Pasangan akreditasi ditambah', banpt_alias_removed: 'Pasangan akreditasi dihapus', banpt_resync: 'Sinkron ulang BAN-PT',
    catalog_toggled: 'Katalog: tampil/sembunyi', catalog_reimported: 'Katalog diimpor ulang', catalog_edited: 'Katalog disunting', catalog_released: 'Kunci suntingan dilepas',
    content_created: 'Konten ditambah', content_updated: 'Konten diubah', content_deleted: 'Konten dihapus',
    rule_changed: 'Aturan rekomendasi diubah', report_updated: 'Laporan siswa ditinjau',
    user_activated: 'Pengguna diaktifkan', user_deactivated: 'Pengguna dinonaktifkan', user_temp_password: 'Kata sandi sementara dibuat', user_level_changed: 'Jenjang pengguna diubah', password_reset: 'Kata sandi direset lewat email', password_reset_requested: 'Kode reset kata sandi diminta', current_program_declared: 'Prodi saat ini dipilih',
    password_changed: 'Kata sandi diganti', account_deleted: 'Akun dihapus', umb_program_declared: 'Prodi UMB dipilih',
};
</script>

<template>
    <Head title="Admin · Catatan aktivitas" />
    <Topline title="Catatan aktivitas" desc="Log audit perubahan katalog dan konten, aturan, akun, ekspor data, dan keamanan akun." />
    <div class="notice" style="margin-bottom:16px">Siapa mengubah apa dan kapan, untuk menelusuri perubahan dan menjaga keamanan akun. Yang dicatat: perubahan katalog, konten, aturan, akreditasi, ekspor data, pengelolaan akun admin dan pengguna, serta ganti/reset kata sandi dan hapus akun. Login, tes, dan perubahan profil siswa tidak ditampilkan demi privasi.</div>
    <form class="toolbar" @submit.prevent="apply">
        <select v-model="f.pelaku" aria-label="Pelaku"><option value="">Semua admin</option><option v-for="a in actors" :key="a.id" :value="a.id">{{ a.username }}</option></select>
        <select v-model="f.aksi" aria-label="Jenis aksi"><option value="">Semua aksi</option><option v-for="a in actions" :key="a" :value="a">{{ LABEL[a] || a }}</option></select>
        <label>Dari <input v-model="f.dari" type="date"></label>
        <label>Sampai <input v-model="f.sampai" type="date"></label>
        <button type="submit">Saring</button>
        <button type="button" class="quiet" @click="reset">Hapus saringan</button>
    </form>
    <div class="table-wrap"><table>
        <thead><tr><th>Waktu</th><th>Pelaku</th><th>Aksi</th><th>Objek</th></tr></thead>
        <tbody><tr v-for="l in items.data" :key="l.id"><td>{{ when(l.at) }}</td><td>{{ l.actor || 'sistem' }}</td><td><strong>{{ LABEL[l.action] || l.action }}</strong><small v-if="l.meta" style="word-break:break-word">{{ detail(l.meta) }}</small></td><td>{{ l.subject }}</td></tr></tbody>
    </table><p v-if="!items.data.length" style="padding:20px">{{ Object.values(filters).some((v) => v) ? 'Tidak ada catatan yang cocok dengan saringan ini.' : 'Belum ada aktivitas yang tercatat. Catatan muncul setelah admin mengubah sesuatu (mis. katalog, aturan, akun) atau ada kejadian keamanan akun.' }}</p></div>
    <Pager :p="items" label="catatan" />
</template>
