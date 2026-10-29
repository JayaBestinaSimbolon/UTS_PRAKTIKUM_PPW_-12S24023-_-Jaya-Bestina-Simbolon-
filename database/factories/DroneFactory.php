<?php

namespace Database\Factories;

use App\Models\Drone;
use App\Models\Kategori;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Buku>
 */
class DroneFactory extends Factory
{
    protected $model = Buku::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $penerbit = $this->faker->randomElement([
            'Informatika',
            'Andi Publisher',
            'Elex Media Komputindo',
            'Gramedia',
            'Erlangga',
            'Deepublish',
            'Rineka Cipta',
            'Balai Pustaka',
        ]);

        return [
            'isbn' => $this->faker->unique()->numerify('978-###-####-##-#'),
            'judul' => $this->faker->randomElement([
                'Pemrograman Web Modern dengan Laravel',
                'Dasar-Dasar Basis Data Relasional',
                'Analisis dan Perancangan Sistem Informasi',
                'Jaringan Komputer dan Keamanan Siber',
                'Kecerdasan Buatan: Teori dan Implementasi',
                'Algoritma dan Struktur Data',
                'Pemrograman Berorientasi Objek dengan PHP',
                'Sistem Manajemen Basis Data',
                'Rekayasa Perangkat Lunak Modern',
                'Pemrograman Python untuk Data Science',
                'Pengantar Teknologi Informasi',
                'Metodologi Penelitian Sistem Informasi',
                'Cloud Computing: Konsep dan Implementasi',
                'Mobile Application Development dengan Flutter',
                'Keamanan Jaringan Komputer',
                'Sistem Operasi: Konsep dan Implementasi',
                'Matematika Diskrit untuk Informatika',
                'Manajemen Proyek Teknologi Informasi',
                'Pemrograman Mikrokontroler Arduino',
                'Visualisasi Data dengan Python',
                'Enterprise Resource Planning',
                'Audit Sistem Informasi',
                'Deep Learning dengan TensorFlow',
                'Desain Basis Data Lanjutan',
                'Komunikasi Data dan Jaringan',
            ]),
            'penulis' => $this->faker->randomElement([
                'Dr. Ahmad Fauzi, M.Kom',
                'Prof. Bambang Santoso, Ph.D',
                'Siti Rahayu, S.Kom, M.T',
                'Ir. Budi Hartono, M.Sc',
                'Dra. Linda Kusuma, M.Kom',
                'Rizky Pratama, S.T, M.T',
                'Dr. Hendra Wijaya, M.Cs',
                'Agus Setiawan, M.Kom',
                'Dewi Anggraini, Ph.D',
                'Eko Nugroho, M.Sc',
            ]),
            'penerbit' => $penerbit,
            'tahun_terbit' => $this->faker->numberBetween(2015, 2024),
            'kategori_id' => Kategori::inRandomOrder()->first()?->id ?? Kategori::factory(),
            'stok' => $this->faker->numberBetween(0, 30),
            'sinopsis' => $this->faker->paragraph(3),
        ];
    }
}
