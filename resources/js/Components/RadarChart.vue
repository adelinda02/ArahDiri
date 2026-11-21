<script setup>
// Grafik radar 6 dimensi (port renderRiasecRadarSvg v14).
import { computed } from 'vue';
import { ORDER, DIM } from '../lib/riasec';

const props = defineProps({ norm: { type: Object, required: true }, code: { type: String, default: 'RIASEC' } });
const cx = 190, cy = 145, rMax = 82, lr = 110;
const pt = (i, r) => { const a = ((i * 60 - 90) * Math.PI) / 180; return [cx + r * Math.cos(a), cy + r * Math.sin(a)]; };
const f1 = (n) => n.toFixed(1);
const poly = (f) => ORDER.map((_, i) => pt(i, rMax * f).map(f1).join(',')).join(' ');
const grids = [0.33, 0.66, 1.0].map(poly);
const spokes = ORDER.map((_, i) => pt(i, rMax).map(f1));
const vertices = computed(() => ORDER.map((t, i) => {
    const v = Math.max(0.08, Math.min(1, props.norm[t] ?? 0.5));
    return pt(i, rMax * (0.12 + 0.88 * v)).map(f1);
}));
const labels = ORDER.map((t, i) => {
    const p = pt(i, lr);
    return { x: f1(p[0]), y: f1(p[1] + (i === 0 ? -6 : i === 3 ? 14 : 4)), anchor: i === 0 || i === 3 ? 'middle' : i < 3 ? 'start' : 'end', text: DIM[t].name };
});
</script>

<template>
    <svg viewBox="0 0 380 290" role="img" :aria-label="`Grafik radar 6 dimensi RIASEC: ${code}`" style="width:100%;max-width:320px;height:auto;display:block;margin:0 auto">
        <polygon v-for="(g, k) in grids" :key="'g' + k" :points="g" fill="none" stroke="#CFE2F2" stroke-width="1.2" />
        <line v-for="(s, k) in spokes" :key="'s' + k" :x1="cx" :y1="cy" :x2="s[0]" :y2="s[1]" stroke="#CFE2F2" stroke-width="1" />
        <polygon :points="vertices.map((p) => p.join(',')).join(' ')" fill="rgba(90, 169, 230, 0.22)" stroke="#1D70B8" stroke-width="2.5" stroke-linejoin="round" />
        <circle v-for="(p, k) in vertices" :key="'c' + k" :cx="p[0]" :cy="p[1]" r="4" fill="#1D70B8" stroke="#ffffff" stroke-width="1.5" />
        <text v-for="l in labels" :key="l.text" :x="l.x" :y="l.y" :text-anchor="l.anchor" font-size="11.5" font-weight="700" fill="#0B3C5D" font-family="Plus Jakarta Sans, system-ui, sans-serif">{{ l.text }}</text>
    </svg>
</template>
