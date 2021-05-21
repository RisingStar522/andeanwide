<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AccountIncomeResource extends JsonResource
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
            'account' => new AccountResource($this->account),
            'user' => new UserResource($this->user),
            'origin' => $this->origin,
            'transaction_number' => $this->transaction_number,
            'transaction_date' => $this->transaction_date,
            'amount' => $this->amount,
            'rejected_at' => $this->rejected_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
