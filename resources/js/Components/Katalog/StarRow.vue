<script setup>
// Baris di jendela detail (div.star-row v14).
import Go from '../Go.vue';
defineProps({ title: String, sub: String, href: String });
</script>

<template>
    <div class="star-row">
        <div>{{ title }}<small v-if="sub">{{ sub }}</small></div>
        <Go v-if="href" :href="href" class="btn">Detail</Go>
    </div>
</template>
