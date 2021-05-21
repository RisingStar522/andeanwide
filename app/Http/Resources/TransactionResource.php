<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'id' => $this->id,
            'user' => [
                'id' => $this->user_id,
                'username' => $this->user->name,
                'email' => $this->user->email,
                'account_type' => $this->user->account_type,
                'status' => $this->user->status,
                'is_agent' => $this->user->is_agent
            ],
            'order' => [
                'id' => $this->order_id,
                'amount_to_pay' => $this->order ? $this->order->payment_amount : null,
                'created_at' => $this->order ? $this->order->created_at : null
            ],
            'account' => new AccountResource($this->account),
            'currency' => new CurrencyResource($this->currency),
            'external_id' => $this->external_id,
            'type' => $this->type,
            'amount' => $this->amount,
            'amount_usd' => $this->amount_usd,
            'note' => $this->note,
            'rejected_at' => $this->rejected_at,
            'transaction_date' => $this->transaction_date,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
