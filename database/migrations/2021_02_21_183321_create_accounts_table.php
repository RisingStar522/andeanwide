<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('country_id');
            $table->unsignedBigInteger('currency_id');
            $table->unsignedBigInteger('bank_id');
            $table->string('label', 255);
            $table->string('bank_name', 255)->nullable();
            $table->string('bank_account', 255);
            $table->string('account_name', 255);
            $table->string('account_type', 255);
            $table->text('document_number', 255);
            $table->text('description')->nullable();
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
        Schema::dropIfExists('accounts');
    }
}
