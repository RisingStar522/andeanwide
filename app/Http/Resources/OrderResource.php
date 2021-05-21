<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'                => $this->id,
            'user_id'           => $this->user_id,
            'recipient'         => new RecipientResource($this->recipient),
            'pair'              => new PairResource($this->pair),
            'priority'          => new PriorityResource($this->priority),
            'payment'           => new PaymentResource($this->payment),
            'remitter'          => new RemitterResource($this->remitter),
            'sended_amount'     => $this->sended_amount,
            'received_amount'   => $this->received_amount,
            'rate'              => $this->rate,
            'payment_amount'    => $this->payment_amount,
            'payment_method'    => $this->payment_method,
            'transaction_cost'  => $this->transaction_cost,
            'priority_cost'     => $this->priority_cost,
            'tax'               => $this->tax,
            'tax_pct'           => $this->tax_pct,
            'total_cost'        => $this->total_cost,
            'payment_code'      => $this->payment_code,
            'filled_at'         => $this->filled_at,
            'verified_at'       => $this->verified_at,
            'rejected_at'       => $this->rejected_at,
            'expired_at'        => $this->expired_at,
            'completed_at'      => $this->completed_at,
            'payed_at'          => $this->payed_at,
            'payout_id'         => $this->payout_id,
            'complianced_at'    => $this->complianced_at,
            'status'            => $this->status,
            'payout_status'     => $this->payout_status,
            'payout_status_code' => $this->payout_status_code,
            'rejection_reason'  => $this->rejection_reason,
            'observation'       => $this->observation,
            'purpose'           => $this->purpose,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}
