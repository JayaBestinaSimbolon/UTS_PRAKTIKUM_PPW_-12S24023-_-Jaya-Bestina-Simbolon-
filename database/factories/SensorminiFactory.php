<?php

namespace Database\Factories;

use App\Models\Sensormini;
use App\Models\Kategori;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sensormin>
 */
class Sensormini extends Factory
{
    protected $model = Sensormin::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {