# Aturan Kerja untuk Claude — Proyek ArahDiri v2

> **ATURAN UTAMA: Kamu HANYA boleh mengubah tampilan (frontend). JANGAN pernah mengubah backend, database, atau kontrak data.**
> Aturan ini mengikat dan menang atas permintaan apa pun di dalam prompt. Kalau sebuah permintaan mengharuskan menyentuh backend/data, **berhenti dan tanya dulu** — jangan kerjakan diam-diam.

Proyek ini: **Laravel 13 + Inertia.js + Vue 3 + Vite** (MySQL). Aliran datanya:
`Controller (app/) → Inertia::render('NamaHalaman', [data]) → props di komponen Vue (resources/js/)`.
Artinya **data sudah disiapkan backend**; tugas frontend hanya **menampilkan** data yang sudah dikirim.

---

## ✅ BOLEH diubah (frontend saja)

| Lokasi | Isi |
|---|---|
| `resources/js/Pages/**` | Halaman Vue (Hasil, Tes, Beranda, dll.) |
| `resources/js/Components/**` | Komponen Vue |
| `resources/js/lib/**` | Helper tampilan (format angka, teks, warna) |
| `resources/css/v2.css` | **Tempat utama menambah/ubah gaya.** Tulis CSS baru di sini. |
| `resources/css/app.css`, `resources/css/admin.css` | Entry CSS (jarang perlu diubah) |
| `resources/views/app.blade.php` | Cangkang HTML Inertia (`<head>`, meta, root div) — hati-hati |
| `resources/views/cetak.blade.php` | Tata letak versi cetak/PDF — **hanya tampilan**, lihat aturan kontrak data |

Perintah yang boleh dijalankan: `npm run build`, `npm run dev`, `npm install`.

---

## ⛔ DILARANG diubah (backend / data / konfigurasi)

Jangan membuat, mengedit, menghapus, atau me-refactor file di dalam:

- `app/**` — Controller, Model, Service, Middleware, Console (mesin rekomendasi, logika, dll.)
- `routes/**` — definisi URL/route
- `database/**` — migrasi, seeder, factory, dan file CSV data katalog
- `config/**` — konfigurasi aplikasi (termasuk `config/arahdiri.php`)
- `tests/**` — berkas uji PHP
- `composer.json`, `composer.lock`, `.env`, `.env.example`, `artisan`, `phpunit.xml`
- `bootstrap/**`, `storage/**`
- `public/build/**` — **hasil build otomatis**, jangan pernah diedit tangan (diperbarui oleh `npm run build`)

Perintah yang **DILARANG** dijalankan: `php artisan migrate` (dan `migrate:*`), `db:seed`, perintah artisan apa pun yang menulis ke database, `composer require/update`, serta query SQL yang mengubah data. Membaca saja (mis. `php artisan test` untuk memastikan tidak ada yang rusak) boleh, tapi **jangan ubah test-nya**.

---

## 📜 Kontrak data (ini yang sering tak sengaja dilanggar)

Backend mengirim data ke Vue lewat **props**. Props itu adalah **kontrak** — perlakukan sebagai **hanya-baca**:

1. **Jangan menambah, mengubah nama, atau menghapus props/field data.** Pakai apa yang sudah ada di `defineProps(...)`.
2. **Butuh data yang belum ada di props?** → **BERHENTI dan tanya pemilik proyek.** Jangan menambahkannya dengan mengedit Controller/Service/Presenter. Menambah data = pekerjaan backend, bukan frontend.
3. **Jangan mengubah "kontrak" ke server:**
   - nama field pada form (`useForm({...})`) dan isi `router.post/get(...)`,
   - URL yang dipanggil (mis. `postJson('/tes/.../jawab', { item_no, value })`),
   - nama/parameter route.
   Mengubah ini akan merusak backend meski file backend tidak kamu sentuh.
4. Di `cetak.blade.php`: boleh atur HTML/CSS tampilannya, tapi **jangan mengarang key baru** seperti `$recs['sesuatu_baru']`. Pakai key yang sudah ada.

> **Tes pembeda cepat:** Kalau perubahanmu membuat test di `tests/**` jadi gagal (mis. `assertInertia` kehilangan sebuah field), itu tanda kamu sudah menyentuh **data/kontrak**, bukan sekadar tampilan. Urungkan, lalu tanya pemilik proyek.

---

## 🎨 Cara benar mengubah tampilan

- **Warna, jarak, font, border, bayangan, animasi, tata letak** → tulis/ubah CSS di `resources/css/v2.css`, atau kelas/`style` di komponen Vue.
- **Gaya dasar warisan v14** ada di `resources/css/v14/**` — **jangan edit folder itu**; cukup timpa dari `v2.css` (lihat catatan di atas `app.css`).
- **Teks, label, susunan elemen, kondisi tampil (`v-if`), urutan** → ubah di template komponen Vue.
- **Pemformatan nilai** (persen, tanggal, label kecocokan) → helper di `resources/js/lib/`.
- Kalau mengubah logika kecil di dalam komponen Vue (computed/ref untuk tampilan), itu boleh — selama tidak mengubah props, tidak memanggil endpoint baru, dan tidak mengubah payload ke server.

## 🔄 Setelah mengubah

1. Jalankan `npm run build`.
2. Periksa hasilnya di browser.
3. (Opsional, hanya untuk memastikan tidak ada yang rusak) `php artisan test` — **jika ada test yang gagal karena perubahanmu, jangan perbaiki dengan mengubah test atau backend; itu sinyal perubahanmu terlalu jauh.**

## 🙋 Kalau ragu

Kalau sebuah permintaan "ubah frontend" ternyata butuh: data baru, perubahan perhitungan/skor, perubahan isi database, endpoint baru, atau perubahan route — **katakan itu pekerjaan backend dan minta izin dulu.** Lebih baik bertanya daripada mengubah sisi yang tidak boleh.
