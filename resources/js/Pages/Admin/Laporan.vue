<script setup>
// Kotak masuk laporan "hasil tidak sesuai" dari siswa.
import { reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import Topline from '../../Components/Admin/Topline.vue';
import Pager from '../../Components/Admin/Pager.vue';

defineOptions({ layout: AdminLayout });
const props = defineProps({ items: Object, status: String, counts: Object });
const TABS = [['baru', 'Baru'], ['ditinjau', 'Ditinjau'], ['selesai', 'Selesai'], ['semua', 'Semua']];
const KIND = { career_path: 'Profesi', program: 'Prodi', career: 'Karier', mhs_eval: 'Prodi sekarang', mhs_switch: 'Pindah jurusan' };
const LEVEL = { smp: 'SMP', sma_smk: 'SMA/SMK', mhs: 'Mahasiswa' };
const notes = reactive(Object.fromEntries(props.items.data.map((x) => [x.id, x.admin_note ?? ''])));
const save = (x, status) => router.put(`/admin/laporan/${x.id}`, { status, admin_note: notes[x.id] }, { preserveScroll: true });
const when = (s) => new Date(s).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
const sim = (q) => '/admin/simulator?' + new URLSearchParams(q).toString();
</script>

<template>
    <Head title="Admin · Laporan siswa" />
    <Topline title="Laporan siswa" desc="Laporan dari tombol “Laporkan hasil tidak sesuai” di halaman hasil. Identitas siswa tidak ditampilkan; buka profilnya di simulator untuk menelusuri penyebabnya." />
    <div class="toolbar">
        <Link v-for="[k, l] in TABS" :key="k" as="button" :href="`/admin/laporan?status=${k}`" :class="{ primary: status === k }" preserve-state>{{ l }}<template v-if="counts[k]"> ({{ counts[k] }})</template></Link>
    </div>
    <p v-if="!items.data.length" class="panel">Tidak ada laporan di sini.</p>
    <section v-for="x in items.data" :key="x.id" class="panel" style="margin-bottom:16px">
        <div style="display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap">
            <div>
                <strong>#{{ x.id }} · {{ x.category_label }}</strong>
                <p style="margin:6px 0 0;color:var(--muted)">{{ when(x.at) }} · {{ LEVEL[x.level] || x.level }} · kode {{ x.code || '-' }}</p>
            </div>
            <span class="badge" :class="{ hidden: x.status === 'selesai' }">{{ x.status }}</span>
        </div>
        <p v-if="x.message" style="margin:12px 0">“{{ x.message }}”</p>
        <p v-if="x.recs.length" style="margin:8px 0;font-size:13px"><b>Yang dilihat siswa:</b> <span v-for="(r, i) in x.recs" :key="i">{{ KIND[r.kind] || r.kind }} #{{ r.rank }} {{ r.name }} ({{ Math.round(r.score * 100) }}%)<template v-if="i < x.recs.length - 1"> · </template></span></p>
        <div class="field" style="margin-top:10px"><label :for="'n' + x.id">Catatan admin</label><textarea :id="'n' + x.id" v-model="notes[x.id]" maxlength="2000" rows="2" placeholder="Temuan atau tindakan, mis. ubah kata kunci SMK 6.1"></textarea></div>
        <div class="actions" style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px">
            <a v-if="x.simulate" :href="sim(x.simulate)"><button type="button">Buka di simulator</button></a>
            <button type="button" @click="save(x, 'ditinjau')">Tandai ditinjau</button>
            <button type="button" class="primary" @click="save(x, 'selesai')">Selesai</button>
            <button v-if="x.status !== 'baru'" type="button" class="quiet" @click="save(x, 'baru')">Buka lagi</button>
        </div>
    </section>
    <Pager :p="items" label="laporan" />
</template>
