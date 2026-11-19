<script setup>
// Kebijakan privasi, ditulis untuk siswa dan orang tua.
import { Head } from '@inertiajs/vue3';
import BackBar from '../Components/BackBar.vue';
</script>

<template>
    <Head title="Kebijakan Privasi" />
    <div class="wrap" style="padding-top:24px">
        <BackBar />
        <section class="star-catalog info-page">
            <span class="v2-label">KEBIJAKAN PRIVASI</span>
            <h1>Data kamu, untuk kamu</h1>
            <p>Halaman ini menjelaskan data apa yang disimpan ArahDiri, untuk apa, siapa yang bisa melihatnya, dan bagaimana menghapusnya. Ditulis agar mudah dibaca siswa dan orang tua.</p>

            <h2>Data yang kami simpan</h2>
            <ul class="klist">
                <li><b>Akun</b><br><span>Nama tampilan, nama pengguna, email, nomor HP, jenjang, dan kata sandi (disimpan teracak, tidak bisa dibaca siapa pun).</span></li>
                <li><b>Profil (opsional)</b><br><span>Asal sekolah, bio singkat, dan karakter avatar. Kami tidak menyimpan foto.</span></li>
                <li><b>Mahasiswa</b><br><span>Kampus, prodi, dan semester yang kamu pilih sendiri, tanpa verifikasi.</span></li>
                <li><b>Tes dan hasil</b><br><span>Jawaban 50 pernyataan, skor, kode minat, rekomendasi, serta salinan akreditasi saat tes selesai.</span></li>
                <li><b>Pilihanmu</b><br><span>Favorit dan target pengembangan diri.</span></li>
            </ul>
            <p>Kami <b>tidak</b> meminta tanggal lahir, alamat, agama, foto, atau dokumen identitas.</p>

            <h2>Untuk apa data dipakai</h2>
            <p>Hanya untuk menghitung profil minat, menyusun rekomendasi, menyimpan riwayatmu, dan menjaga keamanan akun. Data tidak dijual atau dibagikan untuk iklan.</p>

            <h2>Siapa yang bisa melihat</h2>
            <ul class="klist">
                <li><b>Kamu</b><br><span>Semua data milikmu.</span></li>
                <li><b>Admin ArahDiri</b><br><span>Hanya angka gabungan, misalnya jumlah tes per jenjang dan sebaran kode minat.</span></li>
                <li><b>Pemilik aplikasi</b><br><span>Data per orang hanya bila diperlukan (misalnya menangani laporan), dan setiap aksesnya tercatat.</span></li>
                <li><b>Penelitian</b><br><span>Data yang diekspor memakai ID acak, tanpa nama, email, atau nomor HP.</span></li>
            </ul>

            <h2>Hak dan kendalimu</h2>
            <p>Lewat menu Profil kamu bisa mengubah data, mengganti kata sandi, keluar dari semua perangkat, menghapus riwayat tes, atau menghapus akun beserta seluruh hasil tes. Penghapusan bersifat permanen.</p>

            <h2>Untuk orang tua dan wali</h2>
            <p>Banyak pengguna ArahDiri masih di bawah umur. Kami menganjurkan orang tua atau wali mendampingi anak saat mendaftar dan membaca hasil bersama guru BK. Hasil tes adalah gambaran minat, bukan penilaian kemampuan.</p>

            <h2>Keamanan</h2>
            <p>Koneksi memakai HTTPS, kata sandi diacak, dan percobaan masuk dibatasi. Tidak ada sistem yang sepenuhnya bebas risiko, jadi gunakan kata sandi yang unik.</p>
        </section>
    </div>
</template>
