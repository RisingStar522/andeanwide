<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Traits\HasSaveImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\PaymentResource;

class PaymentController extends Controller
{
    use HasSaveImage;

    public function index()
    {
        $user = Auth::user();
        $payments = Payment::where('user_id', $user->id)->orderBy('created_at', 'desc')->get();

        return PaymentResource::collection($payments);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'order_id' => 'required|exists:orders,id',
            'payment_type_id' => 'required|exists:payment_types,id',
            'transaction_number' => 'required',
            'transaction_date' => 'required|date',
            'payment_amount' => 'required|numeric',
            'image' => 'nullable|image|max:5120'
        ]);

        $image_url = $this->saveImage($request->image, Auth::user(), 'payments') ??  null;

        $payment = Payment::create([
            'payment_type_id'       => $request->payment_type_id,
            'order_id'              => $request->order_id,
            'user_id'               => Auth::id(),
            'account_id'            => $request->account_id,
            'transaction_number'    => $request->transaction_number,
            'transaction_date'      => $request->transaction_date,
            'payment_amount'        => $request->payment_amount,
            'payment_code'          => $request->payment_code,
            'observation'           => $request->observation,
            'image_url'             => $image_url,
            'verified_at'           => null,
            'rejected_at'           => null,
        ]);

        $order = Order::find($request->order_id);
        $order->filled_at = now();
        $order->save();

        return new PaymentResource($payment);
    }

    public function show(Payment $payment)
    {
        // dump($payment->toArray());
        if($payment->user_id == Auth::id()) {
            return new PaymentResource($payment);
        }
        abort(404);
    }
}
