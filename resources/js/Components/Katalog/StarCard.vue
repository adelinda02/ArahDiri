<script setup>
// Kartu katalog v14 (article.star-card) dengan tombol favorit.
import { router } from '@inertiajs/vue3';
import LineIcon from '../Hasil/LineIcon.vue';
import { KIND_ICON } from '../../lib/format';

const props = defineProps({
    kind: { type: String, required: true }, // program | career | campus
    id: { type: String, required: true },
    label: String, title: String, sub: String, score: String,
    fav: Boolean,
    loggedIn: Boolean,
});
const emit = defineEmits(['detail']);
const icon = { program: 'majors', career: 'careers', campus: 'campuses' }[props.kind];
function toggleFav() {
    if (!props.loggedIn) return router.visit('/masuk');
    router.post('/favorit', { kind: props.kind, target_id: props.id }, { preserveScroll: true, preserveState: true });
}
</script>

<template>
    <article class="star-card">
        <div class="star-card-top">
            <span>{{ label }}</span>
            <button type="button" class="star-favorite" :aria-pressed="fav ? 'true' : 'false'" :aria-label="fav ? 'Hapus dari favorit' : 'Simpan ke favorit'" @click="toggleFav">
                <svg width="20" height="20" viewBox="0 0 24 24" :fill="fav ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="1.6"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z" /></svg>
            </button>
        </div>
        <h3 class="star-title-with-icon"><span class="star-title-icon"><LineIcon :paths="KIND_ICON[icon]" /></span><span>{{ title }}</span></h3>
        <p>{{ sub }}</p>
        <span v-if="score" class="star-score">{{ score }}</span>
        <button type="button" class="star-detail" @click="emit('detail', id)">Lihat detail →</button>
    </article>
</template>
