<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRemittersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('remitters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            // Document data
            $table->string('fullname');
            $table->string('document_type');
            $table->string('dni');
            $table->dateTime('issuance_date')->nullable();
            $table->dateTime('expiration_date')->nullable();
            $table->dateTime('dob')->nullable();
            $table->unsignedBigInteger('issuance_country_id');
            // Address data
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            // Contact Data
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            // Document images
            $table->string('document_url')->nullable();
            $table->string('reverse_url')->nullable();
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
        Schema::dropIfExists('remitters');
    }
}
