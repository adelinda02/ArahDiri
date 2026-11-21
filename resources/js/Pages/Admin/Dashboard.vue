<script setup>
// Ringkasan admin: yang perlu ditindaklanjuti di atas, lalu angka utama, lalu grafik per kelompok.
import { computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import Topline from '../../Components/Admin/Topline.vue';

defineOptions({ layout: AdminLayout });
const props = defineProps({ days: Number, stats: Object, users_by_level: Array, codes: Array, letters: Array, top_programs: Array, last_sync: Object,
    completed_by_level: Array, top_career_paths: Array, top_careers: Array, health: Array });
const page = usePage();
const isEditor = page.props.auth.user.role === 'editor';
const EDITOR_LINKS = ['reports', 'catalog'];
const DOT = { ok: '#16a34a', warn: '#d97706', bad: '#dc2626' };
const RANK = { bad: 0, warn: 1, ok: 2 };
// Istilah Holland, sama dengan yang dilihat siswa.
const NAMA = { R: 'Realistis', I: 'Investigatif', A: 'Artistik', S: 'Sosial', E: 'Enterprising', C: 'Konvensional' };
const num = (n) => (n ?? 0).toLocaleString('id-ID');
const max = (arr) => Math.max(1, ...arr.map((x) => x.n));

const reportsNew = computed(() => page.props.admin?.reports_new ?? 0);
// Yang bermasalah tampil duluan. Laporan siswa punya kartu sendiri, jadi tidak diulang di daftar kesehatan data.
const healthSorted = computed(() => (props.health ?? []).filter((h) => h.key !== 'reports').sort((a, b) => (RANK[a.level] ?? 3) - (RANK[b.level] ?? 3)));
const issues = computed(() => healthSorted.value.filter((h) => h.level !== 'ok').length);
const completion = computed(() => (props.stats.started ? Math.round((props.stats.completed / props.stats.started) * 100) : null));
const canLink = (h) => !isEditor || EDITOR_LINKS.includes(h.key);
</script>

<template>
    <Head title="Admin · Ringkasan" />
    <Topline title="Ringkasan" desc="Rekap penggunaan ArahDiri. Profil, jawaban, dan skor per orang tidak ditampilkan di sini.">
        <select :value="days" aria-label="Rentang waktu" @change="router.get('/admin', { hari: $event.target.value })">
            <option v-for="n in [7, 30, 90, 365]" :key="n" :value="n">{{ n }} hari terakhir</option>
        </select>
    </Topline>

    <!-- 1. Perlu ditindaklanjuti -->
    <section class="db-section">
        <div class="db-head">
            <h2>Perlu ditindaklanjuti</h2>
            <span class="db-pill" :class="{ ok: !reportsNew && !issues }">{{ reportsNew || issues ? `${reportsNew + issues} hal` : 'Semua aman' }}</span>
        </div>
        <div class="db-todo">
            <Link href="/admin/laporan" class="db-report" :class="{ empty: !reportsNew }">
                <span class="db-report-n">{{ num(reportsNew) }}</span>
                <span><b>Laporan siswa baru</b><small>{{ reportsNew ? 'Siswa merasa hasilnya tidak sesuai. Tinjau dan telusuri di simulator.' : 'Tidak ada laporan yang menunggu ditinjau.' }}</small></span>
                <span class="db-arrow" aria-hidden="true">›</span>
            </Link>
            <div class="db-health">
                <component :is="canLink(h) ? Link : 'div'" v-for="h in healthSorted" :key="h.key" :href="canLink(h) ? h.href : undefined" class="dh-item" :class="'lv-' + h.level">
                    <span class="dh-dot" :style="{ background: DOT[h.level] }" :aria-label="{ ok: 'aman', warn: 'perlu dicek', bad: 'bermasalah' }[h.level]"></span>
                    <span><b>{{ h.label }}</b><small>{{ h.text }}</small></span>
                </component>
            </div>
        </div>
    </section>

    <!-- 2. Angka utama -->
    <div class="stats">
        <article class="stat"><small>Akun pengguna aktif</small><strong>{{ num(stats.users) }}</strong><p>Semua jenjang, tidak termasuk admin</p></article>
        <article class="stat"><small>Tes dimulai</small><strong>{{ num(stats.started) }}</strong><p>Dalam {{ days }} hari terakhir</p></article>
        <article class="stat"><small>Tes selesai</small><strong>{{ num(stats.completed) }}</strong><p>{{ completion === null ? 'Belum ada tes dimulai' : `${completion}% dari tes yang dimulai` }}</p></article>
        <article class="stat"><small>Rekomendasi UMB muncul</small><strong>{{ stats.umb_rate === null ? '-' : stats.umb_rate + '%' }}</strong><p>Dari hasil SMA/SMK yang selesai</p></article>
    </div>

    <!-- 3. Grafik per kelompok -->
    <h2 class="db-group">Pengguna &amp; tes</h2>
    <div class="grid2">
        <section class="panel">
            <h3>Pengguna per jenjang</h3>
            <div v-for="u in users_by_level" :key="u.label" class="bar-row"><span>{{ u.label }}</span><div class="bar"><i :style="{ width: (u.n / max(users_by_level)) * 100 + '%' }"></i></div><b>{{ num(u.n) }}</b></div>
        </section>
        <section class="panel">
            <h3>Tes selesai per jenjang <small>({{ days }} hari)</small></h3>
            <div v-for="u in completed_by_level" :key="u.label" class="bar-row"><span>{{ u.label }}</span><div class="bar"><i :style="{ width: (u.n / max(completed_by_level)) * 100 + '%' }"></i></div><b>{{ num(u.n) }}</b></div>
        </section>
    </div>

    <h2 class="db-group">Minat siswa</h2>
    <div class="grid2">
        <section class="panel">
            <h3>Minat utama</h3>
            <div v-for="l in letters" :key="l.t" class="bar-row"><span>{{ NAMA[l.t] }}</span><div class="bar"><i :style="{ width: (l.n / max(letters)) * 100 + '%' }"></i></div><b>{{ num(l.n) }}</b></div>
        </section>
        <section class="panel">
            <h3>Kode Holland terbanyak</h3>
            <p v-if="!codes.length">Belum ada tes selesai.</p>
            <div v-for="c in codes" :key="c.holland_code" class="bar-row"><span class="db-mono">{{ c.holland_code }}</span><div class="bar"><i :style="{ width: (c.n / max(codes)) * 100 + '%' }"></i></div><b>{{ num(c.n) }}</b></div>
        </section>
    </div>

    <h2 class="db-group">Rekomendasi yang paling sering muncul</h2>
    <div class="grid2">
        <section class="panel">
            <h3>Prodi <small>(SMA/SMK)</small></h3>
            <p v-if="!top_programs.length">Belum ada rekomendasi.</p>
            <div v-for="p in top_programs" :key="p.name + p.level" class="bar-row"><span :title="p.name + ' (' + p.level + ')'">{{ p.name }} <small>{{ p.level }}</small></span><div class="bar"><i :style="{ width: (p.n / max(top_programs)) * 100 + '%' }"></i></div><b>{{ num(p.n) }}</b></div>
        </section>
        <section class="panel">
            <h3>Karier</h3>
            <p v-if="!top_careers.length">Belum ada rekomendasi karier.</p>
            <div v-for="p in top_careers" :key="p.level" class="bar-row"><span :title="p.name">{{ p.name }}</span><div class="bar"><i :style="{ width: (p.n / max(top_careers)) * 100 + '%' }"></i></div><b>{{ num(p.n) }}</b></div>
        </section>
        <section class="panel">
            <h3>Profesi jalur SMP</h3>
            <p v-if="!top_career_paths.length">Belum ada hasil SMP.</p>
            <div v-for="p in top_career_paths" :key="p.level" class="bar-row"><span :title="p.name">{{ p.name }}</span><div class="bar"><i :style="{ width: (p.n / max(top_career_paths)) * 100 + '%' }"></i></div><b>{{ num(p.n) }}</b></div>
        </section>
    </div>

</template>

<style scoped>
.db-section { margin: 4px 0 8px; }
.db-head { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
.db-head h2, .db-group { font-size: 17px; letter-spacing: -.01em; margin: 0; color: var(--ink); }
.db-group { margin: 28px 0 12px; }
.db-pill { font-size: 12px; font-weight: 700; padding: 3px 10px; border-radius: 999px; background: #fef3c7; color: #92400e; }
.db-pill.ok { background: #dcfce7; color: #166534; }
.db-todo { display: grid; grid-template-columns: minmax(240px, 320px) 1fr; gap: 12px; align-items: start; }
.db-report { display: flex; align-items: center; gap: 14px; padding: 18px; border-radius: 16px; border: 1.5px solid #1D70B8; background: #E3F0FB; color: inherit; text-decoration: none; transition: transform .15s, box-shadow .15s; }
.db-report:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(11, 60, 93, .12); }
.db-report.empty { border-color: var(--line); background: white; }
.db-report-n { flex: none; min-width: 52px; height: 52px; padding: 0 8px; border-radius: 14px; display: grid; place-items: center; font-size: 26px; font-weight: 800; background: #1D70B8; color: #fff; }
.db-report.empty .db-report-n { background: #E3F0FB; color: #1D70B8; }
.db-report small, .dh-item small { display: block; color: var(--muted); margin-top: 3px; line-height: 1.45; font-size: 12.5px; }
.db-arrow { margin-left: auto; font-size: 22px; color: #1D70B8; }
.db-health { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 10px; }
.dh-item { display: flex; gap: 12px; align-items: flex-start; padding: 12px 14px; border: 1px solid var(--line); border-radius: 12px; color: inherit; text-decoration: none; background: white; }
.dh-item.lv-bad { border-color: #fca5a5; background: #fef2f2; }
.dh-item.lv-warn { border-color: #fcd34d; background: #fffbeb; }
a.dh-item:hover { border-color: #1D70B8; }
.dh-dot { flex: none; width: 10px; height: 10px; border-radius: 50%; margin-top: 5px; }
.panel h3 { font-size: 15px; margin: 0 0 6px; color: var(--ink); }
.panel h3 small { font-weight: 500; color: var(--muted); }
.grid2 :deep(.bar-row), .grid2 .bar-row { grid-template-columns: minmax(120px, 1.1fr) 1fr 44px; margin: 16px 0; }
.bar-row > span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.bar-row > span small { color: var(--muted); }
.db-mono { font-family: ui-monospace, Consolas, monospace; font-weight: 700; letter-spacing: .06em; }
@media (max-width: 860px) { .db-todo { grid-template-columns: 1fr; } }
</style>
