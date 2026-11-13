<script setup>
import { reactive, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import Topline from '../../Components/Admin/Topline.vue';
import Pager from '../../Components/Admin/Pager.vue';

defineOptions({ layout: AdminLayout });
const props = defineProps({ runs: Array, unmatched: Object, aliases: Array, filters: Object, has_raw: Boolean, expiry: Object, expiryCounts: Object, tab: String });
const e = reactive({ q: props.tab === 'kedaluwarsa' ? props.filters.q ?? '' : '', habis: props.filters.habis ?? 'lewat', jenis: props.filters.jenis ?? '' });
const applyExpiry = () => router.get('/admin/akreditasi', { tampil: 'kedaluwarsa', ...Object.fromEntries(Object.entries(e).filter(([, v]) => v)) }, { preserveState: true });
const day = (s) => (s ? new Date(s).toLocaleDateString('id-ID', { dateStyle: 'medium' }) : '-');
const left = (s) => Math.round((new Date(s) - new Date(new Date().toDateString())) / 86400000);
const f = reactive({ q: props.tab === 'cocok' ? props.filters.q ?? '' : '', sumber: props.filters.sumber ?? '' });
const apply = () => router.get('/admin/akreditasi', Object.fromEntries(Object.entries(f).filter(([, v]) => v)), { preserveState: true });
const when = (s) => (s ? new Date(s.replace(' ', 'T') + 'Z').toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : '-');

// Memasangkan nama BAN-PT ke kampus katalog.
const pairing = ref(null);
const search = ref('');
const found = ref([]);
let t;
function find() {
    clearTimeout(t);
    t = setTimeout(async () => {
        const r = await fetch('/admin/akreditasi/kampus?q=' + encodeURIComponent(search.value), { headers: { Accept: 'application/json' } });
        found.value = r.ok ? await r.json() : [];
    }, 250);
}
function startPair(name) { pairing.value = name; search.value = name.split(',')[0]; find(); }
function pair(pt) { router.post('/admin/akreditasi/pasangan', { banpt_name: pairing.value, pt_code: pt }, { preserveScroll: true, onSuccess: () => (pairing.value = null) }); }
const unpair = (id) => confirm('Hapus pasangan ini?') && router.delete(`/admin/akreditasi/pasangan/${id}`, { preserveScroll: true });
const resync = () => router.post('/admin/akreditasi/sinkron', {}, { preserveScroll: true });
</script>

<template>
    <Head title="Admin · Akreditasi" />
    <Topline title="Akreditasi BAN-PT" desc="Riwayat sinkron dan nama kampus di direktori BAN-PT yang belum cocok dengan katalog PDDikti. Pasangkan manual, lalu jalankan sinkron ulang.">
        <button class="primary" type="button" :disabled="!has_raw" :title="has_raw ? 'Memakai JSON BAN-PT terakhir yang tersimpan' : 'Jalankan php artisan banpt:sync dulu'" @click="resync">Sinkron ulang</button>
    </Topline>

    <section class="panel">
        <h2>Riwayat sinkron</h2>
        <div class="table-wrap"><table>
            <thead><tr><th>Jenis</th><th>Mulai</th><th>Status</th><th>Baris</th><th>Catatan</th></tr></thead>
            <tbody><tr v-for="r in runs" :key="r.id"><td>{{ r.kind }}</td><td>{{ when(r.started_at) }}</td><td><span class="badge" :class="{ hidden: r.status !== 'ok' }">{{ r.status }}</span></td><td>{{ r.rows_matched }} / {{ r.rows_in }}</td><td><small style="white-space:pre-line">{{ r.log }}</small></td></tr></tbody>
        </table></div>
    </section>

    <div class="toolbar" style="margin-top:24px">
        <Link as="button" href="/admin/akreditasi" :class="{ primary: tab === 'cocok' }">Belum cocok &amp; pasangan</Link>
        <Link as="button" href="/admin/akreditasi?tampil=kedaluwarsa" :class="{ primary: tab === 'kedaluwarsa' }">Kedaluwarsa ({{ expiryCounts.lewat }})</Link>
    </div>

    <section v-if="tab === 'kedaluwarsa'" class="panel">
        <h2>Akreditasi kedaluwarsa &amp; hampir habis</h2>
        <p class="notice" style="margin-bottom:14px">Data mengikuti direktori BAN-PT saat sinkron terakhir. Prodi yang sedang diakreditasi ulang bisa tampil kedaluwarsa sampai SK barunya terbit. Rekomendasi tetap memakai akreditasi ini, jadi sinkron ulang secara berkala.</p>
        <form class="toolbar" @submit.prevent="applyExpiry">
            <select v-model="e.habis" aria-label="Rentang"><option value="lewat">Sudah lewat ({{ expiryCounts.lewat }})</option><option value="30">Habis ≤ 30 hari ({{ expiryCounts['30'] }})</option><option value="90">Habis ≤ 90 hari ({{ expiryCounts['90'] }})</option></select>
            <select v-model="e.jenis" aria-label="Jenis"><option value="">Prodi &amp; kampus</option><option value="prodi">Prodi</option><option value="kampus">Kampus</option></select>
            <input v-model="e.q" placeholder="Cari kampus atau prodi…" aria-label="Cari">
            <button type="submit">Saring</button>
        </form>
        <div class="table-wrap"><table>
            <thead><tr><th>Kampus / prodi</th><th>Peringkat</th><th>Berlaku sampai</th><th>SK</th></tr></thead>
            <tbody><tr v-for="x in expiry.data" :key="x.id">
                <td><strong>{{ x.subject_type === 'campus' ? x.campus : x.program + ' (' + x.level + ')' }}</strong><small>{{ x.subject_type === 'campus' ? 'Institusi · ' + x.pt_code : x.campus }}</small></td>
                <td>{{ x.rating_raw }}<small>{{ x.body }}</small></td>
                <td>{{ day(x.expires_at) }}<small :style="{ color: left(x.expires_at) < 0 ? '#a83232' : '' }">{{ left(x.expires_at) < 0 ? 'lewat ' + -left(x.expires_at) + ' hari' : 'sisa ' + left(x.expires_at) + ' hari' }}</small></td>
                <td><small>{{ x.decree_no || '-' }}</small></td>
            </tr></tbody>
        </table><p v-if="!expiry.data.length" style="padding:20px">Tidak ada akreditasi di rentang ini.</p></div>
        <Pager :p="expiry" label="akreditasi" />
    </section>

    <section v-if="tab === 'cocok'" class="panel">
        <h2>Belum cocok</h2>
        <form class="toolbar" @submit.prevent="apply">
            <input v-model="f.q" placeholder="Cari nama kampus atau prodi…" aria-label="Cari">
            <select v-model="f.sumber" aria-label="Sumber"><option value="">Prodi &amp; institusi</option><option value="prodi">Prodi</option><option value="institusi">Institusi</option></select>
            <button type="submit">Cari</button>
        </form>
        <div class="table-wrap"><table>
            <thead><tr><th>Nama kampus di BAN-PT</th><th>Sumber</th><th>Baris</th><th>Tindakan</th></tr></thead>
            <tbody>
                <template v-for="u in unmatched.data" :key="u.id">
                    <tr><td><strong>{{ u.campus_name }}</strong></td><td>{{ u.source }}</td><td>{{ u.n }}</td><td class="actions"><button type="button" @click="startPair(u.campus_name)">Pasangkan</button></td></tr>
                    <tr v-if="pairing === u.campus_name"><td colspan="4">
                        <div class="toolbar" style="margin:8px 0"><input v-model="search" placeholder="Cari kampus di katalog (min. 3 huruf)…" @input="find"><button type="button" class="quiet" @click="pairing = null">Batal</button></div>
                        <div class="picker-results">
                            <button v-for="c in found" :key="c.pt_code" type="button" @click="pair(c.pt_code)">{{ c.name }} <small>{{ c.pt_code }} · {{ c.city }}</small></button>
                            <p v-if="!found.length"><small>Tidak ada kampus yang cocok.</small></p>
                        </div>
                    </td></tr>
                </template>
            </tbody>
        </table>
        <p v-if="!unmatched.data.length" style="padding:20px">Tidak ada baris yang belum cocok.</p></div>
        <Pager :p="unmatched" label="nama kampus" />
    </section>

    <section v-if="tab === 'cocok'" class="panel" style="margin-top:24px">
        <h2>Pasangan manual</h2>
        <p v-if="!aliases.length">Belum ada pasangan.</p>
        <div v-else class="table-wrap"><table>
            <thead><tr><th>Nama di BAN-PT</th><th>Kampus katalog</th><th>Tindakan</th></tr></thead>
            <tbody><tr v-for="a in aliases" :key="a.id"><td>{{ a.banpt_name }}</td><td>{{ a.name }} <small>{{ a.pt_code }}</small></td><td class="actions"><button type="button" class="danger" @click="unpair(a.id)">Hapus</button></td></tr></tbody>
        </table></div>
    </section>
</template>
