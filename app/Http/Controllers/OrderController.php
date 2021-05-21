<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Pair;
use App\Models\User;
use App\Models\Order;
use App\Models\Param;
use App\Models\Priority;
use App\Jobs\OrderExpiracy;
use App\Models\PaymentType;
use Illuminate\Support\Str;
use App\Exports\OrderExport;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Barryvdh\DomPDF\Facade as PDF;
use App\Http\Requests\OrderRequest;
use App\Actions\Helpers\OrderAction;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Resources\OrderResource;
use App\Actions\Helpers\PaymentAction;
use App\Actions\Helpers\TransactionAction;

class OrderController extends Controller
{

    public function index()
    {
        $user = Auth::user();
        $orders = Order::where('user_id', $user->id)->orderBy('updated_at', 'desc')->paginate();
        return OrderResource::collection($orders);
    }

    public function store(OrderRequest $request)
    {
        $request->validated();
        $user = User::find(Auth::id());
        $pair = Pair::find($request->input('pair_id'));
        $rate = $request->input('rate');

        if(!OrderAction::validateRate($pair, $rate, $user->account_type)) {
            return response([
                'message' => 'The given data is invalid.',
                'errors' => [
                    'rate' => 'The rate field has changed, is not possible to create a new order.'
                ]
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $paymentAmount = $request->input('payment_amount');
        $transaction = Param::where('name', 'transaction_cost')->first();
        $tax = Param::where('name', 'tax')->first();
        $priority = Priority::find($request->input('priority_id'));
        $params = OrderAction::calculateCosts($paymentAmount, $transaction->value, $tax->value, $priority->cost_pct);
        $amount_to_receive = OrderAction::calculateAmountToReceive($params->amount_to_send, $rate);

        $order = Order::create([
            'user_id'           => $user->id,
            'recipient_id'      => $request->input('recipient_id'),
            'pair_id'           => $request->input('pair_id'),
            'priority_id'       => $request->input('priority_id'),
            'sended_amount'     => $params->amount_to_send,
            'received_amount'   => $amount_to_receive,
            'usd_amount'        => OrderAction::convertAmountToUsd($pair->quote->symbol, $amount_to_receive),
            'rate'              => $rate,
            'payment_amount'    => $paymentAmount,
            'transaction_cost'  => $params->transaction_cost,
            'priority_cost'     => $params->priority_cost,
            'tax'               => $params->tax_cost,
            'tax_pct'           => $tax->value,
            'total_cost'        => $params->total_cost,
            'payment_code'      => strtoupper(Str::random(12)),
            'filled_at'         => null,
            'verified_at'       => null,
            'rejected_at'       => null,
            'expired_at'        => null,
            'completed_at'      => null,
            'complianced_at'    => null,
            'rejection_reason'  => null,
            'observation'       => null,
            'remitter_id'       => $request->input('remitter_id'),
            'purpose'           => $request->input('purpose')
        ]);

        if($order->payment_amount <= $user->availableAmount) {
            PaymentAction::createPayment($order);
            TransactionAction::createOrderTransaction($order);
        }

        OrderExpiracy::dispatch($order)->delay(now()->addHours(config('services.expirations.order', 24)));

        return new OrderResource($order);
    }

    public function show(Order $order)
    {
        $user = Auth::user();
        if ($order->user_id == $user->id) {
            return new OrderResource($order);
        }
        return response('Forbidden', Response::HTTP_FORBIDDEN);
    }

    public function indexAdmin(Request $request)
    {
        $orders = Order::whereNotNull('filled_at');
        $orders->orderBy('created_at', 'desc');
        return OrderResource::collection($orders->paginate());
    }

    public function showAdmin(Order $order)
    {
        return new OrderResource($order);
    }

    public function verifyPayment(Request $request, Order $order)
    {
        if($order->canValidatePayment) {
            $order->payment->verified_at = now();
            $order->payment->observation = $request->observation;
            $order->payment->save();
            return new OrderResource($order);
        }
        return response(null, Response::HTTP_FORBIDDEN);
    }

    public function rejectPayment(Request $request, Order $order)
    {
        if($order->canValidatePayment) {
            $order->payment->rejected_at = now();
            $order->payment->observation = $request->observation;
            $order->payment->save();
            $order->rejected_at = now();
            $order->rejection_reason = $request->observation;
            $order->save();
            return new OrderResource($order);
        }
        return response(null, Response::HTTP_FORBIDDEN);
    }

    public function verifyOrder(Order $order)
    {
        if($order->canValidateOrder && is_null($order->verified_at) && $order->payment && $order->payment->verified_at) {
            $order->verified_at = now();
            $order->save();
            return new OrderResource($order);
        }
        return response(null, Response::HTTP_FORBIDDEN);
    }

    public function rejectOrder(Request $request, Order $order)
    {
        if($order->canValidateOrder) {
            $paymentType = PaymentType::where('name','balance_payment')->first();
            if($order->payment->payment_type_id == $paymentType->id) {
                PaymentAction::rejectOrder($order);
                $order->fresh();
            } else {
                $order->rejected_at = now();
                $order->save();
            }
            return new OrderResource($order);
        }
        return response(null, Response::HTTP_FORBIDDEN);
    }

    public function payWithBalance(Order $order)
    {
        $user = User::find(Auth::id());
        if($user->id == $order->user_id && $order->payment_amount <= $user->availableAmount){
            if(is_null($order->filled_at) && is_null($order->payment)) {
                PaymentAction::createPayment($order);
                TransactionAction::createOrderTransaction($order);
                return new OrderResource($order);
            }
        }
        return response('Forbidden', Response::HTTP_FORBIDDEN);
    }

    public function generateOrderDocument(Order $order)
    {
        if($order->user_id === Auth::id()) {
            $status = [
                'EXPIRED' =>'ORDEN EXPIRO',
                'PAYOUT_CANCELLED' =>'CANCELADO',
                'PAYMENT_REJECTED' =>'PAGO RECHAZADO',
                'ORDER_REJECTED' =>'ORDEN RECHAZADA',
                'ORDER_COMPLETED' =>'ORDEN COMPLETADA',
                'PAYOUT_RECEIVED' =>'PAGO EN PROCESO',
                'PAYOUT_COMPLETED' =>'ORDEN COMPLETADA',
                'PAYOUT_REJECTED' =>'PAGO RECHAZADO',
                'PAYOUT_CANCELLED' =>'CANCELADO',
                'PAYOUT_REJECTED' =>'ORDEN_RECHADA',
                'PAYOUT_DELIVERED' =>'ENTREGADO',
                'PAYOUT_ONHOLD' =>'RETENIDO',
                'ORDER_VERIFIED' =>'EN PROCESO',
                'PAYMENT_VERIFIED' =>'EN PROCESO',
                'PAYMENT_VERIFIED' =>'EN PROCESO',
                'FILLED' =>'ORDEN ENVIADA',
                'INCOMPLETED' =>'INCOMPLETA',
            ];
            $pdf = PDF::loadView('pdf.order', array('order' => $order, 'status' => $status[$order->status]));
            return $pdf->download('orden-' . Str::padLeft($order->id, 6, '0') .'.pdf');
        }
        return response(null, Response::HTTP_FORBIDDEN);
    }

    public function export(Request $request)
    {
        $from = $request->query('from') ? Carbon::create($request->query('from')) : null;
        $to = $request->query('to') ? Carbon::create($request->query('to')) : null;
        $filename = now()->timestamp;
        return Excel::download(new OrderExport($from, $to), $filename . '.xlsx');
    }
}
