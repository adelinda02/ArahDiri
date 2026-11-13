<script setup>
import { computed, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import CatalogShell from '../../Components/Katalog/CatalogShell.vue';
import StarCard from '../../Components/Katalog/StarCard.vue';
import StarDialog from '../../Components/Katalog/StarDialog.vue';
import StarRow from '../../Components/Katalog/StarRow.vue';
import { tanggal } from '../../lib/format';

const props = defineProps({ items: Array, total: Number, page: Number, pages: Number, filters: Object, favorites: Object, provinces: Array, statuses: Array, detail: Object, accreditation_date: String });
const shell = ref(null);
const user = computed(() => usePage().props.auth.user);
const fav = (id) => (props.favorites.campus ?? []).includes(id);
const open = (id) => router.get(`/kampus/${id}`, shell.value.query(), { only: ['detail'], preserveState: true, preserveScroll: true });
const close = () => props.detail && router.get('/kampus', shell.value.query(), { only: ['detail'], preserveState: true, preserveScroll: true });
const notice = computed(() => 'Akreditasi menurut Direktori BAN-PT' + (props.accreditation_date ? ` per ${tanggal(props.accreditation_date)}` : ' (belum disinkron)') + '. Selalu cek situs resmi kampus.');
const url = (w) => (w.startsWith('http') ? w : 'https://' + w);
</script>

<template>
    <Head :title="detail ? detail.name : 'Katalog Kampus'" />
    <CatalogShell ref="shell" base="/kampus" title="Katalog Kampus" intro="Perguruan tinggi seluruh Indonesia dari katalog PDDikti beserta akreditasinya." :notice="notice" :filters="filters" :total="total" :page="page" :pages="pages">
        <template #filters="{ f, apply }">
            <select v-model="f.provinsi" aria-label="Filter provinsi" @change="apply()"><option :value="null">Semua provinsi</option><option v-for="x in provinces" :key="x" :value="x">{{ x }}</option></select>
            <select v-model="f.status" aria-label="Filter status" @change="apply()"><option :value="null">Semua status</option><option v-for="x in statuses" :key="x" :value="x">{{ x }}</option></select>
        </template>
        <StarCard v-for="it in items" :key="it.id" kind="campus" :id="it.id" label="Kampus" :title="it.name" :sub="[it.ownership, it.city, it.province].filter(Boolean).join(' · ')" :score="it.rating_score > 0 ? 'Akreditasi ' + it.rating_raw : ''" :fav="fav(it.id)" :logged-in="!!user" @detail="open" />
    </CatalogShell>
    <StarDialog :open="!!detail" :title="detail?.name" @close="close">
        <template v-if="detail">
            <p>{{ detail.meta }}</p>
            <p>Akreditasi institusi: <b>{{ detail.accreditation ? detail.accreditation.rating + (detail.accreditation.active ? '' : ' (tidak berlaku)') : 'belum ada data' }}</b>
                <template v-if="detail.accreditation?.expires_at"> · berlaku s.d. {{ tanggal(detail.accreditation.expires_at) }}</template>
                <br v-if="detail.accreditation?.decree_no"><small v-if="detail.accreditation?.decree_no">SK {{ detail.accreditation.decree_no }}</small></p>
            <p v-if="detail.website"><a :href="url(detail.website)" target="_blank" rel="noopener">Situs resmi ↗</a></p>
            <h3>Program studi ({{ detail.programs.length }})</h3>
            <StarRow v-for="(p, i) in detail.programs" :key="i" :title="p.title" :sub="p.sub" :href="p.id ? `/jurusan/${p.id}` : null" />
        </template>
    </StarDialog>
</template>
