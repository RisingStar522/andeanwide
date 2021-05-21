<?php

namespace App\Providers;

use App\Providers\PayoutNotificationArrived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdatePayoutOrderStatus
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
        //
    }
}
