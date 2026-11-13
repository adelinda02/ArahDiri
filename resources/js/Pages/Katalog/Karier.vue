<script setup>
import { computed, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import CatalogShell from '../../Components/Katalog/CatalogShell.vue';
import StarCard from '../../Components/Katalog/StarCard.vue';
import StarDialog from '../../Components/Katalog/StarDialog.vue';
import StarRow from '../../Components/Katalog/StarRow.vue';
import RiasecLine from '../../Components/Katalog/RiasecLine.vue';

const props = defineProps({ items: Array, total: Number, page: Number, pages: Number, filters: Object, favorites: Object, groups: Object, detail: Object });
const shell = ref(null);
const user = computed(() => usePage().props.auth.user);
const fav = (id) => (props.favorites.career ?? []).includes(id);
const MINAT = { R: 'Realistis', I: 'Investigatif', A: 'Artistik', S: 'Sosial', E: 'Enterprising', C: 'Konvensional' };
const open = (id) => router.get(`/karier/${id}`, shell.value.query(), { only: ['detail'], preserveState: true, preserveScroll: true });
const close = () => props.detail && router.get('/karier', shell.value.query(), { only: ['detail'], preserveState: true, preserveScroll: true });
</script>

<template>
    <Head :title="detail ? detail.name : 'Katalog Karier'" />
    <CatalogShell ref="shell" base="/karier" title="Katalog Karier & Profesi" intro="Jabatan menurut Klasifikasi Baku Jabatan Indonesia (KBJI) 2026 dari BPS dan Kemnaker." notice="Profil minat jabatan dipadankan dari O*NET 31.0; sebagian padanan masih draf dan belum ditelaah peneliti." :filters="filters" :total="total" :page="page" :pages="pages">
        <template #filters="{ f, apply }">
            <select v-model="f.golongan" aria-label="Filter golongan pokok" @change="apply()"><option :value="null">Semua golongan</option><option v-for="(n, k) in groups" :key="k" :value="k">{{ n }}</option></select>
            <select v-model="f.minat" aria-label="Filter minat utama" @change="apply()"><option :value="null">Semua minat utama</option><option v-for="(n, k) in MINAT" :key="k" :value="k">{{ n }}</option></select>
        </template>
        <StarCard v-for="it in items" :key="it.id" kind="career" :id="it.id" label="Karier" :title="it.name" :sub="`KBJI ${it.id} · ${it.subgroup_title ?? ''}`" :score="it.holland_code ? 'Kode minat ' + it.holland_code : ''" :fav="fav(it.id)" :logged-in="!!user" @detail="open" />
    </CatalogShell>
    <StarDialog :open="!!detail" :title="detail?.name" @close="close">
        <template v-if="detail">
            <p>{{ detail.meta }}</p>
            <p v-if="detail.official"><small>Nama resmi: {{ detail.official }}</small></p>
            <p class="star-notice">{{ detail.status }}</p>
            <template v-if="detail.description"><h3>Uraian tugas</h3><p class="catalog-desc">{{ detail.description }}</p></template>
            <p v-if="detail.page"><small>Sumber: {{ detail.page }}</small></p>
            <template v-if="detail.skills?.length"><h3>Skill terkait</h3><p v-for="k in detail.skills" :key="k.title" class="catalog-desc"><b>{{ k.title }}</b><template v-if="k.body"><br>{{ k.body }}</template></p></template>
            <template v-if="detail.riasec"><h3>Profil minat</h3><RiasecLine :v="detail.riasec" /></template>
            <template v-if="detail.programs.length"><h3>Program studi terkait</h3><StarRow v-for="p in detail.programs" :key="p.id" :title="p.title" :sub="p.sub" :href="`/jurusan/${p.id}`" /></template>
            <template v-if="detail.smk.length"><h3>Program keahlian SMK terkait</h3><StarRow v-for="p in detail.smk" :key="p.id" :title="p.title" :sub="p.sub" href="/program-smk" /></template>
        </template>
    </StarDialog>
</template>
