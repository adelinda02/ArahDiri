<script setup>
defineProps({ title: String, desc: String });
</script>

<template>
    <div class="topline">
        <div><span class="eyebrow">ARAH DIRI / ADMIN</span><h1>{{ title }}</h1><p>{{ desc }}</p></div>
        <slot />
    </div>
</template>
