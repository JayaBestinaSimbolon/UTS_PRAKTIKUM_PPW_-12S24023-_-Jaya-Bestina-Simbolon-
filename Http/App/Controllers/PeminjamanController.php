<?php

namespace App\Http\Controllers;

use App\Models\alat;
use App\Models\Peminjaman;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PeminjamanController extends Controller
{
    /**
     * Menampilkan daftar sirkulasi peminjaman buku perpustakaan.
     * Menggunakan Fluent Query Builder (DB::table) untuk statistik dan withQueryString untuk paginasi.
     */
    public function index(Request $request)
    {
        $status = $request->input('status');
        $q      = $request->input('q');

        // Simpan pencarian terakhir ke session state jika diisi
        if ($request->filled('q')) {
            session(['last_peminjaman_search' => $q]);
        }

        // Query daftar peminjaman dengan eager loading buku & kategori untuk menghindari N+1 query
        $peminjamans = Peminjaman::with(['alat.kategori'])
            ->when($status, function ($query, $st) {
                $query->where('status', $st);
            })
            ->when($q, function ($query, $search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('nim_peminjam', 'like', "%{$search}%")
                        ->orWhere('nama_peminjam', 'like', "%{$search}%")
                        ->orWhere('kode_pinjam', 'like', "%{$search}%")
                        ->orWhereHas('alat', function ($b) use ($search) {
                            $b->where('judul', 'like', "%{$search}%")
                              ->orWhere('isbn', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        // Statistik sirkulasi berbasis Fluent Query Builder (DB::table)
        $stats = [
            'total_transaksi' => DB::table('peminjamans')->count(),
            'sedang_dipinjam' => DB::table('peminjamans')->where('status', 'dipinjam')->count(),
            'dikembalikan'    => DB::table('peminjamans')->where('status', 'dikembalikan')->count(),
            'total_alat'      => DB::table('alats')->count(),
            'total_stok'      => (int) DB::table('bukus')->sum('stok'),
        ];

        // Daftar alat yang dapat dipilih untuk form peminjaman cepat
        $alats = Alat::orderBy('nama')->get(['id', 'nama', 'stok', 'kode_rak', 'lantai']);

        // Daftar peminjaman aktif untuk formulir pengembalian cepat
        $activeLoans = Peminjaman::with('alat')->where('status', 'dipinjam')->latest()->get();

        return view('peminjaman.index', compact('peminjamans', 'stats', 'alats', 'activeLoans', 'status', 'q'));
    }

    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'alat_id'             => 'required|integer|exists:alats,id',
            'nim_peminjam'        => 'required|string|min:6|max:20',
            'nama_peminjam'       => 'required|string|min:3|max:150',
            'tanggal_pinjam'      => 'required|date',
            'tanggal_jatuh_tempo' => 'required|date|after_or_equal:tanggal_pinjam',
        ], [
            'buku_id.required'             => 'Silakan pilih buku yang akan dipinjam.',
            'nim_peminjam.required'        => 'NIM peminjam wajib diisi.',
            'nama_peminjam.required'       => 'Nama peminjam wajib diisi.',
            'tanggal_pinjam.required'      => 'Tanggal peminjaman wajib diisi.',
            'tanggal_jatuh_tempo.required' => 'Tanggal jatuh tempo wajib diisi.',
            'tanggal_jatuh_tempo.after_or_equal' => 'Tanggal jatuh tempo harus sama atau setelah tanggal pinjam.',
        ]);

        $nim = strtoupper(trim($validated['nim_peminjam']));
        $bukuId = (int) $validated['buku_id'];

        try {
            $peminjaman = DB::transaction(function () use ($validated, $alatId, $nim) {
                // 1. Kunci baris buku menggunakan Pessimistic Locking (SELECT ... FOR UPDATE)
                $alat = Alat::where('id', $alatId)->lockForUpdate()->firstOrFail();

                // 2. Periksa stok buku sebelum peminjaman (Kritis: cegah stok 0 atau minus)
                if ($alat->stok < 1) {
                    throw new \RuntimeException("Transaksi dibatalkan (Rollback): Stok alat '{$alat->nama}' habis (0 eksemplar).");
                }

                // 3. Cegah NIM yang sama meminjam alat yang sama ketika peminjaman sebelumnya belum dikembalikan
                $peminjamanAktif = Peminjaman::where('alat_id', $alatId)
                    ->where('nim_peminjam', $nim)
                    ->where('status', 'dipinjam')
                    ->lockForUpdate()
                    ->first();

                if ($peminjamanAktif) {
                    throw new \RuntimeException("NIM {$nim} masih memiliki transaksi peminjaman aktif untuk alat ini (Kode: {$peminjamanAktif->kode_pinjam}). Selesaikan pengembalian terlebih dahulu.");
                }

                // 4. Kurangi stok alat secara atomik
                $alat->decrement('stok');

                // 5. Generate kode peminjaman unik (PMJ-YYYYMMDD-XXXX)
                do {
                    $kodePinjam = 'PMJ-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));
                } while (Peminjaman::where('kode_pinjam', $kodePinjam)->exists());

                // 6. Simpan transaksi ke tabel peminjamans
                return Peminjaman::create([
                    'kode_pinjam'         => $kodePinjam,
                    'alat_id'             => $alat->id,
                    'nim_peminjam'        => $nim,
                    'nama_peminjam'       => trim($validated['nama_peminjam']),
                    'tanggal_pinjam'      => $validated['tanggal_pinjam'],
                    'tanggal_jatuh_tempo' => $validated['tanggal_jatuh_tempo'],
                    'tanggal_kembali'     => null,
                    'status'              => 'dipinjam',
                ]);
            }, 3);

            return back()->with('success', "Peminjaman berhasil dicatat secara atomik! Kode: {$peminjaman->kode_pinjam}. Stok alat berkurang 1.");
        } catch (\RuntimeException $e) {
            // Rollback otomatis terjadi saat RuntimeException dilempar
            return back()->withInput()->with('error', $e->getMessage());
        } catch (QueryException $e) {
            Log::error('[PEMINJAMAN DB EXCEPTION]: ' . $e->getMessage(), [
                'alat_id' => $alatId,
                'nim'     => $nim,
            ]);
            return back()->withInput()->with('error', 'Terjadi kesalahan sistem basis data saat memproses peminjaman.');
        }
    }

   
    public function kembali(Request $request, int $id)
    {
        try {
            DB::transaction(function () use ($id) {
                // 1. Kunci baris peminjaman dengan Pessimistic Locking
                $peminjaman = Peminjaman::where('id', $id)->lockForUpdate()->firstOrFail();

                // 2. Cegah pengembalian ganda agar stok tidak bertambah dua kali
                if ($peminjaman->status === 'dikembalikan') {
                    throw new \RuntimeException("Alat dengan kode {$peminjaman->kode_pinjam} sudah pernah dikembalikan sebelumnya.");
                }

                // 3. Kunci baris alat yang bersangkutan
                $alat = Alat::where('id', $peminjaman->alat_id)->lockForUpdate()->firstOrFail();

                // 4. Ubah status menjadi 'dikembalikan' dan simpan tanggal pengembalian
                $peminjaman->update([
                    'status'          => 'dikembalikan',
                    'tanggal_kembali' => now()->toDateString(),
                ]);

                // 5. Kembalikan stok alat tepat 1 eksemplar secara atomik
                $alat->increment('stok');
            }, 3);

            return back()->with('success', 'Alat berhasil dikembalikan secara atomik! Stok alat telah bertambah 1.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (QueryException $e) {
            Log::error('[PENGEMBALIAN DB EXCEPTION]: ' . $e->getMessage(), [
                'peminjaman_id' => $id,
            ]);
            return back()->with('error', 'Terjadi kesalahan sistem basis data saat memproses pengembalian alat.');
        }
    }

    /**
     * Memproses pengembalian alat berdasarkan Kode Pinjam (form input pengembalian cepat).
     */
    public function kembaliByKode(Request $request)
    {
        $validated = $request->validate([
            'kode_pinjam' => 'required|string',
        ], [
            'kode_pinjam.required' => 'Silakan masukkan atau pilih Kode Pinjam untuk pengembalian.',
        ]);

        $kodePinjam = strtoupper(trim($validated['kode_pinjam']));
        $peminjaman = Peminjaman::where('kode_pinjam', $kodePinjam)->first();

        if (!$peminjaman) {
            return back()->withInput()->with('error', "Transaksi sirkulasi dengan kode '{$kodePinjam}' tidak ditemukan dalam database.");
        }

        return $this->kembali($request, $peminjaman->id);
    }
}
