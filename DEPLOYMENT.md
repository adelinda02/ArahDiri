# Deployment ke VPS dengan Docker

Berkas deployment berada di root: `Dockerfile`, `compose.yaml`, dan `compose.local.yaml`. GitHub Actions menjalankan tes pada push ke `main` atau `ci`; image dibuat dan dideploy hanya saat ada Git tag versi `vMAJOR.MINOR.PATCH`, misalnya `v1.0.0`.

## Arsitektur

Satu stack Compose memuat tiga service: `app` (Apache + PHP), `scheduler`, dan `mysql` (MySQL 8.4.13). Di VPS, container aplikasi hanya bind ke `127.0.0.1:8080`; Caddy menjadi pintu HTTPS. Port MySQL tidak dipublish ke host, sehingga hanya bisa diakses lewat network privat `arahdiri-backend`.

- `compose.yaml` dipakai di VPS. Image aplikasi diambil dari GitHub Container Registry (GHCR) lewat `APP_IMAGE` dan `APP_VERSION`.
- `compose.local.yaml` dipakai di komputer sendiri. Image aplikasi dibangun lokal dari `Dockerfile`.
- Konfigurasi dipisah dua file: `.env` untuk Laravel dan `.env.mysql` untuk container MySQL. Keduanya tidak ikut Git.
- Volume `app_storage` menyimpan storage Laravel dan `mysql_data` menyimpan data MySQL.

## Menjalankan di komputer sendiri

Stack lokal memakai `compose.local.yaml`; image aplikasi dibangun dari `Dockerfile` dan MySQL ikut dalam stack yang sama. Butuh Docker dengan Compose plugin.

Buat dua file konfigurasi di root proyek. Keduanya sudah masuk `.gitignore`; jangan pernah commit atau kirim isinya.

`.env` untuk Laravel:

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_KEY=base64:ISI_KEY_LOKAL
APP_URL=http://127.0.0.1:8080

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=arahdiri
DB_USERNAME=arahdiri
DB_PASSWORD=dev123

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=false
CACHE_STORE=database
QUEUE_CONNECTION=database
TRUSTED_PROXIES=127.0.0.1
MAIL_MAILER=log
```

`.env.mysql` untuk container MySQL:

```dotenv
MYSQL_ROOT_PASSWORD=root-lokal-kuat
MYSQL_DATABASE=arahdiri
MYSQL_USER=arahdiri
MYSQL_PASSWORD=dev123
```

Aturannya:

- `MYSQL_DATABASE` harus sama dengan `DB_DATABASE`, `MYSQL_USER` sama dengan `DB_USERNAME`, dan `MYSQL_PASSWORD` sama dengan `DB_PASSWORD`. Laravel dan MySQL adalah dua container berbeda, jadi nilainya harus dicocokkan manual.
- `DB_HOST=mysql` adalah nama service di Compose, bukan `127.0.0.1`. `127.0.0.1` di dalam container menunjuk container itu sendiri.
- `MYSQL_USER` tidak boleh `root`; image MySQL menolak nilai itu karena akun root diatur lewat `MYSQL_ROOT_PASSWORD`.
- Jalankan dengan `APP_ENV=local`. Mode production memaksa URL HTTPS, sedangkan Apache lokal hanya melayani HTTP di port 8080, sehingga aset gagal dimuat dengan `ERR_SSL_PROTOCOL_ERROR`.

Jalankan stack, migrasi, dan katalog:

```sh
docker compose -f compose.local.yaml up -d --build
docker compose -f compose.local.yaml exec app php artisan migrate --force
docker compose -f compose.local.yaml exec app php artisan catalog:import
```

Buka `http://127.0.0.1:8080`. Hentikan dengan `docker compose -f compose.local.yaml down`, atau `down -v` bila ingin menghapus data MySQL lokal juga.

`compose.local.yaml` tidak mengatur `APP_ENV`; nilai dari `.env` yang dipakai. Jika pernah mengubah `.env`, jalankan ulang `docker compose -f compose.local.yaml up -d --force-recreate app`.

## Siapkan VPS

Pasang Docker Engine dengan Compose plugin, Caddy, dan utilitas `git`/`curl`. Buat direktori untuk proyek dan network privat:

```sh
sudo mkdir -p /opt/arahdiri
sudo docker network create arahdiri-backend
```

Buat key aplikasi sekali sebelum rilis pertama:

```sh
printf 'base64:%s\n' "$(openssl rand -base64 32)"
```

Buat `/opt/arahdiri/.env.mysql` dengan kredensial MySQL yang kuat. Jangan commit atau kirim file ini ke tempat lain:

```dotenv
MYSQL_ROOT_PASSWORD=ganti-dengan-rahasia-panjang
MYSQL_DATABASE=arahdiri
MYSQL_USER=arahdiri
MYSQL_PASSWORD=ganti-dengan-rahasia-berbeda
```

Buat `/opt/arahdiri/.env` dari `.env.example`, lalu atur paling tidak. Isi `APP_KEY` dengan nilai yang dicetak:

```dotenv
APP_ENV=production
APP_KEY=base64:ISI_DENGAN_KEY_YANG_DIBUAT_SEKALI
APP_DEBUG=false
APP_URL=https://domain-anda

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=arahdiri
DB_USERNAME=arahdiri
DB_PASSWORD=samakan-dengan-MYSQL_PASSWORD

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
SESSION_SECURE_COOKIE=true
TRUSTED_PROXIES=*
MAIL_MAILER=smtp
```

`DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` di `.env` harus sama dengan `MYSQL_DATABASE`, `MYSQL_USER`, dan `MYSQL_PASSWORD` di `.env.mysql`. Password root MySQL hanya dipakai container MySQL; Laravel memakai akun aplikasi `arahdiri`.

Image GHCR private memerlukan login pada VPS. Buat GitHub token dengan akses `read:packages`, lalu jalankan `docker login ghcr.io -u USERNAME` dan masukkan token saat diminta. Jangan masukkan token pada argumen command atau file repository. Batasi izin token dan simpan Docker credentials dengan izin file yang ketat.

## GitHub Actions

Pastikan package GHCR dapat dibaca oleh akun VPS. Tambahkan secrets berikut pada repository GitHub:

- `VPS_HOST`: alamat IP atau hostname VPS.
- `VPS_USER`: akun SSH khusus deployment dengan akses Docker dan `/opt/arahdiri`.
- `VPS_SSH_PRIVATE_KEY`: private key untuk akun deployment.
- `VPS_KNOWN_HOSTS`: host key VPS yang diverifikasi di luar workflow, format `ssh-keyscan`.

Workflow tidak menyalin `.env` atau mengirim password database. Deploy berjalan saat tag versi didorong:

```sh
git tag v1.0.0
git push origin v1.0.0
```

Workflow menjalankan tes Laravel dan build Vite pada push branch `main`, `ci`, atau tag. Push ke `ci` hanya menjalankan tes, tanpa publish dan tanpa deploy, sehingga branch itu aman untuk uji coba sebelum membuat versi. Jika tag versi lolos tes, workflow membangun image, menerbitkannya ke GHCR dengan tag versi dan commit SHA, mengirim `compose.yaml`, menjalankan migrasi, lalu memperbarui container menggunakan image SHA.

Workflow mengasumsikan file `/opt/arahdiri/.env` dan `/opt/arahdiri/.env.mysql`, network `arahdiri-backend`, serta login GHCR sudah disiapkan. Kedua file env tidak pernah dikirim lewat CI.

## Buat versi dengan npm

Tool `release-it` tersedia dari `package.json` root sebagai development dependency. Aplikasi tidak dipublikasikan ke npm. `.release-it.json` mensyaratkan branch `ci-*` (untuk uji deploy) dengan upstream yang sudah diset, working tree bersih, dan minimal satu commit.

```sh
npm ci
npm run release -- patch
```

Ganti `patch` dengan `minor` atau `major`. Bentuk positional `npm run release -- patch` dipakai karena sebagian versi npm membaca `--increment` sebagai opsi npm sendiri.

`release-it` menaikkan versi di `package.json` dan `package-lock.json`, membuat commit `Release x.y.z`, membuat tag `vMAJOR.MINOR.PATCH`, lalu mendorong branch dan tag ke `origin`. Tag memulai workflow deploy; commit versi harus ikut terkirim agar isi repo cocok dengan tag.

Branch `ci-*` wajib punya upstream sekali saja:

```sh
git push -u origin ci-deploy
```

Jangan membuat tag release manual untuk versi yang sama. Bila rilis gagal setelah tag dibuat, hapus tag lokal dan remote sebelum mengulang, dan pastikan commit versi sudah terdorong.

## Deploy awal dan katalog

Workflow menjalankan migrasi sebelum container web dimulai. Sebelum deploy pertama, buat backup database. Setelah deploy pertama sukses, muat katalog sekali:

```sh
cd /opt/arahdiri
APP_IMAGE=ghcr.io/OWNER/REPOSITORY APP_VERSION=v1.0.0 docker compose run --rm app php artisan catalog:import
```

CSV katalog dikemas dalam image. Jangan jalankan import pada setiap deployment karena admin dapat mengubah data katalog.

## Caddy

Tambahkan domain ke konfigurasi Caddy dan proxy ke port lokal aplikasi:

```caddy
contoh.id {
    reverse_proxy 127.0.0.1:8080
}
```

Arahkan record DNS `A` (dan `AAAA` jika IPv6 tersedia) ke VPS. Caddy mengurus sertifikat HTTPS otomatis. Pastikan port publik 80 dan 443 menuju Caddy; jangan expose port MySQL atau Apache aplikasi secara publik.

## Scheduler BAN-PT

Scheduler **tidak dimulai secara default**. Aplikasi menjadwalkan `banpt:sync` mingguan dan README proyek meminta izin/sumber data yang sesuai sebelum sinkronisasi otomatis. Setelah izin dan sumber data dikonfirmasi, aktifkan scheduler:

```sh
cd /opt/arahdiri
APP_IMAGE=ghcr.io/OWNER/REPOSITORY APP_VERSION=v1.0.0 docker compose --profile scheduler up -d scheduler
```

Nonaktifkan dengan `docker compose --profile scheduler stop scheduler`. Pantau log sebelum mengandalkan sinkronisasi.

## Backup dan rollback

Backup MySQL dan uji pemulihan secara berkala. Simpan salinan backup di luar VPS; volume Docker bukan backup. Contoh dump:

```sh
cd /opt/arahdiri
docker compose exec -T mysql sh -c 'exec mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction "$MYSQL_DATABASE"' > /lokasi-backup/arahdiri.sql
```

Backup juga file `/opt/arahdiri/.env` dan `.env.mysql` secara terenkripsi, serta `app_storage` bila menyimpan data penting. Pertahankan versi image sebelumnya. Untuk rollback aplikasi, jalankan Compose dengan `APP_VERSION` tag sebelumnya lalu `docker compose up -d app`. Rollback image tidak membatalkan perubahan database; pulihkan backup atau buat migrasi perbaikan bila migrasi baru tidak kompatibel.

## Pemeriksaan

```sh
cd /opt/arahdiri
APP_IMAGE=ghcr.io/OWNER/REPOSITORY APP_VERSION=v1.0.0 docker compose ps
curl --fail http://127.0.0.1:8080/up
```

Verifikasi login aplikasi, pengiriman email reset password, health check, restart container, dan pemulihan backup sebelum membuka layanan ke publik.
