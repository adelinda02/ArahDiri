<script setup>
// Tab "Karier": jabatan KBJI 2026 dari rekomendasi (daftar .mini v14).
import { computed } from 'vue';
import Go from '../Go.vue';
import { pct } from '../../lib/format';

const props = defineProps({ recs: { type: Object, required: true } });

const groups = computed(() => {
    const r = props.recs;
    const g = [];
    if (r.career_paths?.length) {
        g.push({ title: 'Profesi yang paling cocok dengan minatmu', items: r.career_paths.map((c) => ({ code: c.id, title: c.title, desc: c.description, tag: pct(c.score) + '% selaras' })) });
        r.career_paths.forEach((c) => c.programs.length && g.push({ title: `Prodi menuju ${c.title}`, items: c.programs.map((p) => ({ code: p.id, title: p.name + ' (' + p.level + ')', tag: pct(p.score) + '% selaras' })) }));
    }
    if (r.level === 'mhs_umb' && r.careers.length) {
        g.push({ title: `Karier dari prodimu (${r.umb_eval?.name ?? ''}) yang paling cocok`, items: r.careers.map((c) => ({ code: c.id, title: c.title, desc: c.description, tag: pct(c.score) + '% selaras' })) });
    }
    const progs = r.level === 'mhs_umb' ? r.umb_programs.filter((p) => !p.is_current) : r.programs;
    progs.forEach((p) => g.push({ title: `Dari prodi ${p.name} (${p.level})`, items: p.careers.map((c) => ({ code: c.code, title: c.title, tag: 'KBJI ' + c.code })) }));
    if (r.level === 'smp') {
        r.smk_programs.forEach((s) => g.push({ title: `Dari program SMK ${s.name}`, items: s.careers.map((c) => ({ code: c.code, title: c.title, tag: 'KBJI ' + c.code })) }));
    }
    return g.filter((x) => x.items.length);
});
</script>

<template>
    <section class="star-catalog rec-tab">
        <span class="v2-label">KARIER UNTUK DIJELAJAHI</span>
        <h2>Jabatan yang terhubung dengan rekomendasimu</h2>
        <p class="star-notice">Nama jabatan mengikuti Klasifikasi Baku Jabatan Indonesia (KBJI) 2026 dari BPS dan Kemnaker.</p>
        <div v-for="grp in groups" :key="grp.title" class="rec-group">
            <h3>{{ grp.title }}</h3>
            <div v-for="c in grp.items" :key="grp.title + c.code" class="mini">
                <span class="nm">{{ c.title }}</span>
                <span class="tag">{{ c.tag }}</span>
                <span v-if="c.desc" class="ds">{{ c.desc }}</span>
            </div>
        </div>
        <p v-if="!groups.length" class="lead">Belum ada data karier untuk hasil ini.</p>
        <div class="btn-row" style="margin-top:18px"><Go href="/karier" class="btn">Jelajahi katalog karier</Go></div>
    </section>
</template>
