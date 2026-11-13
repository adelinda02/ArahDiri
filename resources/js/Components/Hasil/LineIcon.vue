<script setup>
defineProps({ paths: { type: String, required: true } });
</script>

<template>
    <svg class="arah-line-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" v-html="paths"></svg>
</template>
