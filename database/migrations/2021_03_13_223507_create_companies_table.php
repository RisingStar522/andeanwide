<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name'); // Razon social
            $table->string('id_number'); // RUT
            $table->string('activity'); // Giro o actividad
            $table->unsignedBigInteger('country_id');
            // Declaracion funciones publicas o politicas
            $table->boolean('has_politician_history')->default(false);
            $table->string('politician_history_charge')->nullable();
            $table->unsignedBigInteger('politician_history_country_id')->nullable();
            $table->dateTime('politician_history_from')->nullable();
            $table->dateTime('politician_history_to')->nullable();
            // Actividad de la empresa
            $table->string('activities')->nullable();
            $table->enum('anual_revenues', Array('LT_100M_USD', 'LT_1MM_USD', 'LT_4MM_USD', 'GT_4MM_USD'))->nullable();
            $table->enum('company_size', Array('micro', 'small', 'mid', 'large'))->nullable();
            // Origines de fondos
            // $table->json('funds_origins')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->string('rejection_reasons')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('companies');
    }
}
