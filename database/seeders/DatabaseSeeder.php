<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // A consistent history, built through the application's own actions.
            DemoIncidentsSeeder::class,
            // Then a few incidents broken on purpose with raw SQL, for the diagnostic exercise (see README).
            InconsistenciesSeeder::class,
        ]);
    }
}
