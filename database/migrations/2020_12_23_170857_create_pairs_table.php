<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePairsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pairs', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->string('name',10);
            $table->string('api_class')->nullable();
            $table->double('default_amount')->default(0);
            $table->double('min_amount')->default(0);
            $table->text('observation')->nullable();
            $table->unsignedBigInteger('base_id');
            $table->unsignedBigInteger('quote_id');
            $table->double('offset')->default(0);
            $table->double('offset_to_corps')->default(0);
            $table->double('offset_to_imports')->default(0);
            $table->enum('offset_by', ['point', 'percentage'])->default('percentage');
            $table->double('min_pip_value')->default(1);
            $table->boolean('show_inverse')->default(false);
            $table->double('max_tier_1')->default(0);
            $table->double('max_tier_2')->default(0);
            $table->double('more_rate')->nullable();
            $table->boolean('is_more_enabled')->default(false);
            $table->integer('decimals')->default(4);
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
        Schema::dropIfExists('pairs');
    }
}
