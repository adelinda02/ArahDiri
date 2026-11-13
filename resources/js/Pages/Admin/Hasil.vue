<script setup>
import { reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import Topline from '../../Components/Admin/Topline.vue';
import Pager from '../../Components/Admin/Pager.vue';

defineOptions({ layout: AdminLayout });
const props = defineProps({ items: Object, filters: Object, levels: Object, isOwner: Boolean, total: Number });
const f = reactive({ jenjang: props.filters.jenjang ?? '', dari: props.filters.dari ?? '', sampai: props.filters.sampai ?? '', kode: props.filters.kode ?? '' });
const reset = () => { Object.keys(f).forEach((k) => (f[k] = '')); apply(); };
const clean = () => Object.fromEntries(Object.entries(f).filter(([, v]) => v));
const apply = () => router.get('/admin/hasil', clean(), { preserveState: true });
const exportUrl = (answers) => '/admin/hasil/ekspor?' + new URLSearchParams({ ...clean(), ...(answers ? { jawaban: 1 } : {}) });
const when = (s) => new Date(s.replace(' ', 'T') + 'Z').toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
</script>

<template>
    <Head title="Admin · Data hasil tes" />
    <Topline title="Data hasil tes" desc="Hasil ditampilkan dengan ID acak, tanpa nama, email, atau nomor HP. Ekspor CSV untuk penelitian memakai ID yang sama.">
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a class="primary" :href="exportUrl(false)" style="padding:11px 16px;border-radius:10px;text-decoration:none">Ekspor CSV</a>
            <a v-if="isOwner" :href="exportUrl(true)" style="padding:11px 16px;border:1px solid var(--line);border-radius:10px;text-decoration:none" title="Khusus owner, tercatat di log audit">Ekspor + jawaban mentah</a>
        </div>
    </Topline>
    <form class="toolbar" @submit.prevent="apply">
        <select v-model="f.jenjang" aria-label="Jenjang"><option value="">Semua jenjang</option><option v-for="(l, k) in levels" :key="k" :value="k">{{ l }}</option></select>
        <label>Dari <input v-model="f.dari" type="date"></label>
        <label>Sampai <input v-model="f.sampai" type="date"></label>
        <input v-model="f.kode" maxlength="3" placeholder="Kode Holland, mis. I atau IAS" aria-label="Kode Holland" style="max-width:220px;text-transform:uppercase">
        <button type="submit">Saring</button>
        <button type="button" class="quiet" @click="reset">Hapus saringan</button>
    </form>
    <p style="margin:0 0 10px;color:var(--muted)"><b>{{ (total ?? 0).toLocaleString('id-ID') }}</b> hasil cocok dengan saringan. Ekspor CSV mengikuti saringan yang sama. Huruf tunggal = minat utama; 2–3 huruf = awalan kode.</p>
    <div class="table-wrap">
        <table>
            <thead><tr><th>ID acak</th><th>Jenjang</th><th>Kode Holland</th><th>Selesai</th></tr></thead>
            <tbody><tr v-for="r in items.data" :key="r.rid"><td><code>{{ r.rid }}</code></td><td>{{ r.level }}</td><td><strong>{{ r.code }}</strong></td><td>{{ when(r.completed_at) }}</td></tr></tbody>
        </table>
        <p v-if="!items.data.length" style="padding:20px">Belum ada hasil yang cocok.</p>
    </div>
    <Pager :p="items" label="hasil" />
</template>
