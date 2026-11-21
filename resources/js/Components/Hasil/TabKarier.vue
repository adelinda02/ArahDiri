<script setup>
// Tab "Karier": peluang karier dari rekomendasi, dengan deskripsi, kecocokan, dan pendidikan yang dibutuhkan.
import { computed } from 'vue';
import Go from '../Go.vue';
import LineIcon from './LineIcon.vue';
import { fitLabel, neutralFit, zoneText, KIND_ICON } from '../../lib/format';
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

// Ikon garis untuk tampilan (judul grup mengikuti jenisnya: jurusan untuk SMA/SMK, profesi untuk lainnya).
const groupIcon = computed(() => (props.recs.level === 'sma_smk' ? KIND_ICON.majors : KIND_ICON.careers));
const UI_ICON = {
    info: '<circle cx="12" cy="12" r="9"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
    edu: '<path d="m2 9 10-5 10 5-10 5-10-5Z"/><path d="M6 11v5c4 2.5 8 2.5 12 0v-5"/>',
    book: '<path d="M2 5h6a4 4 0 0 1 4 4v11a3 3 0 0 0-3-3H2z"/><path d="M22 5h-6a4 4 0 0 0-4 4v11a3 3 0 0 1 3-3h7z"/>',
    layers: '<path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 13 9 5 9-5"/>',
    path: '<path d="M6 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM18 9a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/><path d="M8 17h5a3 3 0 0 0 3-3V9"/>',
};
</script>

<template>
    <section class="star-catalog rec-tab rk">
        <span class="v2-label">PELUANG KARIER</span>
        <h2>Pekerjaan yang Terhubung dengan Rekomendasi</h2>
        <p class="rk-notice">
            <LineIcon :paths="UI_ICON.info" />
            <span>Nama jabatan mengikuti <b>Klasifikasi Baku Jabatan Indonesia (KBJI) 2026.</b> Persentase menunjukkan kecocokan pekerjaan itu dengan minatmu; pendidikan yang dibutuhkan diambil dari tingkat persiapan kerja O*NET.</span>
        </p>

        <div v-for="grp in groups" :key="grp.title" class="rk-group">
            <h3 class="rk-group-title"><span class="rk-group-icon"><LineIcon :paths="groupIcon" /></span>{{ grp.title }}</h3>
            <div class="rk-grid">
                <article v-for="c in grp.items" :key="grp.title + c.code" class="rk-card">
                    <div class="rk-card-head">
                        <span class="rk-card-icon"><LineIcon :paths="KIND_ICON.careers" /></span>
                        <div class="rk-card-title">
                            <div class="rk-title-row">
                                <b>{{ c.title }}</b>
                                <span v-if="c.score != null" class="fit-badge rk-fit" :class="'fit-' + badge(c.score).tone">{{ badge(c.score).label }} · {{ badge(c.score).pct }}%</span>
                            </div>
                            <p v-if="c.desc" class="rk-desc" :title="c.desc">{{ c.desc }}</p>
                            <p v-if="c.because.length" class="rk-sub">Cocok karena minat <b>{{ becauseText(c.because) }}</b></p>
                            <p v-if="c.via" class="rk-sub rk-via"><LineIcon :paths="UI_ICON.path" />{{ c.via }}</p>
                        </div>
                    </div>
                    <div class="rk-card-foot">
                        <span v-if="c.zone" class="rk-foot-item rk-foot-edu"><LineIcon :paths="UI_ICON.edu" /><span>Pendidikan: <b>{{ c.zone }}</b></span></span>
                        <span class="rk-foot-item rk-foot-code">
                            <LineIcon :paths="UI_ICON.book" />
                            <span class="rk-code-text"><span>KBJI {{ c.code }}</span><Go :href="'/karier/' + c.code" class="rk-link">Lihat di katalog →</Go></span>
                        </span>
                    </div>
                </article>
            </div>
        </div>

        <p v-if="!groups.length" class="lead">Belum ada karier untuk hasil ini.</p>
        <div class="rk-actions"><Go href="/karier" class="rk-explore"><LineIcon :paths="UI_ICON.layers" />Jelajahi katalog karier <span aria-hidden="true">→</span></Go></div>
    </section>
</template>
