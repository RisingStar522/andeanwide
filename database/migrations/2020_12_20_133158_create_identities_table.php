<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIdentitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('identities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('issuance_country_id');
            $table->unsignedBigInteger('nationality_country_id');
            $table->string('identity_number');
            $table->enum('document_type', ['dni', 'passport'])->default('dni');
            $table->string('firstname');
            $table->string('lastname');
            $table->dateTime('dob')->nullable();
            $table->dateTime('issuance_date')->nullable();
            $table->dateTime('expiration_date')->nullable();
            $table->enum('gender', ['M', 'F'])->nullable();
            $table->string('profession')->nullable();
            $table->string('activity')->nullable();
            $table->string('position')->nullable();
            $table->enum('state', ['NA', 'single', 'married', 'divorced'])->default('NA');
            $table->dateTime ('verified_at')->nullable();
            $table->dateTime ('rejected_at')->nullable();
            $table->string('front_image_url')->nullable();
            $table->string('back_image_url')->nullable();
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
        Schema::dropIfExists('identities');
    }
}
