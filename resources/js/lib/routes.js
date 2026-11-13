// Pemetaan nama tampilan v14 (data-v) ke URL v2.
export const VIEW_URL = {
    landing: '/',
    beranda: '/beranda',
    tes: '/tes',
    hasil: '/hasil',
    jurusan: '/jurusan',
    karier: '/karier',
    kampus: '/kampus',
    langkah: '/langkah',
    favorit: '/favorit',
    riwayat: '/riwayat',
    profil: '/profil',
    referensi: '/referensi',
    login: '/masuk',
    daftar: '/daftar',
};

/** v14 'mulai': beranda bila sudah masuk, halaman awal bila tamu. */
export function homeUrl(user) {
    return user ? VIEW_URL.beranda : VIEW_URL.landing;
}
