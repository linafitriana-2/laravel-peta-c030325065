<?php

use App\Models\Sekolah;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PetaController;
use App\Http\Controllers\SekolahController;
use App\Http\Controllers\ProfilSekolahController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PasswordController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;


// Publik
Route::controller(PetaController::class)->group(function () {
    Route::get('/', 'index')->name('peta.index');
    Route::get('/data/sekolah', 'geojson')->name('peta.data');
    Route::get('/data/sekolah/radius', 'radius')->name('peta.radius');
    Route::get('/pencarian', 'cari')->name('peta.cari');
    Route::get('/klaster', 'klaster')->name('peta.klaster');
    Route::get('/peta-panas', 'panas')->name('peta.panas');
    Route::get('/wilayah', 'wilayah')->name('peta.wilayah');
    Route::get('/data/wilayah', 'geojsonWilayah')->name('peta.data.wilayah');
    Route::get('/rute', 'rute')->name('peta.rute');
    Route::get('/cek-alamat', 'alamat')->name('peta.alamat');
    Route::get('/gabungan', 'gabungan')->name('peta.gabungan');
    Route::get('/health', function () {
        try {
            DB::select('SELECT 1');

            return response()->json([
                'status'    => 'ok',
                'database'  => 'connected',
                'timestamp' => now()->toIso8601String(),
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'   => 'error',
                'database' => 'disconnected',
            ], 500);
        }
    })->name('health');
});



// Tamu
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');
});

// Pengguna yang sudah login
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');
    Route::get('/dashboard', DashboardController::class)
        ->name('dashboard');
    Route::get('/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/password', [PasswordController::class, 'update'])->name('password.update');

    // Dinas: daftar, tambah, dan hapus sekolah
    Route::middleware('role:dinas')->prefix('dinas')->name('dinas.')
        ->group(function () {
            Route::resource('sekolah', SekolahController::class)
                ->only(['index', 'create', 'store', 'destroy']);
        });

    // Dinas dan Sekolah: ubah data (dibatasi Policy)
    Route::middleware('role:dinas,sekolah')->group(function () {
        Route::get('sekolah/{sekolah}/edit', [SekolahController::class, 'edit'])
            ->name('sekolah.edit');
        Route::put('sekolah/{sekolah}', [SekolahController::class, 'update'])
            ->name('sekolah.update');
        Route::get('sekolah/{sekolah}/profil', [ProfilSekolahController::class, 'edit'])
            ->name('sekolah.profil.edit');
        Route::put('sekolah/{sekolah}/profil', [ProfilSekolahController::class, 'update'])
            ->name('sekolah.profil.update');
    });

    Route::middleware('role:admin')->prefix('admin')->name('admin.')
        ->group(function () {
            Route::resource('users', UserController::class)->except('show');
            Route::patch('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])
                ->name('users.toggle-active');
        });

    Route::get('/sekolah/{sekolah}', function (Sekolah $sekolah) {
        $sekolah->load('profil'); 
        return view('sekolah.show', compact('sekolah'));
    })->name('sekolah.show');
});
