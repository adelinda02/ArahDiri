<script setup>
// AI PATHWAY: kesimpulan ringkas + langkah berikutnya, disusun otomatis dari hasil tes (lib/pathway.js).
import { computed } from 'vue';
import { buildPathway } from '../../lib/pathway';

const props = defineProps({
    recs: { type: Object, required: true },
    code: { type: String, required: true },
    top3: { type: Array, required: true },
    norm: { type: Object, required: true },
});
const emit = defineEmits(['open']);
const p = computed(() => buildPathway(props.recs, { code: props.code, top3: props.top3, norm: props.norm }));
</script>

<template>
    <section v-if="p" class="aip" aria-labelledby="aip-title">
        <div class="aip-head">
            <span class="aip-badge">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l1.9 5.6L19.5 10l-5.6 1.9L12 17.5l-1.9-5.6L4.5 10l5.6-1.4z" /><path d="M19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8z" /></svg>
                AI PATHWAY
            </span>
            <h2 id="aip-title">{{ p.headline }}</h2>
            <p class="aip-summary">{{ p.summary }}</p>
        </div>

        <div class="aip-grid">
            <article class="aip-main">
                <span class="aip-kicker">{{ p.main.label }}</span>
                <h3>{{ p.main.title }}</h3>
                <span class="fit-badge" :class="'fit-' + p.main.fit.tone">{{ p.main.fit.label }} · {{ p.main.fit.pct }}%</span>
                <ul class="aip-points"><li v-for="t in p.main.points" :key="t">{{ t }}</li></ul>
            </article>
            <article v-if="p.alternatives.length" class="aip-alts">
                <span class="aip-kicker">{{ p.alternativesTitle || 'Alternatif yang juga cocok' }}</span>
                <div v-for="a in p.alternatives" :key="a.title" class="aip-alt">
                    <div class="aip-alt-top"><b>{{ a.title }}</b><span class="fit-badge" :class="'fit-' + a.fit.tone">{{ a.fit.pct }}%</span></div>
                    <p>{{ a.text }}</p>
                </div>
            </article>
        </div>

        <div class="aip-steps">
            <span class="aip-kicker">Langkah berikutnya</span>
            <ol class="aip-timeline">
                <li v-for="s in p.steps" :key="s.when">
                    <b>{{ s.when }}</b>
                    <ul><li v-for="t in s.items" :key="t">{{ t }}</li></ul>
                </li>
            </ol>
        </div>

        <ul v-if="p.notes.length" class="aip-notes"><li v-for="n in p.notes" :key="n">{{ n }}</li></ul>
        <p class="aip-method">Disusun otomatis oleh ArahDiri dari skor RIASEC-mu, katalog prodi PDDikti, jabatan KBJI 2026, dan akreditasi BAN-PT. Ini bahan pertimbangan, bukan keputusan; diskusikan dengan guru BK, dosen, atau orang tua.</p>
        <button type="button" class="btn btn-primary" @click="emit('open', 'jurusan', 0)">Lihat detail lengkap</button>
    </section>
</template>
