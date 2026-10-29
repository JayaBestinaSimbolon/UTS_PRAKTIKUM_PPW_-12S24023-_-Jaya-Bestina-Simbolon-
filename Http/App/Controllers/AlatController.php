<?php

namespace App\Http\Controllers;

use App\Models\alat;
use App\Models\Kategori;
use Illuminate\Http\Request;

class BukuController extends Controller
{
    /**
     * Display a listing of the resource (Katalog & Pencarian & Filter Lokasi).
     * Terbuka untuk umum maupun admin.
     */
    public function index(Request $request)
    {
        // Simpan riwayat kata kunci pencarian ke session state
        if ($request->filled('q')) {
            session(['last_buku_search' => $request->q]);
        }

        $filters = [
            'q'             => $request->input('q'),
            'kategori'      => $request->input('kategori'),
            'jenis_koleksi' => $request->input('jenis_koleksi'),
            'lantai'        => $request->input('lantai'),
            'ketersediaan'  => $request->input('ketersediaan'),
            'sort'          => $request->input('sort', 'latest'),
        ];

        // Eager loading kategori dan hitungan relasi peminjaman untuk mencegah masalah N+1 query
        $query = Buku::with('kategori')
            ->withCount('peminjamans')
            ->filter($filters);

        // Pengurutan
        switch ($filters['sort']) {
            case 'nama_asc':
                $query->orderBy('nama', 'asc');
                break;
            case 'nama_desc':
                $query->orderBy('nama', 'desc');
                break;
            case 'tahun_desc':
                $query->orderBy('tahun_terbit', 'desc');
                break;
            case 'tahun_asc':
                $query->orderBy('tahun_terbit', 'asc');
                break;
            case 'stok_desc':
                $query->orderBy('stok', 'desc');
                break;
            default:
                $query->latest();
                break;
        }

        $bukus = $query->paginate(12)->withQueryString();
        $kategoris = Kategori::orderBy('nama_kategori')->get();

        // Statistik admin menggunakan Fluent Query Builder (DB::table)
        $stats = [
            'total_alat'     => \Illuminate\Support\Facades\DB::table('bukus')->count(),
            'total_stok'      => (int) \Illuminate\Support\Facades\DB::table('bukus')->sum('stok'),
            'sedang_dipinjam' => \Illuminate\Support\Facades\DB::table('peminjamans')->where('status', 'dipinjam')->count(),
            'dikembalikan'    => \Illuminate\Support\Facades\DB::table('peminjamans')->where('status', 'dikembalikan')->count(),
        ];

        // Mode tampilan: adminlaboratorium table atau catalog grid
        $viewMode = $request->input('view', 'catalog');

        return view('alat.index', compact('alats', 'kategoris', 'filters', 'viewMode', 'stats'));
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
