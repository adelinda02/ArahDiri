<script setup>
// Kerangka katalog v14 (section.star-catalog): judul, saringan, jumlah, kisi kartu, halaman.
import { reactive, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import BackBar from '../BackBar.vue';

const props = defineProps({
    base: { type: String, required: true }, // mis. /jurusan
    title: String, intro: String, notice: String,
    filters: { type: Object, required: true },
    total: Number, page: Number, pages: Number,
});
const f = reactive({ ...props.filters });
let timer;
function apply(delay = 0) {
    clearTimeout(timer);
    timer = setTimeout(() => {
        const q = Object.fromEntries(Object.entries(f).filter(([, v]) => v !== null && v !== ''));
        router.get(props.base, q, { preserveState: true, preserveScroll: true, replace: true });
    }, delay);
}
watch(() => f.q, () => apply(350));
function go(p) {
    const q = Object.fromEntries(Object.entries(f).filter(([, v]) => v !== null && v !== ''));
    router.get(props.base, { ...q, page: p }, { preserveState: true });
    document.querySelector('.star-catalog')?.scrollIntoView({ block: 'start' });
}
defineExpose({ query: () => Object.fromEntries(Object.entries(f).filter(([, v]) => v !== null && v !== '')) });
</script>

<template>
    <div class="wrap" style="padding-top:24px">
        <BackBar />
        <section class="star-catalog">
            <span class="v2-label">EKSPLORASI</span>
            <h1>{{ title }}</h1>
            <p>{{ intro }}</p>
            <p v-if="notice" class="star-notice">{{ notice }}</p>
            <div class="star-filters">
                <input v-model="f.q" aria-label="Cari dalam katalog" placeholder="Cari nama atau kode…">
                <slot name="filters" :f="f" :apply="apply" />
            </div>
            <p>{{ total.toLocaleString('id-ID') }} pilihan</p>
            <div class="star-grid"><slot /></div>
            <div v-if="pages > 1" class="star-pagination">
                <button v-if="page > 1" type="button" class="btn" @click="go(page - 1)">← Sebelumnya</button>
                <span>Halaman {{ page }} dari {{ pages }}</span>
                <button v-if="page < pages" type="button" class="btn" @click="go(page + 1)">Berikutnya →</button>
            </div>
        </section>
        <slot name="after" />
    </div>
</template>
