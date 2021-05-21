<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRecipientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('recipients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('country_id');
            $table->unsignedBigInteger('bank_id');
            $table->string('name', 255);
            $table->string('lastname', 255);
            $table->string('dni', 255);
            $table->string('document_type', 255);
            $table->string('phone', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('bank_name', 255)->nullable();
            $table->string('bank_account', 255)->nullable();
            $table->string('account_type', 5)->nullable();
            $table->string('bank_code', 255)->nullable();
            $table->string('address', 255)->nullable();
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
        Schema::dropIfExists('recipients');
    }
}
