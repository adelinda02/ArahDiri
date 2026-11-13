<script setup>
// Hasil pencarian lengkap dari kotak cari header.
import { Head } from '@inertiajs/vue3';
import Go from '../Components/Go.vue';

defineProps({ q: { type: String, default: '' }, results: { type: Object, required: true } });
</script>

<template>
    <Head :title="`Cari: ${q}`" />
    <div class="wrap" style="padding-top:24px">
        <div class="v2-dash-heading">
            <div>
                <div class="ed-kicker">HASIL PENCARIAN</div>
                <h1>&ldquo;{{ q }}&rdquo;</h1>
                <p>{{ results.programs.length + results.careers.length + results.campuses.length }} hasil dari katalog jurusan, profesi KBJI, dan kampus.</p>
            </div>
        </div>
        <div v-for="sec in [['programs', 'Jurusan Kuliah'], ['careers', 'Karier & Profesi'], ['campuses', 'Kampus']]" :key="sec[0]" class="search-section" style="margin-bottom:24px">
            <template v-if="results[sec[0]].length">
                <div class="search-sec-title">{{ sec[1] }} ({{ results[sec[0]].length }})</div>
                <Go v-for="it in results[sec[0]]" :key="it.key ?? it.code" :href="it.url" class="search-item">
                    <div class="search-item-info"><span class="search-item-title">{{ it.name }}</span><span class="search-item-sub">{{ it.sub }}</span></div>
                    <span class="search-item-tag">{{ it.tag ?? 'Profesi' }}</span>
                </Go>
            </template>
        </div>
    </div>
</template>
