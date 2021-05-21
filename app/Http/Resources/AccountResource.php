<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
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
            'is_active' => $this->is_active,
            'country' => new CountryResource($this->country),
            'currency' => new CurrencyResource($this->currency),
            'bank' => $this->bank,
            'label' => $this->label,
            'bank_name' => $this->bank_name,
            'bank_account' => $this->bank_account,
            'account_name' => $this->account_name,
            'account_type' => $this->account_type,
            'description' => $this->description,
            'document_number' => $this->document_number,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
