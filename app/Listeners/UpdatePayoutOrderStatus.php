<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Http;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\PayoutNotificationArrived;
use Illuminate\Contracts\Queue\ShouldQueue;

// class UpdatePayoutOrderStatus
class UpdatePayoutOrderStatus implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  PayoutNotificationArrived  $event
     * @return void
     */
    public function handle(PayoutNotificationArrived $event)
    {
        $url = config('app.url') . '/api/orders/' . $event->order->id . '/check-payout';
        Http::post($url);
    }
}
