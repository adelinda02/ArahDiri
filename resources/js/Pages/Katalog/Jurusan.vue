<script setup>
import { computed, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import CatalogShell from '../../Components/Katalog/CatalogShell.vue';
import StarCard from '../../Components/Katalog/StarCard.vue';
import StarDialog from '../../Components/Katalog/StarDialog.vue';
import StarRow from '../../Components/Katalog/StarRow.vue';
import RiasecLine from '../../Components/Katalog/RiasecLine.vue';

const props = defineProps({ items: Array, total: Number, page: Number, pages: Number, filters: Object, favorites: Object, fields: Array, levels: Array, detail: Object });
const shell = ref(null);
const user = computed(() => usePage().props.auth.user);
const fav = (id) => (props.favorites.program ?? []).includes(String(id));
const open = (id) => router.get(`/jurusan/${id}`, shell.value.query(), { only: ['detail'], preserveState: true, preserveScroll: true });
const close = () => props.detail && router.get('/jurusan', shell.value.query(), { only: ['detail'], preserveState: true, preserveScroll: true });
</script>

<template>
    <Head :title="detail ? detail.name : 'Katalog Jurusan'" />
    <CatalogShell ref="shell" base="/jurusan" title="Katalog Jurusan & Program Studi" intro="Jelajahi program studi dari katalog PDDikti beserta karier KBJI 2026 dan kampus penyelenggaranya." notice="Pemetaan prodi–karier adalah kandidat yang sebagian masih draf. Keselarasan profil bukan peluang diterima atau bukti kecocokan kurikulum." :filters="filters" :total="total" :page="page" :pages="pages">
        <template #filters="{ f, apply }">
            <select v-model="f.bidang" aria-label="Filter bidang" @change="apply()"><option :value="null">Semua bidang</option><option v-for="x in fields" :key="x" :value="x">{{ x }}</option></select>
            <select v-model="f.jenjang" aria-label="Filter jenjang" @change="apply()"><option :value="null">Semua jenjang</option><option v-for="x in levels" :key="x" :value="x">{{ x }}</option></select>
        </template>
        <StarCard v-for="it in items" :key="it.id" kind="program" :id="String(it.id)" label="Jurusan" :title="it.name" :sub="`${it.level} · ${it.campus_count} kampus dalam katalog`" :score="it.holland_code ? 'Kode minat ' + it.holland_code : ''" :fav="fav(it.id)" :logged-in="!!user" @detail="open" />
    </CatalogShell>
    <StarDialog :open="!!detail" :title="detail?.name" @close="close">
        <template v-if="detail">
            <p>{{ detail.meta }}</p>
            <p v-if="detail.description" class="catalog-desc">{{ detail.description }}</p>
            <p class="star-notice">Pemetaan kandidat; sebagian masih draf. Gunakan untuk eksplorasi awal.</p>
            <h3>Profil minat prodi</h3>
            <RiasecLine :v="detail.riasec" />
            <h3>Karier terkait (KBJI 2026)</h3>
            <StarRow v-for="c in detail.careers" :key="c.id" :title="c.title" :sub="c.sub" :href="`/karier/${c.id}`" />
            <h3>Kampus penyelenggara ({{ detail.campus_total }})</h3>
            <p v-if="detail.campus_total > detail.campuses.length" class="star-notice">Ditampilkan {{ detail.campuses.length }} kampus dengan akreditasi prodi tertinggi.</p>
            <StarRow v-for="c in detail.campuses" :key="c.id" :title="c.title" :sub="c.sub" :href="`/kampus/${c.id}`" />
        </template>
    </StarDialog>
</template>
