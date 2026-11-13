<script setup>
// Beranda v14 (vMulai/"RUANG PERJALANANMU"): dua keadaan, sebelum dan sesudah tes.
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import Go from '../Components/Go.vue';
import { VIEW_URL } from '../lib/routes';

const props = defineProps({
    latest: { type: Object, default: null }, // { holland_code, completed_at }
    umbProgram: { type: Object, default: null }, // khusus mhs_umb: { name, level }
});
const user = computed(() => usePage().props.auth.user);
const isMhs = computed(() => user.value.level === 'mhs_umb');
</script>

<template>
    <Head title="Beranda" />
    <div class="v2-dashboard wrap">
        <div class="v2-dash-heading">
            <div>
                <div class="ed-kicker">RUANG PERJALANANMU</div>
                <h1>Halo, {{ user.name }}.</h1>
                <p>Langkah kecil hari ini, arah yang lebih jelas esok hari.</p>
            </div>
            <Go :href="VIEW_URL.profil" class="v2-link">Profil saya ↗</Go>
        </div>

        <div class="v2-dash-main">
            <section v-if="latest" class="v2-next">
                <span class="v2-label">HASIL PERJALANANMU</span>
                <h2>Kenali kombinasi<br>minatmu: {{ latest.holland_code }}</h2>
                <p v-if="isMhs">Hasil tesmu sudah tersimpan. Lihat seberapa cocok prodi {{ umbProgram?.name ?? 'UMB-mu' }} dengan minatmu.</p>
                <p v-else>Hasil tesmu sudah tersimpan. Gunakan sebagai panduan awal untuk mengeksplorasi pilihan.</p>
                <Go :href="VIEW_URL.hasil" class="ed-button">Lihat hasil minat </Go>
                <Go :href="VIEW_URL.tes + '?ulang=1'" class="ed-button ad-retest">Tes Ulang</Go>
            </section>
            <section v-else class="v2-next">
                <span class="v2-label">LANGKAH PERTAMA</span>
                <h2>Mulai dari<br>mengenal diri.</h2>
                <p v-if="isMhs">50 pertanyaan sederhana tentang aktivitas yang kamu sukai. Hasilnya membantumu menilai kecocokan dengan prodi {{ umbProgram?.name ?? 'UMB-mu' }}.</p>
                <p v-else>50 pertanyaan sederhana tentang aktivitas yang kamu sukai. Jawab dengan jujur, sesuai ritmemu.</p>
                <Go :href="VIEW_URL.tes" class="ed-button">Mulai tes minat </Go>
            </section>
        </div>

        <div class="v2-dash-section">
            <div class="ed-kicker">JELAJAHI PILIHAN</div>
            <h2>Mau mencari apa?</h2>
            <div class="v2-explore-grid">
                <Go :href="VIEW_URL.jurusan"><span class="v2-index">01 </span><h3>Jurusan</h3><p>Temukan bidang studi yang menarik bagimu.</p></Go>
                <Go :href="VIEW_URL.karier"><span class="v2-index">02 </span><h3>Karier</h3><p>Kenali profesi dan keterampilan yang dibutuhkan.</p></Go>
                <Go :href="VIEW_URL.kampus"><span class="v2-index">03 </span><h3>Kampus</h3><p>Jelajahi tempat untuk belajar dan berkembang.</p></Go>
            </div>
        </div>

        <div class="v2-shortcuts">
            <Go :href="VIEW_URL.langkah"><span>Rencana pengembangan</span><small>Susun langkah dan tandai progresmu</small><b>↗</b></Go>
            <Go :href="VIEW_URL.favorit"><span>Pilihan tersimpan</span><small>Lihat jurusan, karier, dan kampus favorit</small><b>↗</b></Go>
            <Go :href="VIEW_URL.riwayat"><span>Riwayat tes</span><small>Kembali ke hasil perjalanan sebelumnya</small><b>↗</b></Go>
        </div>
    </div>
</template>
