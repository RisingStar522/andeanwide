<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RecipientResource extends JsonResource
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
            'id'            => $this->id,
            'user_id'       => $this->user_id,
            'country'       => $this->country,
            'bank'          => $this->bank,
            'name'          => $this->name,
            'lastname'      => $this->lastname,
            'dni'           => $this->dni,
            'document_type' => $this->document_type,
            'phone'         => $this->phone,
            'email'         => $this->email,
            'bank_name'     => $this->bank_name,
            'bank_account'  => $this->bank_account,
            'account_type'  => $this->account_type,
            'bank_code'     => $this->bank_code,
            'address'       => $this->address,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}
