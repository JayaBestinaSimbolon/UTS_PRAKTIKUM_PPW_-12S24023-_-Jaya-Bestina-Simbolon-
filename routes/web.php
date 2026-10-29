<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BukuController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\LayananController;
use App\Http\Controllers\PeminjamanController;
use App\Http\Controllers\TurnitinController;
use Illuminate\Support\Facades\Route;


// Beranda & Katalog
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/katalog', [AlatController::class, 'index'])->name('katalog');
Route::get('/buku/{buku}', [AlatController::class, 'show'])->name('alat.show');

// Kategori Koleksi (Read-Only Publik)
Route::get('/kategori', [KategoriController::class, 'index'])->name('kategori.index');
Route::get('/kategori/{kategori}', [KategoriController::class, 'show'])->name('kategori.show');

// Layanan Informasi 
Route::get('/layanan', [LayananController::class, 'index'])->name('layanan.index');
Route::get('/layanan/bantuan', [LayananController::class, 'bantuan'])->name('layanan.bantuan');
Route::get('/tentang', [LayananController::class, 'tentang'])->name('tentang');
Route::get('/turnitin', [TurnitinController::class, 'index'])->name('peminjaman.index');
Route::post('/turnitin', [TurnitinController::class, 'store'])->name('peminjaman.store');

// Autentikasi Adminlaboratotium
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// =========================================================================
// 2. RUTE MUTASI & ADMINISTRASI (Diproteksi Penuh Oleh Middleware 'auth')
// =========================================================================
Route::middleware('auth')->group(function () {
    // Mode Pengelolaan Buku & Mutasi CRUD
    Route::get('/alat', [BukuController::class, 'index'])->name('alat.index');
    Route::get('/alat-baru/create', [BukuController::class, 'create'])->name('alat.create');
    Route::post('/alat', [BukuController::class, 'store'])->name('alat.store');
    Route::get('/alat/{alat}/edit', [BukuController::class, 'edit'])->name('alat.edit');
    Route::match(['put', 'patch'], '/alat/{alat}', [BukuController::class, 'update'])->name('alat.update');
    Route::delete('/alat/{alat}', [BukuController::class, 'destroy'])->name('alat.destroy');

    // Mutasi CRUD Kategori
    Route::get('/kategori-baru/create', [KategoriController::class, 'create'])->name('kategori.create');
    Route::post('/kategori', [KategoriController::class, 'store'])->name('kategori.store');
    Route::get('/kategori/{kategori}/edit', [KategoriController::class, 'edit'])->name('kategori.edit');
    Route::match(['put', 'patch'], '/kategori/{kategori}', [KategoriController::class, 'update'])->name('kategori.update');
    Route::delete('/kategori/{kategori}', [KategoriController::class, 'destroy'])->name('kategori.destroy');

    // Sirkulasi Peminjaman & Pengembalian Buku (ACID & Concurrency Locking)
    Route::get('/peminjaman', [PeminjamanController::class, 'index'])->name('peminjaman.index');
    Route::post('/peminjaman', [PeminjamanController::class, 'store'])->name('peminjaman.store');
    Route::post('/peminjaman/kembali-by-kode', [PeminjamanController::class, 'kembaliByKode'])->name('peminjaman.kembaliByKode');
    Route::post('/peminjaman/{id}/kembali', [PeminjamanController::class, 'kembali'])->name('peminjaman.kembali');
});
