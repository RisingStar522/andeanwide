<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RemitterResource extends JsonResource
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
            'user_id' => $this->user_id,
            'fullname' => $this->fullname,
            'document_type' => $this->document_type,
            'dni' => $this->dni,
            'issuance_date' => $this->issuance_date,
            'expiration_date' => $this->expiration_date,
            'dob' => $this->dob,
            'issuance_country_id' => $this->issuance_country_id,
            'issuance_country' => new CountryResource($this->issuance_country),
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country_id' => $this->country_id,
            'country' => new CountryResource($this->country),
            'phone' => $this->phone,
            'email' => $this->email,
            'document_url' => $this->document_url,
            'reverse_url' => $this->reverse_url,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_atl,
        ];
    }
}
