<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['stadt' => 'Stadtleben', 'vereine' => 'Vereine', 'kultur' => 'Kultur', 'sport' => 'Sport', 'handel' => 'Handel', 'verkehr' => 'Verkehr'] as $slug => $name) {
            Category::query()->firstOrCreate(['slug' => $slug], ['name' => $name]);
        }
    }
}
