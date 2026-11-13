<script setup>
// Program keahlian SMK (Kepmendikbudristek 244/M/2024).
import { Head } from '@inertiajs/vue3';
import BackBar from '../../Components/BackBar.vue';
import Go from '../../Components/Go.vue';

defineProps({ fields: Array });
</script>

<template>
    <Head title="Program Keahlian SMK" />
    <div class="wrap" style="padding-top:24px">
        <BackBar />
        <section class="star-catalog">
            <span class="v2-label">EKSPLORASI</span>
            <h1>Program Keahlian SMK</h1>
            <p>50 program keahlian dan konsentrasinya menurut Spektrum Keahlian SMK/MAK (Kepmendikbudristek 244/M/2024).</p>
            <p class="star-notice">Nama program bisa berbeda di tiap sekolah. Contoh jabatan lulusan dipadankan ke KBJI 2026 dan masih draf.</p>
            <template v-for="fld in fields" :key="fld.name">
                <h2>{{ fld.name }}</h2>
                <div class="star-grid">
                    <article v-for="p in fld.programs" :key="p.code" class="star-card">
                        <div class="star-card-top"><span>Program {{ p.code }}</span><span v-if="p.holland">Kode minat {{ p.holland }}</span></div>
                        <h3>{{ p.name }}</h3>
                        <p>{{ p.concentrations.join(' · ') }}</p>
                        <p v-if="p.careers.length"><small>Contoh jabatan: <template v-for="(c, i) in p.careers" :key="c.id"><Go :href="`/karier/${c.id}`" class="v2-link">{{ c.title }}</Go><template v-if="i < p.careers.length - 1">, </template></template></small></p>
                    </article>
                </div>
            </template>
        </section>
    </div>
</template>
