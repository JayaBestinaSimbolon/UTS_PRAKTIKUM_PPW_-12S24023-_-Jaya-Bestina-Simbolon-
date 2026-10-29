SOAL UJIAN TENGAH SEMESTER PRAKTIKUM (HOTS) 
Studi Kasus: Pengembangan Sistem Manajemen Aset dan Peminjaman Inventaris 
Laboratorium Terpadu (DEL Lab Equipment & Asset Tracking System - DEL-LEATS) 

Laboratorium Rekayasa Perangkat Lunak dan Jaringan di Institut Teknologi Del mengelola ratusan aset 
perangkat teknologi berharga tinggi (seperti laptop pengujian performa tinggi, drone observasi, kit sensor 
IoT, server mini, dan tablet digital). Selama ini, proses inventarisasi dan peminjaman perangkat 
laboratorium oleh mahasiswa untuk keperluan tugas akhir dan riset masih dilakukan secara manual 
menggunakan lembaran formulir fisik atau spreadsheet terpisah. Hal ini menimbulkan sejumlah kendala 
operasional yang serius: (1) Sering terjadi peminjaman ganda (double-booking) karena ketersediaan unit 
tidak termonitor secara real-time, (2) Riwayat kondisi fisik perangkat tidak terdokumentasi secara visual 
sehingga saat terjadi kerusakan tidak diketahui pihak yang bertanggung jawab, (3) Pengguna kesulitan 
mencari dan memfilter perangkat berdasarkan kategori spesifik karena data tidak terpaginasi dengan baik, 
dan (4) Formulir peminjaman kerap diisi dengan data asal-asalan tanpa penegakan validasi ketat sehingga 
batas pengembalian sering terlampaui tanpa perhitungan kompensasi yang jelas.Untuk memecahkan permasalahan di atas, Anda ditugaskan merancang dan membangun modul inti 
aplikasi web terintegrasi berbasis pola arsitektur Model-View-Controller (MVC) yang menghubungkan 
antarmuka pengguna interaktif dengan penyimpanan basis data persisten. Aplikasi web yang Anda 
kembangkan harus mencakup alur lengkap: mulai dari penyiapan skema tabel basis data, pendaftaran 
inventaris baru disertai pengunggahan berkas foto fisik perangkat, katalog penjelajahan aset berpaginasi 
dengan pencarian kata kunci dan multi-filter, hingga transaksi pengajuan peminjaman yang menerapkan 
validasi aturan bisnis ketat, pencegahan serangan formulir, dan mutasi status perangkat secara konsisten.

A. SPESIFIKASI KEBUTUHAN SISTEM & ATURAN PENGEMBANGAN 
1. Perancangan Skema Basis Data & Migrations (15 Poin) 
Buat berkas migrasi basis data dengan nama tabel dan atribut yang presisi sebagai berikut: 
• Tabel 'categories': 'id' (Primary Key Auto Increment), 'name' (VARCHAR 100, Unique), 'code' (VARCHAR 10, 
Unique, misal: 'IOT', 'SRV', 'DRN'), 'created_at', 'updated_at'. 
• Tabel 'equipments': 'id' (Primary Key), 'category_id' (Foreign Key merujuk ke 'categories.id' on-delete 
restrict), 'asset_code' (VARCHAR 20, Unique, format: EQ-[KODE_KAT]-[NOMOR]), 'name' (VARCHAR 150), 
'specification' (TEXT), 'condition' (ENUM: 'baik', 'rusak_ringan', 'rusak_berat', default: 'baik'), 'status' (ENUM: 
'tersedia', 'dipinjam', 'dalam_perbaikan', default: 'tersedia'), 'daily_rental_fee' (INTEGER, tarif kompensasi 
harian dalam Rupiah), 'image_path' (VARCHAR 255, nullable, menyimpan jalur berkas gambar perangkat), 
timestamps. 
• Tabel 'loan_requests': 'id' (Primary Key), 'equipment_id' (Foreign Key merujuk ke 'equipments.id'), 
'borrower_name' (VARCHAR 100), 'borrower_nim' (VARCHAR 10), 'borrower_email' (VARCHAR 100), 
'loan_date' (DATE), 'expected_return_date' (DATE), 'purpose' (TEXT), 'status' (ENUM: 'diajukan', 'disetujui', 
'ditolak', 'selesai', default: 'diajukan'), 'total_estimated_fee' (INTEGER, hasil kalkulasi otomatis selisih hari 
dikali tarif harian), timestamps. 
• Siapkan berkas Database Seeder untuk mengisi data awal: minimal 3 kategori ('Server & Cloud', 'Internet of 
Things', 'Multimedia & Drone') dan minimal 6 data perangkat laboratorium dengan status dan kondisi 
bervariasi. 

2. Arsitektur Model, Controller, dan Routing RESTful (15 Poin) 
Terapkan pemisahan tanggung jawab MVC secara disiplin: 
• Model Eloquent: 'Category', 'Equipment', dan 'LoanRequest'. Definisikan properti mass assignment ($fillable) 
dengan aman, serta relasi Eloquent: Category hasMany Equipment, Equipment belongsTo Category, 
Equipment hasMany LoanRequest. 
• RESTful Routing & Controller Action pada 'routes/web.php': 
1.GET  /equipments -> EquipmentController@index (Katalog berpaginasi, pencarian & filter)
2.GET  /equipments/create > EquipmentController@create (Tampilan formulir tambah aset) 
3.POST /equipments  -> EquipmentController@store (Validasi, upload foto, penyimpanan aset) 
4.GET  /equipments/{id} -> EquipmentController@show (Detail spesifikasi & ketersediaan aset) 
5.GET  /loans/create -> LoanController@create (Formulir pengajuan peminjaman aset)
6.POST /loans -> LoanController@store (Validasi aturan bisnis & transaksi peminjaman)


3. Desain Antarmuka Semantik HTML5 & CSS Responsif (20 Poin) 
• Gunakan layout induk Blade ('layouts/app.blade.php') dengan penandaan elemen semantik HTML5 
(<header>, <nav>, <main>, <section>, <article>, <footer>). 
• Buat tata letak responsif menggunakan Flexbox atau CSS Grid (minimal 3 kolom pada desktop, 2 kolom pada 
tablet, dan 1 kolom pada ponsel cerdas). 
'Tersedia'        
• Terapkan indikator visual status ketersediaan (Status Badge) yang kontras dan jelas: 
1. -> Badge warna Hijau (misal: latar #DCFCE7, teks #166534) 
2. 
3. 
'Dipinjam'        -> Badge warna Kuning/Oranye (misal: latar #FEF9C3, teks #854D0E) 
'Dalam Perbaikan' -> Badge warna Merah (misal: latar #FEE2E2, teks #991B1B) 
• Navigasi bar navigasi utama harus menyajikan tautan aktif menuju Katalog Aset, Tambah Aset, dan Riwayat 
Peminjaman. 

4. Pengelolaan Formulir, Validasi Server-Side, & Upload Berkas (25 Poin) 
Keamanan Formulir: Wajib menyertakan direktif token proteksi CSRF (@csrf) pada setiap elemen formulir 
metode POST. 
• Formulir Tambah Aset Baru: 
1. 
Elemen formulir wajib menyertakan atribut enctype="multipart/form-data". 
2. 
3. 
Validasi Server-side: 'asset_code' (required, unique:equipments), 'name' (required, min:5, max:150), 
'category_id' (required, exists:categories,id), 'daily_rental_fee' (required, numeric, min:0), 'image' 
(required, image, mimes:jpeg,png,webp, max:2048 KB). 
Berkas gambar wajib disimpan ke direktori storage publik dengan penamaan unik berbasis waktu/hash 
agar tidak terjadi penimpaan berkas. 
• Formulir Pengajuan Peminjaman: 
1. 
'equipment_id': Wajib dipilih dan saat submit harus diverifikasi bahwa statusnya bernilai 'tersedia'. Jika 
berstatus 'dipinjam' atau 'dalam_perbaikan', sistem wajib menolak dan menampilkan pesan galat bisnis. 
2. 
3. 
4. 
5. 
6. 
'borrower_name': Required, minimal 3 karakter. 
'borrower_nim': Required, tepat 8 digit angka/karakter format NIM Del. 
'borrower_email': Required, format email valid. 
'loan_date': Required, date, tidak boleh sebelum hari ini (after_or_equal:today). 
'expected_return_date': Required, date, after_or_equal:loan_date, dan durasi sewa maksimal 7 hari 
kalender. 
• Penanganan Error & Retensi Input: Jika terjadi kegagalan validasi, form tidak boleh dikosongkan. Nilai input 
yang sudah diketikkan harus dipertahankan menggunakan helper old(), dan pesan galat spesifik wajib muncul 
tepat di bawah field input yang bermasalah. 
• Post-Redirect-Get (PRG) & Flash Notification: Setelah transaksi berhasil, arahkan pengguna kembali dengan 
pesan notifikasi kilat (flash session message) berwarna hijau yang muncul di bagian atas halaman.


5. Paginasi Dinamis, Filter Kategori, & Pencarian Kata Kunci (25 Poin)
Pada halaman katalog peralatan ('GET /equipments'), tampilkan data secara berpaginasi sebanyak 5 atau 6 
kartu aset per halaman. 
• Sediakan formulir pencarian (metode GET) dengan fitur: 
1. Input teks kata kunci (search keyword) yang mencari secara parsial pada 'name' dan 'asset_code'. 
2.Dropdown filter 'category_id' untuk menyaring berdasarkan kategori. 
3.Dropdown filter 'status' untuk menyaring berdasarkan ketersediaan ('semua', 'tersedia', 'dipinjam', 
'dalam_perbaikan'). 
Dropdown filter 'category_id' untuk menyaring berdasarkan kategori. 
Dropdown filter 'status' untuk menyaring berdasarkan ketersediaan ('semua', 'tersedia', 'dipinjam', 
'dalam_perbaikan'). 
• Tautan pagination wajib mempertahankan parameter query string pencarian (menggunakan method 
>appends(request()->query()) pada objek paginator).
