<script setup>
// Pilihan tersimpan v14 (vFavorit): jurusan, karier, kampus.
import { Head, router } from '@inertiajs/vue3';
import BackBar from '../Components/BackBar.vue';
import Go from '../Components/Go.vue';
import LineIcon from '../Components/Hasil/LineIcon.vue';
import { KIND_ICON } from '../lib/format';

defineProps({ programs: Array, careers: Array, campuses: Array });
const sections = [['programs', 'Jurusan', 'program', 'majors', '/jurusan'], ['careers', 'Karier', 'career', 'careers', '/karier'], ['campuses', 'Kampus', 'campus', 'campuses', '/kampus']];
const remove = (kind, id) => router.post('/favorit', { kind, target_id: id }, { preserveScroll: true });
</script>

<template>
    <Head title="Pilihan tersimpan" />
    <div class="wrap" style="padding-top:24px">
        <BackBar />
        <section class="star-catalog">
            <h1>Pilihan tersimpan</h1>
            <template v-for="s in sections" :key="s[0]">
                <h2>{{ s[1] }}</h2>
                <div class="star-grid">
                    <article v-for="it in $props[s[0]]" :key="it.id" class="star-card">
                        <div class="star-card-top">
                            <span>{{ s[1] }}</span>
                            <button type="button" class="star-favorite" aria-pressed="true" aria-label="Hapus dari favorit" @click="remove(s[2], it.id)">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.6"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" /></svg>
                            </button>
                        </div>
                        <h3 class="star-title-with-icon"><span class="star-title-icon"><LineIcon :paths="KIND_ICON[s[3]]" /></span><span>{{ it.name }}</span></h3>
                        <p>{{ it.sub }}</p>
                        <span v-if="it.code" class="star-score">Kode minat {{ it.code }}</span>
                        <Go :href="it.url" class="star-detail">Lihat detail →</Go>
                    </article>
                    <p v-if="!$props[s[0]].length" class="lead" style="grid-column:1/-1">Belum ada {{ s[1].toLowerCase() }} yang disimpan. <Go :href="s[4]" class="v2-link">Jelajahi {{ s[1].toLowerCase() }} ↗</Go></p>
                </div>
            </template>
        </section>
    </div>
</template>
