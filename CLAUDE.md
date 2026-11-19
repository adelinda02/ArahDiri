# ArahDiri v2

**ATURAN WAJIB: Hanya ubah frontend (tampilan). JANGAN ubah backend, database, atau kontrak data.**
Kalau sebuah permintaan butuh menyentuh backend/data, berhenti dan tanya dulu.

- **Boleh:** `resources/js/**`, `resources/css/v2.css` (dan entry CSS), `resources/views/app.blade.php`, `resources/views/cetak.blade.php`. Perintah: `npm run build` / `npm run dev`.
- **Dilarang:** `app/**`, `routes/**`, `database/**`, `config/**`, `tests/**`, `composer.*`, `.env`, `public/build/**`, dan perintah `php artisan migrate` / `db:seed` / composer.
- **Props Inertia = hanya-baca.** Jangan menambah/mengganti nama field data; jangan ubah URL endpoint, nama route, atau field form. Butuh data baru → minta izin, jangan edit backend.

Aturan lengkap dan alasannya ada di **@AGENTS.md** — baca itu sebelum mulai mengubah apa pun.
