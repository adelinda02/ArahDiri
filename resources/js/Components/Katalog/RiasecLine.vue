<script setup>
// Profil minat 6 dimensi singkat untuk jendela detail.
defineProps({ v: Object });
const NAMA = { R: 'Realistis', I: 'Investigatif', A: 'Artistik', S: 'Sosial', E: 'Enterprising', C: 'Konvensional' };
</script>

<template>
    <div v-if="v" class="riasec-line">
        <div v-for="(x, t) in v" :key="t" class="riasec-line-item"><span>{{ NAMA[t] }}</span><div class="ad-meter"><i :style="{ width: Math.round(x * 100) + '%' }"></i></div><b>{{ Math.round(x * 100) }}%</b></div>
    </div>
</template>
