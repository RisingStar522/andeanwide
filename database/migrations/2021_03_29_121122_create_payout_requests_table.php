<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayoutRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payout_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('external_id')->nullable();
            $table->string('document_id')->nullable();
            $table->string('document_type')->nullable();
            $table->string('beneficiary_name')->nullable();
            $table->string('beneficiary_lastname')->nullable();
            $table->string('country')->nullable();
            $table->string('bank_code')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('account_type')->nullable();
            $table->string('amount')->nullable();
            $table->string('address')->nullable();
            $table->string('currency')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('purpose')->nullable();
            $table->string('remitter_fullname')->nullable();
            $table->string('remitter_document')->nullable();
            $table->string('remitter_address')->nullable();
            $table->string('remitter_city')->nullable();
            $table->string('remitter_country')->nullable();
            $table->string('notification_url')->nullable();
            $table->string('request_url')->nullable();
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
        Schema::dropIfExists('payout_requests');
    }
}
