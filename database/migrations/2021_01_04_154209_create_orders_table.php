<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('recipient_id');
            $table->unsignedBigInteger('pair_id');
            $table->unsignedBigInteger('priority_id');
            $table->double('sended_amount');
            $table->double('received_amount');
            $table->double('rate');
            $table->double('payment_amount');
            $table->double('transaction_cost');
            $table->double('priority_cost')->default(0);
            $table->double('tax');
            $table->double('tax_pct');
            $table->double('total_cost');
            $table->string('payment_code')->nullable();
            $table->timestamp('filled_at')->nullable();     // La orden ha sido completada por el usuario
            $table->timestamp('verified_at')->nullable();   // Cuando ha sido verificado el pago de la orden
            $table->timestamp('rejected_at')->nullable();   // La orden no procede y es cancelada por administracion
            $table->timestamp('expired_at')->nullable();    // La orden cumplio su tiempo, y fue cancelada
            $table->timestamp('completed_at')->nullable();  // La orden ha sido completada por administracion
            $table->timestamp('payed_at')->nullable();      // La orden ha sido pagada
            $table->unsignedBigInteger('payout_id')->nullable();
            $table->string('payout_status')->nullable();
            $table->string('payout_status_code')->nullable();
            $table->timestamp('complianced_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->string('observation')->nullable();
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
        Schema::dropIfExists('orders');
    }
}
