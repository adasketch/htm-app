<?php

namespace Database\Seeders;

use App\Models\Property;
use Illuminate\Database\Seeder;

class PropertySeeder extends Seeder
{
    public function run(): void
    {
        Property::firstOrCreate(['name' => 'The Westin Surabaya'], ['timezone' => 'Asia/Jakarta']);
        Property::firstOrCreate(['name' => 'Four Points Surabaya Pakuwon Indah'], ['timezone' => 'Asia/Jakarta']);
    }
}
