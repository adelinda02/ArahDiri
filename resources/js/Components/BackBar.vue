<script setup>
// Tombol kembali v14 (div.back-bar > button.btn-back).
// Bawaan: "Kembali ke Beranda". Dengan prop `back`: "Kembali" ke halaman sebelumnya
// (dibuka langsung tanpa riwayat → ke `fallback`, bawaan Beranda).
import { computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import Go from './Go.vue';
import Icon from './Icon.vue';
import { homeUrl } from '../lib/routes';

const props = defineProps({ back: Boolean, fallback: String });
const home = computed(() => homeUrl(usePage().props.auth?.user));
function goBack() {
    if (window.history.length > 1) window.history.back();
    else router.visit(props.fallback || home.value);
}
</script>

<template>
    <div class="back-bar">
        <button v-if="back" type="button" class="btn-back" title="Kembali ke halaman sebelumnya" @click="goBack"><Icon name="back" /><span>Kembali</span></button>
        <Go v-else :href="home" class="btn-back"><Icon name="back" /><span>Kembali ke Beranda</span></Go>
    </div>
</template>
