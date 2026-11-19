<script setup>
// Tab "Karier": peluang karier dari rekomendasi, dengan deskripsi, kecocokan, dan pendidikan yang dibutuhkan.
import { computed } from 'vue';
import Go from '../Go.vue';
import { fitLabel, neutralFit, zoneText } from '../../lib/format';
import { becauseText } from '../../lib/riasec';

const props = defineProps({ recs: { type: Object, required: true } });
// Mahasiswa yang suka jurusannya: angka kecocokan tanpa label "kurang cocok".
const badge = (s) => (props.recs.level === 'mhs' && !props.recs.current?.feels_mismatch ? neutralFit(s) : fitLabel(s));

const card = (c, extra = {}) => ({
    code: c.code ?? c.id, title: c.title, desc: c.description, score: c.score ?? null,
    zone: zoneText(c.zone_min, c.zone_max), because: c.because ?? [], ...extra,
});

const groups = computed(() => {
    const r = props.recs;
    const g = [];
    if (r.level === 'smp') {
        g.push({ title: 'Profesi yang paling cocok dengan minatmu', items: (r.career_paths ?? []).map((c) => card(c, { via: 'Kuliah: ' + c.programs.map((p) => p.name).join(' / ') })) });
    }
    if (r.level === 'mhs') {
        const ya = r.current?.feels_mismatch;
        g.push({
            title: ya ? 'Karier yang paling cocok dengan minatmu' : `Profesi dari jurusan ${r.current?.name ?? 'mu'} yang paling cocok`,
            items: (r.careers ?? []).map((c) => card(c, { via: ya && c.programs?.length ? 'Jurusan yang sejalan: ' + c.programs.map((p) => p.name).join(', ') : null })),
        });
    } else if (r.level === 'sma_smk') {
        r.programs.forEach((p) => g.push({ title: `Dari jurusan ${p.name} (${p.level})`, items: (p.careers ?? []).map((c) => card(c)) }));
    }
    return g.filter((x) => x.items.length);
});
</script>

<template>
    <section class="star-catalog rec-tab">
        <span class="v2-label">PELUANG KARIER</span>
        <h2>Pekerjaan yang terhubung dengan rekomendasimu</h2>
        <p class="star-notice">Nama jabatan mengikuti Klasifikasi Baku Jabatan Indonesia (KBJI) 2026. Persentase menunjukkan kecocokan pekerjaan itu dengan minatmu; pendidikan yang dibutuhkan diambil dari tingkat persiapan kerja O*NET.</p>
        <div v-for="grp in groups" :key="grp.title" class="rec-group">
            <h3>{{ grp.title }}</h3>
            <div class="rx-career-grid">
                <article v-for="c in grp.items" :key="grp.title + c.code" class="rx-career-card">
                    <div class="rx-career-top">
                        <b>{{ c.title }}</b>
                        <span v-if="c.score != null" class="fit-badge" :class="'fit-' + badge(c.score).tone">{{ badge(c.score).label }} · {{ badge(c.score).pct }}%</span>
                    </div>
                    <p v-if="c.desc">{{ c.desc }}</p>
                    <ul class="rx-career-meta">
                        <li v-if="c.because.length">Cocok karena minat <b>{{ becauseText(c.because) }}</b></li>
                        <li v-if="c.zone">Pendidikan: <b>{{ c.zone }}</b></li>
                        <li v-if="c.via">{{ c.via }}</li>
                    </ul>
                    <small>KBJI {{ c.code }} · <Go :href="'/karier/' + c.code">lihat di katalog →</Go></small>
                </article>
            </div>
        </div>
        <p v-if="!groups.length" class="lead">Belum ada karier untuk hasil ini.</p>
        <div class="btn-row" style="margin-top:18px"><Go href="/karier" class="btn">Jelajahi katalog karier</Go></div>
    </section>
</template>
