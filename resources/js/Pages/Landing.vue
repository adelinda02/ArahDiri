<script setup>
// Halaman awal v14 (vLanding). Judul dan deskripsi hero bisa diubah admin (content_items: hero-title, hero-description).
import { computed } from 'vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import Go from '../Components/Go.vue';
import { VIEW_URL } from '../lib/routes';

const props = defineProps({
    copy: { type: Object, default: () => ({}) },
});
const user = computed(() => usePage().props.auth?.user ?? null);
const target = computed(() => (user.value ? VIEW_URL.beranda : VIEW_URL.login));
const heroTitle = computed(() => props.copy['hero-title'] || 'Masa depanmu. Dengan caramu.');
const heroDesc = computed(() => props.copy['hero-description'] || 'Kenali minatmu, jelajahi pilihan jurusan dan karier, lalu tentukan langkah berikutnya. Satu per satu, bersama ArahDiri.');

const projects = [
    [VIEW_URL.jurusan, '01', 'Jurusan yang terasa kamu.', 'Eksplorasi bidang studi', 'Kenali apa yang dipelajari, keterampilan, dan peluangnya.', 'photo-1517694712202-14dd9538aa97'],
    [VIEW_URL.karier, '02', 'Bayangkan masa depanmu.', 'Pilihan karier', 'Temukan profesi yang selaras dengan minat dan potensimu.', 'photo-1504384308090-c894fdcc538d'],
    [VIEW_URL.kampus, '03', 'Temukan tempat bertumbuh.', 'Pilihan kampus', 'Jelajahi kampus dan lingkungan belajar untuk langkah berikutnya.', 'photo-1523050854058-8df90110c9f1'],
];
const types = [['R', 'Realistic'], ['I', 'Investigative'], ['A', 'Artistic'], ['S', 'Social'], ['E', 'Enterprising'], ['C', 'Conventional']];

function scrollTo(sel) {
    document.querySelector(sel)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
</script>

<template>
    <Head title="Temukan arahmu" />
    <div class="ed-shell">
        <section class="v2-hero">
            <div class="v2-hero-copy">
                <div class="ed-kicker">KENALI DIRI · TEMUKAN ARAH</div>
                <h1>{{ heroTitle }}</h1>
                <p>{{ heroDesc }}</p>
                <div class="v2-actions">
                    <Go :href="target" class="ed-button">{{ user ? 'Lanjut ke beranda' : 'Mulai kenali dirimu' }} </Go>
                    <button type="button" class="v2-link" @click="scrollTo('#landing-features')">Jelajahi dulu <span>↓</span></button>
                </div>
                <div class="v2-hero-meta"><span><b>50</b> pertanyaan</span><span><b>6</b> dimensi minat</span><span>Tanpa batas waktu</span></div>
            </div>
            <div class="v2-hero-photo">
                <img src="https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=1000&q=85" alt="Mahasiswa berdiskusi dan belajar bersama" fetchpriority="high">
                <div class="v2-photo-top"><span>PERJALANANMU DIMULAI DI SINI</span></div>
                <div class="v2-photo-bottom"><span>01 / Mengenal diri</span><h2>Bukan harus jadi siapa.<br>Tapi ingin jadi apa.</h2></div>
            </div>
        </section>
        <div class="v2-audience"><span>UNTUK SETIAP LANGKAHMU</span><span>SMP</span><span>SMA / SMK</span><span>Mahasiswa</span><span>Masa depan punya banyak jalan ↗</span></div>

        <section class="ed-section" id="landing-features">
            <div class="ed-section-head">
                <div><div class="ed-kicker">Jelajahi kemungkinan</div><h2>Dunia luas.<br>Pilihanmu juga.</h2></div>
                <Go :href="VIEW_URL.jurusan" class="ed-text-link">Mulai eksplorasi ↗</Go>
            </div>
            <div class="ed-projects">
                <Go v-for="p in projects" :key="p[1]" :href="p[0]" class="ed-project">
                    <div class="ed-project-media"><img loading="lazy" :src="`https://images.unsplash.com/${p[5]}?auto=format&fit=crop&w=900&q=80`" :alt="p[3]"></div>
                    <div class="ed-project-line"><h3>{{ p[2] }}</h3><small>{{ p[1] }} / {{ p[3] }}</small></div>
                    <p>{{ p[4] }}</p>
                </Go>
            </div>
        </section>

        <section class="ed-section ed-process">
            <div>
                <div class="ed-kicker">Langkah kecil, arah yang jelas</div>
                <h2>Tidak perlu tahu<br>semuanya sekarang.</h2>
                <p>ArahDiri membantumu menghubungkan minat dengan pilihan pendidikan dan karier. Jalani satu langkah pada satu waktu.</p>
                <Go :href="target" class="ed-text-link">Temukan langkah pertamamu ↗</Go>
            </div>
            <div>
                <div class="ed-step"><span>01 /</span><div><h3>Kenali dirimu.</h3><p>Pilih jenjang pendidikanmu dan jawab pertanyaan tes berdasarkan hal yang benar-benar menarik bagimu.</p></div></div>
                <div class="ed-step"><span>02 /</span><div><h3>Lihat kemungkinanmu.</h3><p>Pelajari profil minat, rekomendasi jurusan, dan pilihan karier. Simpan yang menarik ke favorit.</p></div></div>
                <div class="ed-step"><span>03 /</span><div><h3>Wujudkan langkahmu.</h3><p>Eksplorasi jurusan dan kampus, lalu ikuti checklist pengembangan diri. Riwayat membantu melihat perjalananmu.</p></div></div>
            </div>
        </section>

        <section class="ed-riasec" id="landing-riasec">
            <div>
                <div class="ed-kicker">Kenalan dengan RIASEC</div>
                <h2>Kamu lebih dari<br>satu tipe.</h2>
                <p>Enam dimensi minat Holland membantu membaca aktivitas yang kamu sukai. Kombinasinya menjadi titik awal untuk mengeksplorasi pilihanmu—bukan batasan masa depanmu.</p>
                <Go :href="VIEW_URL.tes" class="ed-text-link">Lihat panduan tes ↗</Go>
            </div>
            <div class="ed-types">
                <div v-for="t in types" :key="t[0]" class="ed-type"><b>{{ t[0] }}</b><span>{{ t[1] }}</span></div>
            </div>
        </section>

        <section class="ed-cta">
            <div><h2>Arah berikutnya,<br>dimulai dari kamu.</h2><p>Ambil waktu sejenak. Kenali potensimu lebih dekat.</p></div>
            <Go :href="target" class="ed-button">Temukan arahku </Go>
        </section>

        <footer class="ed-footer">
            <strong>ArahDiri ↗</strong>
            <span>Panduan penjurusan &amp; karier</span>
            <span>© 2026 ArahDiri ·  · <Go :href="VIEW_URL.referensi" class="v2-link">Panduan &amp; referensi</Go></span>
        </footer>
    </div>
</template>
