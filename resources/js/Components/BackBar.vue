<script setup>
// Tombol "Kembali ke Beranda" v14 (div.back-bar > button.btn-back).
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Go from './Go.vue';
import Icon from './Icon.vue';
import { homeUrl } from '../lib/routes';

const home = computed(() => homeUrl(usePage().props.auth?.user));
</script>

<template>
    <div class="back-bar"><Go :href="home" class="btn-back"><Icon name="back" /><span>Kembali ke Beranda</span></Go></div>
</template>
