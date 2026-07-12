<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Urutan PENTING: UserSeeder -> AtributKlasifikasiSeeder -> KategoriAtributSeeder
     * (kategori butuh atribut sudah ada).
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            AtributKlasifikasiSeeder::class,
            KategoriAtributSeeder::class,
            DataTrainingSeeder::class,
            WargaSeeder::class,
        ]);
    }
}
