<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\CatalogEditorController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\RecommendationCheckController;
use App\Http\Controllers\Admin\UserAdminController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\InfoController;
use App\Http\Controllers\DevelopmentController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TestController;
use Illuminate\Support\Facades\Route;

/*
| Nama route = nama tampilan v14 (data-v). Cangkang Vue memakainya untuk menu aktif.
*/

Route::get('/', LandingController::class)->name('landing');

// Katalog & pencarian terbuka untuk tamu (v14: "Jelajahi sebagai tamu").
Route::get('/jurusan', [CatalogController::class, 'programs'])->name('jurusan');
Route::get('/jurusan/{id}', [CatalogController::class, 'programs'])->whereNumber('id')->name('jurusan.detail');
Route::get('/karier', [CatalogController::class, 'careers'])->name('karier');
Route::get('/karier/{code}', [CatalogController::class, 'careers'])->where('code', '[0-9.]+')->name('karier.detail');
Route::get('/kampus', [CatalogController::class, 'campuses'])->name('kampus');
Route::get('/kampus/{pt}', [CatalogController::class, 'campuses'])->where('pt', '[0-9]{6}')->name('kampus.detail');
Route::get('/program-smk', [CatalogController::class, 'smk'])->name('smk');
Route::get('/referensi', [InfoController::class, 'references'])->name('referensi');
Route::get('/privasi', [InfoController::class, 'privacy'])->name('privasi');
Route::middleware('throttle:search')->group(function () {
    Route::get('/cari', [SearchController::class, 'index'])->name('cari');
    Route::get('/cari/saran', [SearchController::class, 'suggest'])->name('cari.saran');
});

Route::middleware('guest')->group(function () {
    Route::get('/masuk', [LoginController::class, 'create'])->name('login');
    Route::post('/masuk', [LoginController::class, 'store'])->middleware('throttle:login');
    Route::get('/daftar', [RegisterController::class, 'create'])->name('daftar');
    Route::post('/daftar', [RegisterController::class, 'store'])->middleware('throttle:register');
});

Route::middleware('auth')->group(function () {
    Route::post('/keluar', [LoginController::class, 'destroy'])->name('logout');

    // Mahasiswa UMB: pilih prodi UMB sebelum memakai fitur lain.
    Route::get('/mulai/prodi-umb', [OnboardingController::class, 'edit'])->name('onboarding');
    Route::post('/mulai/prodi-umb', [OnboardingController::class, 'update']);

    Route::middleware('onboarded')->group(function () {
        Route::get('/beranda', BerandaController::class)->name('beranda');
        Route::get('/tes', [TestController::class, 'index'])->name('tes');
        Route::post('/tes/mulai', [TestController::class, 'start'])->middleware('throttle:20,1')->name('tes.mulai');
        Route::post('/tes/{testSession}/jawab', [TestController::class, 'answer'])->middleware('throttle:240,1')->name('tes.jawab');
        Route::post('/tes/{testSession}/selesai', [TestController::class, 'finish'])->name('tes.selesai');
        Route::get('/hasil/{testSession?}', [ResultController::class, 'show'])->name('hasil');
        Route::get('/hasil/{testSession}/cetak', [ResultController::class, 'print'])->name('hasil.cetak');
        Route::post('/hasil/{testSession}/laporan', [ResultController::class, 'report'])->middleware('throttle:5,10')->name('hasil.laporan');

        Route::get('/riwayat', [HistoryController::class, 'index'])->name('riwayat');
        Route::delete('/riwayat/{testSession}', [HistoryController::class, 'destroy'])->name('riwayat.hapus');
        Route::delete('/riwayat', [HistoryController::class, 'destroyAll'])->name('riwayat.hapus-semua');

        Route::get('/favorit', [FavoriteController::class, 'index'])->name('favorit');
        Route::post('/favorit', [FavoriteController::class, 'toggle'])->middleware('throttle:120,1')->name('favorit.toggle');

        Route::get('/langkah', [DevelopmentController::class, 'index'])->name('langkah');
        Route::post('/langkah', [DevelopmentController::class, 'store'])->name('langkah.tambah');
        Route::post('/langkah/{key}/centang', [DevelopmentController::class, 'toggle'])->name('langkah.centang');
        Route::delete('/langkah/{key}', [DevelopmentController::class, 'destroy'])->name('langkah.hapus');

        Route::get('/profil', [ProfileController::class, 'show'])->name('profil');
        Route::put('/profil', [ProfileController::class, 'update'])->name('profil.update');
        Route::put('/profil/kata-sandi', [ProfileController::class, 'password'])->middleware('throttle:10,1')->name('profil.password');
        Route::post('/profil/keluar-semua', [ProfileController::class, 'logoutOthers'])->middleware('throttle:10,1')->name('profil.keluar-semua');
        Route::delete('/profil', [ProfileController::class, 'destroy'])->middleware('throttle:5,1')->name('profil.hapus');
    });
});

// Panel admin. Editor konten (guru BK): ringkasan, pengecekan rekomendasi, katalog, konten.
// Admin & owner: semuanya. Pengelolaan akun admin dan jawaban mentah khusus owner (dicek di controller).
Route::middleware(['auth', 'role:editor,admin,owner'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/simulator', [RecommendationCheckController::class, 'simulator'])->middleware('throttle:30,1')->name('simulator');
    Route::get('/laporan', [RecommendationCheckController::class, 'reports'])->name('laporan');
    Route::put('/laporan/{report}', [RecommendationCheckController::class, 'updateReport'])->name('laporan.ubah');
    Route::get('/katalog', [AdminController::class, 'catalog'])->name('katalog');
    Route::post('/katalog/tampil', [AdminController::class, 'toggleCatalog'])->name('katalog.tampil');
    Route::get('/katalog/cari-karier', [CatalogEditorController::class, 'searchCareers'])->name('katalog.cari-karier');
    Route::get('/katalog/{jenis}/{id}', [CatalogEditorController::class, 'show'])->whereIn('jenis', ['karier', 'prodi', 'smk', 'sma'])->name('katalog.lihat');
    Route::put('/katalog/{jenis}/{id}', [CatalogEditorController::class, 'update'])->whereIn('jenis', ['karier', 'prodi', 'smk', 'sma'])->name('katalog.ubah');
    Route::post('/katalog/{jenis}/{id}/lepas', [CatalogEditorController::class, 'release'])->whereIn('jenis', ['karier', 'prodi', 'smk'])->name('katalog.lepas');
    Route::get('/konten', [ContentController::class, 'index'])->name('konten');
    Route::post('/konten', [ContentController::class, 'save'])->name('konten.simpan');
    Route::delete('/konten/{item}', [ContentController::class, 'destroy'])->name('konten.hapus');

    Route::middleware('role:admin,owner')->group(function () {
        Route::get('/hasil', [AdminController::class, 'results'])->name('hasil');
        Route::get('/hasil/ekspor', [AdminController::class, 'export'])->middleware('throttle:10,1')->name('ekspor');
        Route::get('/akreditasi', [AdminController::class, 'accreditation'])->name('akreditasi');
        Route::get('/akreditasi/kampus', [AdminController::class, 'campusSearch'])->name('akreditasi.kampus');
        Route::post('/akreditasi/pasangan', [AdminController::class, 'pair'])->name('akreditasi.pasang');
        Route::delete('/akreditasi/pasangan/{id}', [AdminController::class, 'unpair'])->whereNumber('id')->name('akreditasi.lepas');
        Route::post('/akreditasi/sinkron', [AdminController::class, 'resync'])->middleware('throttle:5,1')->name('akreditasi.sinkron');
        Route::post('/katalog/impor', [AdminController::class, 'reimport'])->middleware('throttle:3,1')->name('katalog.impor');
        Route::get('/aturan', [RecommendationCheckController::class, 'rules'])->name('aturan');
        Route::post('/aturan', [RecommendationCheckController::class, 'saveRules'])->name('aturan.simpan');
        Route::get('/pengguna', [UserAdminController::class, 'index'])->name('pengguna');
        Route::post('/pengguna/{user}/status', [UserAdminController::class, 'toggle'])->name('pengguna.status');
        Route::post('/pengguna/{user}/sandi-sementara', [UserAdminController::class, 'temporaryPassword'])->middleware('throttle:20,1')->name('pengguna.sandi');
        Route::get('/akun', [AdminController::class, 'accounts'])->name('akun');
        Route::post('/akun', [AdminController::class, 'promote'])->name('akun.tambah');
        Route::post('/akun/{user}', [AdminController::class, 'setAdmin'])->name('akun.ubah');
        Route::get('/audit', [AdminController::class, 'audit'])->name('audit');
    });
});
