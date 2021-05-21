<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class OrderExpiracy implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if(is_null($this->order->payment)) {
            $this->order->rejected_at = now();
            $this->order->observation = "La orden No. " . str_pad($this->order->id, 6, 0, STR_PAD_LEFT) . " ha sido eliminada por el sistema, la orden alcanzo su tiempo de expiración.";
            $this->order->rejection_reason = "La orden No. " . str_pad($this->order->id, 6, 0, STR_PAD_LEFT) . " ha sido eliminada por el sistema, la orden alcanzo su tiempo de expiración.";
            $this->order->save();
        }
    }
}
