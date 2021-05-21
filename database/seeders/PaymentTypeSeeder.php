<?php

namespace Database\Seeders;

use App\Models\PaymentType;
use Illuminate\Database\Seeder;

class PaymentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        PaymentType::factory()->create([
            'name' => 'bank_tansfer',
            'class_name' => 'BankTransfer',
            'label' => 'Transferencia Bancaria',
            'description' => 'Transferencia Bancaria',
            'is_active' => true
        ]);

        PaymentType::factory()->create([
            'name' => 'd_local_smartfield',
            'class_name' => 'DLocalSmartField',
            'label' => 'Pago con TDC',
            'description' => 'Servicio de SmartField de d-local',
            'is_active' => true
        ]);

        PaymentType::factory()->create([
            'name' => 'otros_pagos',
            'class_name' => 'OtrosPagos',
            'label' => 'Otros Pagos',
            'description' => 'Otros Pagos',
            'is_active' => true
        ]);

        PaymentType::factory()->create([
            'name' => 'balance_payment',
            'class_name' => 'BalancePayment',
            'label' => 'Pago con balance disponible',
            'description' => 'Pagar con el balance disponible en mi cuenta.',
            'is_active' => true
        ]);
    }
}
