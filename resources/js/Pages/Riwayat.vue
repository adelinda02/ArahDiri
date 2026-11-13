<script setup>
// Riwayat tes v14 (vRiwayat): setiap sesi tes tersimpan apa adanya.
import { Head, router } from '@inertiajs/vue3';
import Go from '../Components/Go.vue';

const props = defineProps({ items: { type: Array, required: true }, max: { type: Object, required: true } });
const ORDER = ['R', 'I', 'A', 'S', 'E', 'C'];
const NAMA = { R: 'Praktis', I: 'Peneliti', A: 'Kreatif', S: 'Penolong', E: 'Pemimpin', C: 'Penata' };
const when = (iso) => new Date(iso).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' }) + ' WIB';
const pct = (x) => Math.round(x * 100);

function hapus(id) {
    if (confirm('Hapus riwayat sesi tes ini beserta rekomendasinya?')) router.delete(`/riwayat/${id}`, { preserveScroll: true });
}
function hapusSemua() {
    if (confirm('Hapus seluruh riwayat tes? Tindakan ini tidak bisa dibatalkan.')) router.delete('/riwayat');
}
const trash = '<polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>';
</script>

<template>
    <Head title="Riwayat Tes" />
    <div class="wrap">
        <div class="view-head">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px">
                <div>
                    <p class="eyebrow">Riwayat &amp; Progres</p>
                    <h2 class="h2" style="margin-bottom:4px">Riwayat Aktivitas &amp; Sesi Tes</h2>
                    <p class="lead" style="margin-bottom:0">Semua hasil tes minat tersimpan aman di akunmu, lengkap dengan rekomendasi saat itu.</p>
                </div>
                <button v-if="items.length" type="button" class="btn" style="font-size:0.84rem;padding:8px 16px;color:#3c75c6;border:1.5px solid #b7ccea;background:#f4f7fc;display:inline-flex;align-items:center;gap:6px;border-radius:10px;font-weight:700" title="Hapus seluruh riwayat tes yang tersimpan" @click="hapusSemua">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" v-html="trash"></svg>
                    <span>Hapus Semua Riwayat</span>
                </button>
            </div>
        </div>

        <div v-if="!items.length" class="panel" style="margin-top:12px">
            <p class="lead">Belum ada tes yang selesai.</p>
            <div class="btn-row" style="margin-top:12px"><Go href="/tes" class="btn btn-primary">Mulai Tes RIASEC</Go></div>
        </div>

        <div v-for="(it, i) in items" :key="it.id" class="hist-card">
            <div class="hist-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
                <div>
                    <h3 style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin:0 0 4px">
                        <span>Tes Minat Holland (RIASEC)</span>
                        <span v-if="i === 0" class="modal-tag-pill modal-badge-orange" style="font-size:0.72rem;padding:3px 10px">Sesi Terakhir (Aktif)</span>
                    </h3>
                    <span class="note">Status: <span class="tag on">Tersimpan di Riwayat</span> • Jenjang: <b>{{ it.level }}</b></span>
                </div>
                <div style="display:flex;align-items:center;gap:10px">
                    <span class="note" style="font-weight:600;color:var(--muted)">{{ when(it.completed_at) }}</span>
                    <button type="button" class="hist-delete-btn" title="Hapus riwayat sesi ini" aria-label="Hapus riwayat sesi ini" @click="hapus(it.id)">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" v-html="trash"></svg>
                    </button>
                </div>
            </div>
            <p style="margin:10px 0 12px;font-size:0.95rem">Profil Minat: <b class="mono" style="font-size:1.15rem;color:var(--accent)">{{ it.code }}</b> ({{ it.code.split('').map((t) => NAMA[t]).join(', ') }})</p>
            <div class="hist-dim-grid">
                <div v-for="t in ORDER" :key="t" class="hist-dim-card"><b>{{ NAMA[t] }}</b><span>{{ pct(it.norm[t]) }}% <small>({{ it.raw[t] }}/{{ max[t] }})</small></span></div>
            </div>
            <div class="btn-row" style="margin-top:16px;gap:10px;align-items:center;flex-wrap:wrap">
                <Go :href="`/hasil/${it.id}`" class="btn btn-primary" style="font-size:0.86rem;padding:8px 16px">Buka Hasil Rekomendasi →</Go>
                <a :href="`/hasil/${it.id}/cetak`" target="_blank" rel="noopener" class="btn" style="font-size:0.86rem;padding:8px 16px">Unduh PDF</a>
                <Go v-if="i === 0" href="/tes" class="btn" style="font-size:0.86rem;padding:8px 16px">Mulai Tes Kembali</Go>
            </div>
        </div>
    </div>
</template>
