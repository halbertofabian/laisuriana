<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SeguridadBaseSeeder::class,
            OperacionBaseSeeder::class,
            ComercialBaseSeeder::class,
            ChecklistBaseSeeder::class,
        ]);
    }
}
