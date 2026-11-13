<script setup>
// Navigasi halaman untuk paginator Laravel.
import { router } from '@inertiajs/vue3';
defineProps({ p: Object, label: { type: String, default: 'baris' } });
</script>

<template>
    <div class="pagination">
        <span>{{ p.total.toLocaleString('id-ID') }} {{ label }} · Halaman {{ p.current_page }} dari {{ p.last_page || 1 }}</span>
        <div>
            <button v-if="p.prev_page_url" type="button" @click="router.visit(p.prev_page_url, { preserveScroll: true })">← Sebelumnya</button>
            <button v-if="p.next_page_url" type="button" @click="router.visit(p.next_page_url, { preserveScroll: true })">Berikutnya →</button>
        </div>
    </div>
</template>
