<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class JabatanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Jabatan::insert([
            ['id' => 1, 'name' => 'Sekretaris'],
            ['id' => 2, 'name' => 'Kepala Subbagian'],
            ['id' => 3, 'name' => 'Staf'],
        ]);
    }
}
