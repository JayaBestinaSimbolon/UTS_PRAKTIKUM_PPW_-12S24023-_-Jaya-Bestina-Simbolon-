<?php

namespace Database\Seeders;

use App\Models\Peminjaman;
use Illuminate\Database\Seeder;

class PeminjamanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Membuat sampel data peminjaman alat (status dipinjam dan dikembalikan).
     * Menggunakan updateOrCreate agar aman dijalankan berulang kali tanpa duplikasi data.
     */
    public function run(): void
    {
        $alat1 = Alat::where('stok', '>', 0)->first();
        $alat2 = Alat::where('stok', '>', 0)->skip(1)->first();

        if ($alat1) {
            // Sampel 1: Transaksi Peminjaman Aktif (status: dipinjam)
            Peminjaman::updateOrCreate(
                ['kode_pinjam' => 'PMJ-20261015-001'],
                [
                    'alat_id'             => $buku1->id,
                    'nim_peminjam'        => '12S24023',
                    'nama_peminjam'       => 'Jaya Bestina Simbolon',
                    'tanggal_pinjam'      => now()->subDays(3)->toDateString(),
                    'tanggal_jatuh_tempo' => now()->addDays(4)->toDateString(),
                    'tanggal_kembali'     => null,
                    'status'              => 'dipinjam',
                ]
            );
        }

        if ($buku2) {
            // Sampel 2: Transaksi Peminjaman Selesai (status: dikembalikan)
            Peminjaman::updateOrCreate(
                ['kode_pinjam' => 'PMJ-20261010-002'],
                [
                    'alat_id'             => $alat2->id,
                    'nim_peminjam'        => '12S24001',
                    'nama_peminjam'       => 'Budi Setiawan',
                    'tanggal_pinjam'      => now()->subDays(10)->toDateString(),
                    'tanggal_jatuh_tempo' => now()->subDays(3)->toDateString(),
                    'tanggal_kembali'     => now()->subDays(2)->toDateString(),
                    'status'              => 'dikembalikan',
                ]
            );
        }
    }
}
