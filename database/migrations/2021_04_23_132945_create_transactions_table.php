<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('account_id')->nullable();       // account id
            $table->unsignedBigInteger('order_id')->nullable();         // order id
            $table->string('external_id', 100)->nullable();             // bank transaction id
            $table->enum('type', ['income', 'outcome']);                // transaction type
            $table->decimal('amount');
            $table->decimal('amount_usd')->nullable();
            $table->unsignedBigInteger('currency_id');
            $table->text('note')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('transaction_date')->nullable();
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
        Schema::dropIfExists('transactions');
    }
}
