<?php

use App\Http\Controllers\AnggaranController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BahanPanganController;
use App\Http\Controllers\BiayaController;
use App\Http\Controllers\BudgetAlertController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GiziController;
use App\Http\Controllers\ImportTkpiController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\MenuHarianController;
use App\Http\Controllers\PesanMasukController;
use App\Http\Controllers\SimulasiController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Artisan;

// ── Halaman publik ────────────────────────────────────────────────────────────
Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::post('/pesan', [LandingController::class, 'kirimPesan'])->name('landing.kirim-pesan')->middleware('throttle:5,1');

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// ── Trigger reset data demo (dipanggil external cron, mis. cron-job.org) ───────
// Aktif hanya kalau DEMO_MODE=true. Token wajib cocok, bukan cuma di URL biasa.
Route::get('/system/demo-reset/{token}', function (string $token) {
    $expected = config('app.demo_reset_token');

    if (! config('app.demo_mode') || ! $expected || ! hash_equals((string) $expected, $token)) {
        abort(404);
    }

    Artisan::call('demo:reset');

    return response('OK');
})->middleware('throttle:3,60')->name('system.demo-reset');

Route::middleware('auth')->group(function () {

    // ── Superadmin: hanya manajemen akun ─────────────────────────────────────
    Route::middleware('role:superadmin')->group(function () {
        Route::resource('users', UserController::class);
        Route::patch('users/{user}/reset-password', [UserController::class, 'resetPassword'])
            ->name('users.reset-password');
    });

    // ── Semua role operasional (bukan superadmin) ─────────────────────────────
    Route::middleware('role:ketua_sppg,ahli_gizi,akuntan')->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // API autocomplete bahan pangan (semua role operasional bisa)
        Route::get('/api/bahan-pangan/search', [BahanPanganController::class, 'apiSearch'])
            ->name('api.bahan-pangan.search');

        // ── Bahan Pangan ──────────────────────────────────────────────────────
        Route::prefix('bahan-pangan')->name('bahan-pangan.')->group(function () {
            // View: semua role operasional
            Route::get('/', [BahanPanganController::class, 'index'])->name('index');

            // Manajemen: ketua_sppg saja (controller juga memverifikasi)
            Route::middleware('role:ketua_sppg')->group(function () {
                Route::get('/create', [BahanPanganController::class, 'create'])->name('create');
                Route::post('/', [BahanPanganController::class, 'store'])->name('store');
                Route::get('/{bahanPangan}/edit', [BahanPanganController::class, 'edit'])->name('edit');
                Route::put('/{bahanPangan}', [BahanPanganController::class, 'update'])->name('update');
                Route::delete('/{bahanPangan}', [BahanPanganController::class, 'destroy'])->name('destroy');
                Route::patch('/{bahanPangan}/status', [BahanPanganController::class, 'toggleStatus'])
                    ->name('toggle-status');
            });

            // Wildcard show harus di bawah semua route spesifik
            Route::get('/{bahanPangan}', [BahanPanganController::class, 'show'])->name('show');
        });

        // ── Menu Harian ───────────────────────────────────────────────────────
        Route::prefix('menu-harian')->name('menu-harian.')->group(function () {
            // View: ketua_sppg + ahli_gizi (index — tanpa wildcard)
            Route::middleware('role:ketua_sppg,ahli_gizi')->group(function () {
                Route::get('/', [MenuHarianController::class, 'index'])->name('index');
            });

            // Input & manajemen: ahli_gizi saja (rute spesifik SEBELUM wildcard)
            Route::middleware('role:ahli_gizi')->group(function () {
                Route::get('/create', [MenuHarianController::class, 'create'])->name('create');
                Route::post('/', [MenuHarianController::class, 'store'])->name('store');
                Route::get('/{menuHarian}/edit', [MenuHarianController::class, 'edit'])->name('edit');
                Route::put('/{menuHarian}', [MenuHarianController::class, 'update'])->name('update');
                Route::delete('/{menuHarian}', [MenuHarianController::class, 'destroy'])->name('destroy');
                Route::patch('/{menuHarian}/finalize', [MenuHarianController::class, 'finalize'])->name('finalize');
                Route::post('/{menuHarian}/upload-foto', [MenuHarianController::class, 'uploadFoto'])->name('upload-foto');
            });

            // Wildcard show setelah semua rute spesifik
            Route::middleware('role:ketua_sppg,ahli_gizi')->group(function () {
                Route::get('/{menuHarian}', [MenuHarianController::class, 'show'])->name('show');
            });
        });

        // ── Simulasi Menu: ahli_gizi saja ─────────────────────────────────────
        Route::middleware('role:ahli_gizi')->prefix('simulasi')->name('simulasi.')->group(function () {
            Route::get('/', [SimulasiController::class, 'index'])->name('index');
            Route::get('/{menuHarian}/edit', [SimulasiController::class, 'editMenu'])->name('edit-simulasi');
            Route::post('/kalkulasi', [SimulasiController::class, 'kalkulasi'])->name('kalkulasi');
            Route::post('/simpan', [SimulasiController::class, 'simpan'])->name('simpan');
        });

        // ── Monitoring Gizi: ketua_sppg + ahli_gizi ───────────────────────────
        Route::middleware('role:ketua_sppg,ahli_gizi')->prefix('gizi')->name('gizi.')->group(function () {
            Route::get('/dashboard', [GiziController::class, 'dashboard'])->name('dashboard');
            Route::get('/api/trend', [GiziController::class, 'apiTrend'])->name('api.trend');
        });

        // ── Biaya Produksi: ketua_sppg + akuntan ──────────────────────────────
        Route::middleware('role:ketua_sppg,akuntan')->prefix('biaya')->name('biaya.')->group(function () {
            Route::get('/dashboard', [BiayaController::class, 'dashboard'])->name('dashboard');
            Route::get('/detail/{menu}', [BiayaController::class, 'detailMenu'])->name('detail-menu');
            Route::post('/api/estimasi', [BiayaController::class, 'apiEstimasi'])->name('api.estimasi');

            // Harga Bahan — view: ketua_sppg + akuntan; CUD: akuntan saja
            Route::prefix('harga')->name('harga.')->group(function () {
                Route::get('/', [BiayaController::class, 'indexHarga'])->name('index');

                // Tambah, simpan, dan hapus: akuntan saja
                Route::middleware('role:akuntan')->group(function () {
                    Route::get('/tambah', [BiayaController::class, 'createHarga'])->name('create');
                    Route::post('/', [BiayaController::class, 'storeHarga'])->name('store');
                    Route::delete('/{harga}', [BiayaController::class, 'destroyHarga'])->name('destroy');
                });

                // Edit & update selalu redirect (tarif immutable)
                Route::get('/{harga}/edit', [BiayaController::class, 'editHarga'])->name('edit');
                Route::put('/{harga}', [BiayaController::class, 'updateHarga'])->name('update');
            });
        });

        // ── Pesan Masuk: ketua_sppg saja ─────────────────────────────────────
        Route::middleware('role:ketua_sppg')
            ->get('/pesan-masuk', [PesanMasukController::class, 'index'])
            ->name('pesan-masuk.index');

        // ── Anggaran Porsi: ketua_sppg saja ──────────────────────────────────
        Route::middleware('role:ketua_sppg')->prefix('anggaran')->name('anggaran.')->group(function () {
            Route::get('/', [AnggaranController::class, 'index'])->name('index');
            Route::get('/tambah', [AnggaranController::class, 'create'])->name('create');
            Route::post('/', [AnggaranController::class, 'store'])->name('store');
        });

        // ── Laporan: semua role operasional ───────────────────────────────────
        Route::prefix('laporan')->name('laporan.')->group(function () {
            Route::get('/', [LaporanController::class, 'index'])->name('index');
            Route::get('/export-excel', [LaporanController::class, 'exportExcel'])->name('export-excel');
            Route::get('/export-pdf', [LaporanController::class, 'exportPdf'])->name('export-pdf');
        });

        // ── Import TKPI: ketua_sppg saja ──────────────────────────────────────
        Route::middleware('role:ketua_sppg')->prefix('import-tkpi')->name('import-tkpi.')->group(function () {
            Route::get('/', [ImportTkpiController::class, 'index'])->name('index');
            Route::post('/preview', [ImportTkpiController::class, 'preview'])->name('preview');
            Route::post('/import', [ImportTkpiController::class, 'import'])->name('import');
        });

        // ── Budget Alert: ketua_sppg + akuntan ────────────────────────────────
        Route::middleware('role:ketua_sppg,akuntan')->prefix('budget-alert')->name('budget-alert.')->group(function () {
            Route::get('/', [BudgetAlertController::class, 'index'])->name('index');
            Route::post('/dismiss', [BudgetAlertController::class, 'dismiss'])->name('dismiss');
        });
    });
});
