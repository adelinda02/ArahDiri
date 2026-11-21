<script setup>
// Pengguna (siswa/mahasiswa): data minimal, nonaktifkan, dan kata sandi sementara.
import { reactive, computed, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import Topline from '../../Components/Admin/Topline.vue';
import Pager from '../../Components/Admin/Pager.vue';

defineOptions({ layout: AdminLayout });
const props = defineProps({ items: Object, filters: Object, levels: Object, totals: Object });
const page = usePage();
const f = reactive({ q: props.filters.q ?? '', status: props.filters.status ?? '', jenjang: props.filters.jenjang ?? '' });
const apply = () => router.get('/admin/pengguna', Object.fromEntries(Object.entries(f).filter(([, v]) => v)), { preserveState: true });
const toggle = (u) => confirm(u.active ? `Nonaktifkan ${u.username}? Ia langsung keluar dari semua perangkat dan tidak bisa masuk.` : `Aktifkan lagi ${u.username}?`) && router.post(`/admin/pengguna/${u.id}/status`, {}, { preserveScroll: true });
const temp = (u) => confirm(`Buat kata sandi sementara untuk ${u.username}? Kata sandi lamanya langsung tidak berlaku. Pastikan ini permintaan pemilik akun.`) && router.post(`/admin/pengguna/${u.id}/sandi-sementara`, {}, { preserveScroll: true });
// Ubah jenjang (atas permintaan pemilik akun lewat email admin).
const lvEdit = ref(null);
const lvDraft = ref('');
const lvOpen = (u) => { lvEdit.value = u.id; lvDraft.value = u.level_key; };
const lvSave = (u) => {
    if (lvDraft.value === u.level_key) { lvEdit.value = null; return; }
    if (!confirm(`Ubah jenjang ${u.username} dari ${u.level} ke ${props.levels[lvDraft.value]}? Pastikan ini permintaan pemilik akun. Hasil lama tetap tersimpan; ia perlu tes ulang untuk rekomendasi jenjang baru.`)) return;
    router.post(`/admin/pengguna/${u.id}/jenjang`, { level: lvDraft.value }, { preserveScroll: true, onSuccess: () => (lvEdit.value = null) });
};
const issued = computed(() => page.props.flash?.temp_password);
const day = (s) => (s ? new Date(String(s).replace(' ', 'T') + (String(s).length > 10 && !String(s).includes('Z') ? 'Z' : '')).toLocaleDateString('id-ID', { dateStyle: 'medium' }) : '-');
async function copy(t) { try { await navigator.clipboard.writeText(t); } catch (e) { /* abaikan */ } }
</script>

<template>
    <Head title="Admin · Pengguna" />
    <Topline title="Pengguna" :desc="`${totals.all.toLocaleString('id-ID')} akun siswa/mahasiswa, ${totals.inactive} nonaktif. Hanya data minimal yang ditampilkan; jawaban dan hasil per orang tidak dibuka di sini.`" />
    <div class="notice" style="margin-bottom:16px">Bantuan yang bisa diminta pengguna lewat email admin: <b>ubah jenjang</b> (pengguna tidak bisa mengubahnya sendiri), <b>sandi sementara</b> bila ia tidak bisa mereset kata sandi lewat email, dan <b>nonaktifkan</b> akun bila ada penyalahgunaan. Cari akunnya dengan email lengkap atau nomor HP yang ia sebutkan.</div>

    <section v-if="issued" class="panel pg-issued" role="status">
        <h2 style="margin-top:0">Kata sandi sementara untuk {{ issued.username }}</h2>
        <p><code class="pg-pass">{{ issued.password }}</code> <button type="button" @click="copy(issued.password)">Salin</button></p>
        <p>Berikan langsung ke pemilik akun (bukan lewat grup). Kata sandi ini hanya ditampilkan sekali; saat masuk, ia wajib menggantinya.</p>
    </section>

    <form class="toolbar" @submit.prevent="apply">
        <input v-model="f.q" placeholder="Cari nama, nama pengguna, email lengkap, atau nomor HP…" aria-label="Cari pengguna">
        <select v-model="f.jenjang" aria-label="Jenjang"><option value="">Semua jenjang</option><option v-for="(l, k) in levels" :key="k" :value="k">{{ l }}</option></select>
        <select v-model="f.status" aria-label="Status"><option value="">Semua status</option><option value="aktif">Aktif</option><option value="nonaktif">Nonaktif</option></select>
        <button type="submit">Cari</button>
    </form>
    <div class="table-wrap"><table>
        <thead><tr><th>Pengguna</th><th>Jenjang</th><th>Terdaftar</th><th>Tes selesai</th><th>Status</th><th>Tindakan</th></tr></thead>
        <tbody><tr v-for="u in items.data" :key="u.id">
            <td><strong>{{ u.name }}</strong><small>{{ u.username }} · {{ u.email }}</small></td>
            <td class="pg-level">
                <template v-if="lvEdit === u.id">
                    <select v-model="lvDraft" :aria-label="`Jenjang baru untuk ${u.username}`"><option v-for="(l, k) in levels" :key="k" :value="k">{{ l }}</option></select>
                    <span class="pg-level-actions"><button type="button" class="primary" @click="lvSave(u)">Simpan</button><button type="button" class="quiet" @click="lvEdit = null">Batal</button></span>
                    <small v-if="page.props.errors?.level" class="error">{{ page.props.errors.level }}</small>
                </template>
                <template v-else>
                    <span>{{ u.level }}</span>
                    <button type="button" class="pg-level-btn" :title="`Ubah jenjang ${u.username}`" @click="lvOpen(u)">Ubah</button>
                </template>
            </td>
            <td>{{ day(u.joined) }}</td>
            <td>{{ u.tests }}<small v-if="u.last_test">terakhir {{ day(u.last_test) }}</small></td>
            <td><span class="badge" :class="{ hidden: !u.active }">{{ u.active ? 'Aktif' : 'Nonaktif' }}</span><small v-if="u.temp">menunggu ganti sandi</small></td>
            <td class="actions">
                <button type="button" @click="temp(u)">Sandi sementara</button>
                <button type="button" :class="{ danger: u.active }" @click="toggle(u)">{{ u.active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
            </td>
        </tr></tbody>
    </table><p v-if="!items.data.length" style="padding:20px">Tidak ada pengguna yang cocok.</p></div>
    <Pager :p="items" label="pengguna" />
</template>

<style scoped>
.pg-issued { border: 2px solid #334eac; margin-bottom: 18px; }
.pg-level { white-space: nowrap; }
.pg-level > span:first-child { margin-right: 8px; }
.pg-level-btn { padding: 3px 10px !important; font-size: 12px !important; }
.pg-level select { display: block; margin-bottom: 6px; min-width: 130px; }
.pg-level-actions { display: inline-flex; gap: 6px; }
.pg-level-actions button { padding: 4px 10px !important; font-size: 12px !important; }
.pg-pass { font-size: 20px; font-weight: 700; padding: 6px 12px; background: #e7f1ff; border-radius: 8px; letter-spacing: .04em; }
</style>
