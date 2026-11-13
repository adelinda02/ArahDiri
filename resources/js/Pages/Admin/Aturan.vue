<script setup>
// Aturan rekomendasi yang bisa diubah tanpa menyentuh kode.
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import Topline from '../../Components/Admin/Topline.vue';

defineOptions({ layout: AdminLayout });
defineProps({ rules: Array, religiousWords: Array, hiddenCounts: Object, engineVersion: String });
const set = (r) => router.post('/admin/aturan', { key: r.key, value: !r.value }, { preserveScroll: true });
</script>

<template>
    <Head title="Admin · Aturan rekomendasi" />
    <Topline title="Aturan rekomendasi" desc="Aturan yang menyaring hasil rekomendasi. Perubahan berlaku untuk tes yang selesai setelah disimpan; hasil lama tidak berubah. Cek dampaknya di simulator.">
        <Link as="button" href="/admin/simulator">Buka simulator</Link>
    </Topline>
    <div class="table-wrap" style="margin-bottom:20px"><table>
        <thead><tr><th>Aturan</th><th>Status</th><th>Tindakan</th></tr></thead>
        <tbody><tr v-for="r in rules" :key="r.key">
            <td style="max-width:640px"><strong>{{ r.label }}</strong><small>Bawaan: {{ r.default ? 'aktif' : 'nonaktif' }}</small>
                <details v-if="r.key === 'recommend.exclude_religious'" class="rule-pattern"><summary>Kata yang dikenali sebagai khusus agama</summary><p>{{ religiousWords.join(', ') }}</p><p>Prodi atau profesi yang namanya mengandung salah satu kata ini tidak direkomendasikan (tetap ada di katalog).</p></details></td>
            <td><span class="badge" :class="{ hidden: !r.value }">{{ r.value ? 'Aktif' : 'Nonaktif' }}</span></td>
            <td class="actions"><button type="button" @click="set(r)">{{ r.value ? 'Nonaktifkan' : 'Aktifkan' }}</button></td>
        </tr></tbody>
    </table></div>
    <section class="panel">
        <h2 style="margin-top:0">Penyaring lain</h2>
        <p>Item yang disembunyikan di <Link href="/admin/katalog">Katalog</Link> juga tidak direkomendasikan: <b>{{ hiddenCounts.prodi }}</b> prodi, <b>{{ hiddenCounts.karier }}</b> karier, <b>{{ hiddenCounts.kampus }}</b> kampus.</p>
        <p>Mapel pendukung prodi dan kata kunci SMK → prodi disunting per item di Katalog (jenis Prodi dan Program SMK). Versi mesin: <code>{{ engineVersion }}</code>.</p>
    </section>
</template>

<style scoped>
.rule-pattern { margin-top: 6px; font-size: 12px; color: var(--muted); }
.rule-pattern summary { cursor: pointer; font-weight: 600; }
.rule-pattern p { margin: 6px 0 0; line-height: 1.6; }
</style>
