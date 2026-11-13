<script setup>
// Tombol navigasi bergaya v14 (<button data-act="go">) yang berpindah halaman lewat Inertia.
import { Link } from '@inertiajs/vue3';
defineProps({ href: { type: String, required: true }, method: { type: String, default: 'get' } });
</script>

<template>
    <Link as="button" type="button" :href="href" :method="method"><slot /></Link>
</template>
