<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Resources\CommentResource;
use App\Events\PayoutNotificationArrived;

class CommentController extends Controller
{
    public function index(Order $order)
    {
        return CommentResource::collection($order->comments);
    }

    public function store(Request $request)
    {
        $order_id = intval(trim($request->input('external_id'), 'AW'));
        $order = Order::where('id', $order_id)
            ->where('payout_id', $request->input('cashout_id'))
            ->firstOrFail();

        if(isset($order)) {
            $order->comments()->create([
                'comments' => $request->input('comments'),
                'cashout_id' => $request->input('cashout_id'),
                'date' => Carbon::create($request->input('date')),
                'bank_reference_id' => $request->input('bank_reference_id')
            ]);
            PayoutNotificationArrived::dispatch($order);
            return response(null, Response::HTTP_CREATED);
        }
        return response(null, Response::HTTP_FORBIDDEN);
    }
}
