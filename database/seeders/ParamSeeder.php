<?php

namespace Database\Seeders;

use App\Models\Param;
use Illuminate\Database\Seeder;

class ParamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Param::factory()->create([
            'name'          => 'tax',
            'label'         => 'Impuesto al Valor Agregado (%)',
            'description'   => 'Impuesto al Valor Agregado en porcentaje (%)',
            'value'         => 19,
            'value_type'    => 'decimal'
        ]);

        Param::factory()->create([
            'name'          => 'transaction_cost',
            'label'         => 'Costo por transacción (%)',
            'description'   => 'Costo por transacción en porcentaje (%)',
            'value'         => 2,
            'value_type'    => 'decimal'
        ]);

        Param::factory()->create([
            'name'          => 'show_priorities',
            'label'         => 'Mostrar Prioridades',
            'description'   => 'Permite mostrar prioridades a usuarios.',
            'value'         => true,
            'value_type'    => 'boolean'
        ]);

        Param::factory()->create([
            'name' => 'defaultRateApi',
            'label' => 'Api para tasa de cambio por defecto',
            'value' => 'CurrencyLayerApi',
            'value_type' => 'string'
        ]);
    }
}
