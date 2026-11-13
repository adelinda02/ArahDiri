<script setup>
// Panduan, metode, dan sumber data ArahDiri.
import { Head } from '@inertiajs/vue3';
import BackBar from '../Components/BackBar.vue';
import { tanggal } from '../lib/format';

defineProps({ stats: Object, dates: Object, content: { type: Object, default: () => ({ info: [], scopes: [], skills: [], references: [] }) } });
const n = (x) => (x ?? 0).toLocaleString('id-ID');
</script>

<template>
    <Head title="Panduan & Referensi" />
    <div class="wrap" style="padding-top:24px">
        <BackBar />
        <section class="star-catalog info-page">
            <span class="v2-label">PANDUAN &amp; REFERENSI</span>
            <h1>Cara ArahDiri bekerja</h1>
            <p>ArahDiri menghubungkan minatmu dengan pilihan sekolah, prodi, kampus, dan karier. Berikut metode, sumber data, dan batasannya.</p>

            <h2>1. Tes minat RIASEC</h2>
            <p>50 pernyataan tentang aktivitas yang kamu sukai, dinilai 1–5. Skor tiap dimensi Holland (Realistic, Investigative, Artistic, Social, Enterprising, Conventional) adalah jumlah jawaban dibagi skor maksimal dimensinya. Tiga dimensi tertinggi menjadi kode minatmu.</p>

            <h2>2. Cara rekomendasi disusun</h2>
            <ul class="klist">
                <li><b>Keselarasan</b><br><span>Separuh dari kemiripan profil enam dimensi (kosinus) dan separuh dari kecocokan tiga minat teratas. Ini heuristik, bukan peluang diterima.</span></li>
                <li><b>SMP</b><br><span>3 profesi yang paling cocok, lalu jalur mundurnya: arah SMA (dari mapel pendukung prodi pada SNBP) atau program keahlian SMK, kemudian prodi kuliah yang mengarah ke profesi itu.</span></li>
                <li><b>SMA/SMK</b><br><span>3 prodi S1/D4/D3 dengan karier utama berbeda, masing-masing 1 kampus berbeda. Kampus dipilih dari akreditasi prodi, akreditasi kampus, PTN/PTKN lebih dulu, masa berlaku akreditasi, lalu nama.</span></li>
                <li><b>Mahasiswa UMB</b><br><span>Peringkat prodimu di antara prodi UMB sejenjang, 3 prodi UMB paling selaras, dan karier dari prodimu sekarang.</span></li>
                <li><b>Prodi khusus agama</b><br><span>Tidak direkomendasikan otomatis karena ArahDiri tidak menanyakan agama, tetapi tetap ada di katalog.</span></li>
            </ul>

            <h2>3. Sumber data</h2>
            <ul class="klist">
                <li><b>Klasifikasi Baku Jabatan Indonesia (KBJI) 2026 Vol. 1, BPS–Kemnaker</b><br><span>{{ n(stats.jobs) }} jabatan; {{ n(stats.jobs_riasec) }} sudah punya profil minat.</span></li>
                <li><b>O*NET 31.0 (Career Interest Profiles)</b><br><span>Profil minat skala 1–7 yang dipadankan ke KBJI.</span></li>
                <li><b>Katalog Perguruan Tinggi &amp; Program Studi PDDikti</b><br><span>{{ n(stats.campuses) }} perguruan tinggi, {{ n(stats.offerings) }} program studi, {{ n(stats.families) }} kelompok prodi.<template v-if="dates.catalog"> Diimpor {{ tanggal(dates.catalog) }}.</template></span></li>
                <li><b>Direktori Akreditasi BAN-PT (termasuk SK LAM)</b><br><span>{{ n(stats.accredited) }} akreditasi berlaku.<template v-if="dates.banpt"> Disinkron {{ tanggal(dates.banpt) }}.</template><template v-else> Belum disinkron.</template></span></li>
                <li><b>Spektrum Keahlian SMK/MAK, Kepmendikbudristek 244/M/2024</b><br><span>{{ n(stats.smk) }} program keahlian.</span></li>
                <li><b>Mata pelajaran pendukung program studi pada SNBP</b><br><span>Kepmendikdasmen 102/M/2025 (29 Juli 2025, mencabut Kepmendikbudristek 345/M/2022); dipakai untuk menyambungkan arah SMA dengan prodi. Bagi lulusan SMK, nilai Dasar-dasar Program Keahlian dan Projek Kreatif dan Kewirausahaan juga ikut dinilai (Diktum KETIGA).</span></li>
                <li v-for="r in content.references" :key="r.id"><b><a v-if="r.meta?.url" :href="r.meta.url" target="_blank" rel="noopener">{{ r.title }}</a><template v-else>{{ r.title }}</template></b><br v-if="r.body"><span v-if="r.body">{{ r.body }}</span></li>
            </ul>

            <template v-if="content.scopes.length">
                <h2>Bidang minat</h2>
                <ul class="klist"><li v-for="x in content.scopes" :key="x.id"><b>{{ x.title }}</b><br v-if="x.body"><span v-if="x.body">{{ x.body }}</span></li></ul>
            </template>
            <template v-if="content.skills.length">
                <h2>Skill untuk dikembangkan</h2>
                <ul class="klist"><li v-for="x in content.skills" :key="x.id"><b>{{ x.title }}</b><br v-if="x.body"><span v-if="x.body">{{ x.body }}</span></li></ul>
            </template>
            <template v-for="x in content.info" :key="x.id">
                <h2>{{ x.title }}</h2>
                <p class="catalog-desc">{{ x.body }}</p>
            </template>

            <h2>4. Batasan</h2>
            <ul class="klist">
                <li><b>Padanan draf</b><br><span>Sebagian padanan prodi–karier dan KBJI–O*NET dibuat dengan bantuan AI dan belum ditelaah per baris.</span></li>
                <li><b>Arah SMA</b><br><span>Penjurusan SMA direncanakan kembali tahun 2027; arah IPA/IPS/Bahasa disesuaikan setelah aturan terbit.</span></li>
                <li><b>Data berubah</b><br><span>Kuota, jadwal, biaya, dan syarat masuk berubah setiap tahun. Selalu cek situs resmi kampus dan sekolah.</span></li>
                <li><b>Bukan pengganti pendamping</b><br><span>Gunakan hasil bersama guru BK, konselor, dosen PA, atau orang tua.</span></li>
            </ul>
        </section>
    </div>
</template>
