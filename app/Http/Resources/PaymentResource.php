<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
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
            'id'                    => $this->id,
            'order_id'              => $this->order_id,
            'user_id'               => $this->user_id,
            'account_id'            => $this->account_id,
            'transaction_number'    => $this->transaction_number,
            'transaction_date'      => $this->transaction_date,
            'payment_amount'        => $this->payment_amount,
            'payment_code'          => $this->payment_code,
            'observation'           => $this->observation,
            'image_url'             => $this->image_url,
            'verified_at'           => $this->verified_at,
            'rejected_at'           => $this->rejected_at,
            'payment_type'          => new PaymentTypeResource($this->paymentType),
            'account'               => new AccountResource($this->account),
            'created_at'            => $this->created_at,
            'updated_at'            => $this->updated_at,
        ];
    }
}
