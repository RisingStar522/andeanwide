<?php

namespace App\Actions\Helpers;

use App\Models\User;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentType;
use Illuminate\Support\Facades\DB;

class PaymentAction
{


    static public function createPayment(Order $order)
    {
        $payment = DB::transaction(function () use ($order) {
            if($order->filled_at){
                return null;
            }

            $paymentType = PaymentType::where('name','balance_payment')->firstOrFail();
            $user = User::findOrFail($order->id);
            $payment = Payment::create([
                'order_id' => $order->id,
                'user_id' => $user->id,
                'payment_code' => $order->code,
                'transaction_number' => $order->payment_code,
                'transaction_date' => now(),
                'payment_amount' => $order->payment_amount,
                'payment_type_id' => $paymentType->id,
                'verified_at' => now()
            ]);

            $order->filled_at = now();
            $order->save();

            $user->balance -= $order->payment_amount;
            $user->save();
            return $payment;
        });

        return $payment;
    }

    static public function rejectPayment(Order $order, string $observation='Pago Rechazada')
    {
        $payment = DB::transaction(function () use ($order, $observation) {
            if($order->canValidatePayment) {
                $order->payment->rejected_at = now();
                $order->payment->observation = $observation;
                $order->payment->save();

                self::rejectOrder($order, $observation);

                return $order->payment;
            }
            return null;
        });
        return $payment;
    }

    public static function rejectOrder(Order $order, string $observation='Orden Rechazada')
    {
        $order->rejected_at = now();
        $order->rejection_reason = $observation;
        $order->save();

        $user = User::find($order->user_id);
        $user->balance += $order->payment_amount;
        $user->save();
    }
}
