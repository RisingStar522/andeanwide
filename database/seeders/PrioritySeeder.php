<?php

namespace Database\Seeders;

use App\Models\Priority;
use Illuminate\Database\Seeder;

class PrioritySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Priority::factory()->create([
            'name' => 'normal',
            'label' => 'Normal',
            'sublabel' => 'Prioridad Normal (Efectivo en 3 días hábiles)',
            'description' => 'Prioridad Normal',
            'cost_pct' => 1,
        ]);

        Priority::factory()->create([
            'name' => 'inmediata',
            'label' => 'Inmediata',
            'sublabel' => 'Prioridad Inmediata (Efectivo en 1 día hábil)',
            'description' => 'Prioridad Inmediata en condiciones normales efectivo el mismo día.',
            'cost_pct' => 5,
        ]);
    }
}
