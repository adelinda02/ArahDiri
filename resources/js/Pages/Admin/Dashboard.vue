<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import Topline from '../../Components/Admin/Topline.vue';

defineOptions({ layout: AdminLayout });
const props = defineProps({ days: Number, stats: Object, users_by_level: Array, codes: Array, letters: Array, top_programs: Array, last_sync: Object,
    completed_by_level: Array, top_career_paths: Array, top_careers: Array, health: Array });
const isEditor = usePage().props.auth.user.role === 'editor';
const EDITOR_LINKS = ['reports', 'catalog'];
const DOT = { ok: '#16a34a', warn: '#d97706', bad: '#dc2626' };
const NAMA = { R: 'Realistis', I: 'Investigatif', A: 'Artistik', S: 'Sosial', E: 'Enterprising', C: 'Konvensional' };
const num = (n) => (n ?? 0).toLocaleString('id-ID');
const max = (arr) => Math.max(1, ...arr.map((x) => x.n));
const when = (s) => (s ? new Date(s.replace(' ', 'T') + 'Z').toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : '-');
</script>

<template>
    <Head title="Admin · Ringkasan" />
    <Topline title="Ringkasan aktivitas" desc="Pantau penggunaan tanpa membuka profil atau jawaban pribadi pengguna.">
        <select :value="days" aria-label="Rentang waktu" @change="router.get('/admin', { hari: $event.target.value })">
            <option v-for="n in [7, 30, 90, 365]" :key="n" :value="n">{{ n }} hari terakhir</option>
        </select>
    </Topline>
    <div class="stats">
        <article class="stat"><small>Akun pengguna aktif</small><strong>{{ num(stats.users) }}</strong><p>Semua jenjang, tidak termasuk admin</p></article>
        <article class="stat"><small>Sesi tes dimulai</small><strong>{{ num(stats.started) }}</strong><p>Dalam {{ days }} hari terakhir</p></article>
        <article class="stat"><small>Tes selesai</small><strong>{{ num(stats.completed) }}</strong><p>Sesi yang mencapai hasil</p></article>
        <article class="stat"><small>Section UMB tampil</small><strong>{{ stats.umb_rate === null ? '-' : stats.umb_rate + '%' }}</strong><p>Dari hasil SMA/SMK yang selesai</p></article>
    </div>
    <section class="panel" style="margin-bottom:20px">
        <h2 style="margin-top:0">Kesehatan data</h2>
        <div class="dh-grid">
            <component :is="!isEditor || EDITOR_LINKS.includes(h.key) ? Link : 'div'" v-for="h in health" :key="h.key" :href="h.href" class="dh-item">
                <span class="dh-dot" :style="{ background: DOT[h.level] }" :aria-label="h.level"></span>
                <span><b>{{ h.label }}</b><small>{{ h.text }}</small></span>
            </component>
        </div>
    </section>
    <div class="notice">Dashboard hanya menampilkan rekap; profil, jawaban, dan skor individu tidak dibuka di sini.</div>
    <div class="grid2" style="margin-top:24px">
        <section class="panel">
            <h2>Pengguna per jenjang</h2>
            <div v-for="u in users_by_level" :key="u.label" class="bar-row"><span>{{ u.label }}</span><div class="bar"><i :style="{ width: (u.n / max(users_by_level)) * 100 + '%' }"></i></div><b>{{ num(u.n) }}</b></div>
        </section>
        <section class="panel">
            <h2>Tes selesai per jenjang ({{ days }} hari)</h2>
            <div v-for="u in completed_by_level" :key="u.label" class="bar-row"><span>{{ u.label }}</span><div class="bar"><i :style="{ width: (u.n / max(completed_by_level)) * 100 + '%' }"></i></div><b>{{ num(u.n) }}</b></div>
        </section>
        <section class="panel">
            <h2>Minat utama</h2>
            <div v-for="l in letters" :key="l.t" class="bar-row"><span>{{ NAMA[l.t] }}</span><div class="bar"><i :style="{ width: (l.n / max(letters)) * 100 + '%' }"></i></div><b>{{ num(l.n) }}</b></div>
        </section>
        <section class="panel">
            <h2>Kode Holland terbanyak</h2>
            <p v-if="!codes.length">Belum ada tes selesai.</p>
            <div v-for="c in codes" :key="c.holland_code" class="bar-row"><span>{{ c.holland_code }}</span><div class="bar"><i :style="{ width: (c.n / max(codes)) * 100 + '%' }"></i></div><b>{{ num(c.n) }}</b></div>
        </section>
        <section class="panel">
            <h2>Prodi paling sering direkomendasikan</h2>
            <p v-if="!top_programs.length">Belum ada rekomendasi.</p>
            <div v-for="p in top_programs" :key="p.name + p.level" class="bar-row"><span>{{ p.name }} ({{ p.level }})</span><div class="bar"><i :style="{ width: (p.n / max(top_programs)) * 100 + '%' }"></i></div><b>{{ num(p.n) }}</b></div>
        </section>
        <section class="panel">
            <h2>Profesi jalur SMP terbanyak</h2>
            <p v-if="!top_career_paths.length">Belum ada hasil SMP.</p>
            <div v-for="p in top_career_paths" :key="p.level" class="bar-row"><span>{{ p.name }}</span><div class="bar"><i :style="{ width: (p.n / max(top_career_paths)) * 100 + '%' }"></i></div><b>{{ num(p.n) }}</b></div>
        </section>
        <section class="panel">
            <h2>Karier paling sering direkomendasikan</h2>
            <p v-if="!top_careers.length">Belum ada rekomendasi karier.</p>
            <div v-for="p in top_careers" :key="p.level" class="bar-row"><span>{{ p.name }}</span><div class="bar"><i :style="{ width: (p.n / max(top_careers)) * 100 + '%' }"></i></div><b>{{ num(p.n) }}</b></div>
        </section>
    </div>
    <section class="panel" style="margin-top:24px">
        <h2>Sinkron akreditasi BAN-PT terakhir</h2>
        <p v-if="!last_sync">Belum pernah dijalankan.</p>
        <p v-else>{{ when(last_sync.finished_at || last_sync.started_at) }} · status <b>{{ last_sync.status }}</b><br><small style="white-space:pre-line">{{ last_sync.log }}</small></p>
    </section>
</template>

<style scoped>
.dh-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 10px; }
.dh-item { display: flex; gap: 12px; align-items: flex-start; padding: 12px 14px; border: 1px solid var(--line); border-radius: 12px; color: inherit; text-decoration: none; background: #fbfcff; }
a.dh-item:hover { border-color: #7096d1; }
.dh-dot { flex: none; width: 10px; height: 10px; border-radius: 50%; margin-top: 5px; }
.dh-item small { display: block; color: var(--muted); margin-top: 2px; line-height: 1.45; }
</style>
