<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
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
            'address' => $this->address,
            'address_ext' => $this->address_ext,
            'country' => new CountryResource($this->country),
            'state' => $this->state,
            'city' => $this->city,
            'cod' => $this->cod,
            'image' => $this->image,
            'verified_at' => $this->verified_at,
            'rejected_at' => $this->rejected_at,
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
        ];
    }
}
