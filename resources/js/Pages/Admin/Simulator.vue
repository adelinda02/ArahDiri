<script setup>
// Simulator rekomendasi: atur skor RIASEC + jenjang, lihat hasilnya tanpa menyimpan apa pun.
import { reactive, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import Topline from '../../Components/Admin/Topline.vue';

defineOptions({ layout: AdminLayout });
const props = defineProps({ input: Object, levels: Object, umbOptions: Array, code: String, result: Object, ms: Number, report: Object });

const DIMS = [['r', 'Realistic'], ['i', 'Investigative'], ['a', 'Artistic'], ['s', 'Social'], ['e', 'Enterprising'], ['c', 'Conventional']];
const f = reactive({ ...props.input });
const PRESETS = { ISC: [33, 73, 43, 65, 40, 58], RIC: [90, 80, 20, 30, 30, 60], ASE: [20, 30, 95, 80, 70, 30], ECS: [30, 35, 30, 60, 90, 70], SEA: [20, 30, 60, 95, 80, 30], Rata: [50, 50, 50, 50, 50, 50] };
function preset(k) { DIMS.forEach(([d], i) => (f[d] = PRESETS[k][i])); }
function run() {
    const q = { jenjang: f.jenjang, jalankan: 1, ...Object.fromEntries(DIMS.map(([d]) => [d, f[d]])) };
    if (f.jenjang === 'mhs' && f.umb) q.umb = f.umb;
    if (f.jenjang === 'mhs') q.salah = f.salah ? 1 : 0;
    router.get('/admin/simulator', q, { preserveState: true, preserveScroll: true });
}
const pct = (s) => Math.round((s ?? 0) * 100);
const liveCode = computed(() => DIMS.map(([d]) => d).sort((a, b) => f[b] - f[a]).slice(0, 3).join('').toUpperCase());
const r = computed(() => props.result);
const empty = computed(() => r.value && !['career_paths', 'programs', 'careers', 'switch_programs'].some((k) => r.value[k]?.length) && !r.value.current);
const acc = (c) => [c.program_accreditation?.rating && 'Prodi ' + c.program_accreditation.rating, c.campus_accreditation?.rating && 'PT ' + c.campus_accreditation.rating].filter(Boolean).join(' · ');
</script>

<template>
    <Head title="Admin · Simulator rekomendasi" />
    <Topline title="Simulator rekomendasi" desc="Atur skor minat dan jenjang, lalu lihat rekomendasi yang akan diterima siswa dengan profil itu. Simulasi tidak menyimpan apa pun dan memakai data serta aturan yang berlaku saat ini." />

    <div v-if="report" class="notice" style="margin-bottom:18px">Dibuka dari laporan siswa #{{ report.id }}: <b>{{ report.category }}</b><template v-if="report.message"> — “{{ report.message }}”</template></div>

    <section class="panel" style="margin-bottom:20px">
        <form class="form-grid" @submit.prevent="run">
            <div class="field">
                <label for="sim-level">Jenjang</label>
                <select id="sim-level" v-model="f.jenjang"><option v-for="(l, k) in levels" :key="k" :value="k">{{ l }}</option></select>
            </div>
            <div v-if="f.jenjang === 'mhs'" class="field">
                <label for="sim-umb">Contoh prodi yang dijalani (UMB)</label>
                <select id="sim-umb" v-model="f.umb"><option v-for="o in umbOptions" :key="o.id" :value="o.id">{{ o.label }}</option></select>
            </div>
            <div v-if="f.jenjang === 'mhs'" class="field">
                <label for="sim-salah">Merasa salah jurusan?</label>
                <select id="sim-salah" v-model="f.salah"><option :value="true">Iya</option><option :value="false">Tidak</option></select>
            </div>
            <div class="field wide">
                <label>Skor minat (0–100%) · kode saat ini <b>{{ liveCode }}</b></label>
                <div class="sim-sliders">
                    <div v-for="[d, name] in DIMS" :key="d" class="sim-slider">
                        <label :for="'sim-' + d" class="sim-name"><b>{{ d.toUpperCase() }}</b><small>{{ name }}</small></label>
                        <input :id="'sim-' + d" v-model.number="f[d]" type="range" min="0" max="100" step="1" class="sim-range">
                        <input v-model.number="f[d]" type="number" min="0" max="100" class="sim-num" :aria-label="name + ' (angka)'">
                    </div>
                </div>
                <small>Contoh profil: <button v-for="(v, k) in PRESETS" :key="k" type="button" class="quiet sim-chip" @click="preset(k)">{{ k }}</button></small>
            </div>
            <div class="form-actions"><button class="primary" type="submit">Jalankan simulasi</button></div>
        </form>
    </section>

    <template v-if="r">
        <div class="topline" style="margin-bottom:10px"><div><h2 style="margin:0">Hasil untuk kode {{ code }} · {{ levels[r.level] }}</h2><p>Dihitung dalam {{ ms }} ms.</p></div></div>
        <p v-if="empty" class="notice">Tidak ada rekomendasi. Periksa apakah katalog sudah diimpor dan data akreditasi tersedia.</p>

        <!-- SMP: jalur per profesi -->
        <div v-if="r.career_paths?.length" class="table-wrap" style="margin-bottom:20px"><table>
            <thead><tr><th>#</th><th>Profesi</th><th>Selaras</th><th>SMA</th><th>SMK</th><th>Kuliah</th></tr></thead>
            <tbody><tr v-for="c in r.career_paths" :key="c.id">
                <td>{{ c.rank }}</td>
                <td><strong>{{ c.title }}</strong><small>KBJI {{ c.id }} · {{ c.holland }}</small></td>
                <td>{{ pct(c.score) }}%</td>
                <td>{{ c.sma.name }}<small>{{ c.sma.any ? 'tanpa mapel khas' : c.sma.via.join(', ') }}</small></td>
                <td><template v-if="c.smk.length"><span v-for="k in c.smk" :key="k.code" style="display:block">{{ k.name }}<small>{{ k.via === 'career' ? 'lewat jabatan' : 'lewat prodi' }}</small></span></template><small v-else>tidak ada yang linear</small></td>
                <td><span v-for="p in c.programs" :key="p.id" style="display:block">{{ p.name }} ({{ p.level }})<small>{{ p.subjects.join(', ') || 'mapel tidak diketahui' }} · {{ p.offerings_count }} kampus</small></span></td>
            </tr></tbody>
        </table></div>

        <!-- UMB: posisi prodi sekarang -->
        <div v-if="r.current" class="notice" style="margin-bottom:16px">Prodi sekarang <b>{{ r.current.name }} ({{ r.current.level }})</b> di {{ r.current.campus?.name }} · selaras {{ pct(r.current.score) }}% · merasa salah jurusan: <b>{{ r.current.feels_mismatch ? 'iya' : 'tidak' }}</b>.</div>
        <div v-if="r.level === 'mhs' && r.careers?.length" class="table-wrap" style="margin-bottom:20px"><table>
            <thead><tr><th>#</th><th>Karier</th><th>Selaras</th><th>Prodi</th></tr></thead>
            <tbody><tr v-for="c in r.careers" :key="c.id"><td>{{ c.rank }}</td><td><strong>{{ c.title }}</strong><small>KBJI {{ c.id }} · {{ c.holland }}</small></td><td>{{ pct(c.score) }}%</td><td><small>{{ (c.programs || []).map((p) => p.name + (p.score != null ? ' (' + pct(p.score) + '%)' : '')).join(', ') }}</small></td></tr></tbody>
        </table></div>

        <!-- Prodi (SMA/SMK) dan prodi UMB -->
        <template v-for="[key, title] in [['programs', 'Prodi'], ['switch_programs', 'Pindah jurusan'], ['local_programs', 'Di kampus sendiri']]" :key="key">
            <div v-if="r[key]?.length" class="table-wrap" style="margin-bottom:20px"><table>
                <thead><tr><th>#</th><th>{{ title }}</th><th>Selaras</th><th>Kampus</th><th>Karier utama</th></tr></thead>
                <tbody><tr v-for="p in r[key]" :key="p.id">
                    <td>{{ p.rank }}</td>
                    <td><strong>{{ p.name }}</strong><small>{{ p.level }} · {{ p.holland }}</small></td>
                    <td>{{ pct(p.score) }}%</td>
                    <td>{{ p.campus?.name || 'menunggu akreditasi' }}<small>{{ acc(p.campus || {}) }}</small></td>
                    <td><small>{{ (p.careers || []).slice(0, 2).map((c) => c.title).join(', ') }}</small></td>
                </tr></tbody>
            </table></div>
        </template>

        <div v-if="r.careers?.length" class="table-wrap" style="margin-bottom:20px"><table>
            <thead><tr><th>#</th><th>Karier</th><th>Selaras</th></tr></thead>
            <tbody><tr v-for="c in r.careers" :key="c.id"><td>{{ c.rank }}</td><td><strong>{{ c.title }}</strong><small>KBJI {{ c.id }} · {{ c.holland }}</small></td><td>{{ pct(c.score) }}%</td></tr></tbody>
        </table></div>

        <div v-if="r.umb_promos?.length" class="table-wrap"><table>
            <thead><tr><th>Promosi prodi UMB</th><th>Alasan</th></tr></thead>
            <tbody><tr v-for="(p, i) in r.umb_promos" :key="i"><td><strong>{{ p.name }}</strong><small>{{ p.level }}</small></td><td><small>{{ p.type === 'same_family' ? 'Prodi sama dengan rekomendasi #' + p.for_rank : 'Lewat karier ' + (p.via || '') }}</small></td></tr></tbody>
        </table></div>
    </template>
</template>

<style scoped>
.sim-sliders { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 12px 28px; margin: 8px 0 12px; }
.sim-slider { display: grid; grid-template-columns: 104px minmax(0, 1fr) 64px; align-items: center; gap: 12px; padding: 8px 12px; border: 1px solid var(--line); border-radius: 12px; background: #fbfcff; }
.sim-name { display: grid; line-height: 1.25; font-size: 13px; }
.sim-name small { font-weight: 400; color: var(--muted); font-size: 11.5px; }
.admin-app .sim-slider .sim-range { padding: 0; border: 0; background: transparent; width: 100%; min-width: 0; accent-color: #334eac; }
.admin-app .sim-slider .sim-num { width: 64px; padding: 6px 8px; text-align: center; }
.sim-chip { padding: 4px 10px; margin-left: 4px; font-size: 12px; }
td small { display: block; color: var(--muted); margin-top: 2px; }
</style>
