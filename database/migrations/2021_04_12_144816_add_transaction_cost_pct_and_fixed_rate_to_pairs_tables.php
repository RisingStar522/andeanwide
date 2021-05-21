<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTransactionCostPctAndFixedRateToPairsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pairs', function (Blueprint $table) {
            $table->double('personal_cost_pct')->nullable();
            $table->double('corps_cost_pct')->nullable();
            $table->double('imports_cost_pct')->nullable();
            $table->boolean('has_fixed_rate')->default(false);
            $table->double('personal_fixed_rate')->nullable();
            $table->double('corps_fixed_rate')->nullable();
            $table->double('imports_fixed_rate')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pairs', function (Blueprint $table) {
            //
        });
    }
}
