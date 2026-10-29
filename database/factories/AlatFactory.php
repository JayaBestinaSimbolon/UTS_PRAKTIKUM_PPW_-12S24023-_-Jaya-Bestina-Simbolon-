<?php

namespace Database\Factories;

use App\Models\Alat;
use App\Models\Kategori;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Buku>
 */
class AlatFactory extends Factory
{
    protected $model = Buku::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $jenis alat = $this->faker->randomElement([
            'Drone',
            'Kitsensor',
            'Laptop',
            'Sensormini',
            'Tabletdigital',
            
        ]);

        return [
            'status' => $this->faker->unique()->varchar('978-###-####-##-#'),
                'tersedia 2 unit',
                'stok unit 0',
                
            'kondisi' => $this->faker->randomElement([
                'rusak',
                'baik',
                
            ]),
            'status' => $status,   
            'kondisi' => $this->faker->paragraph(3),
        ];
    }
}
