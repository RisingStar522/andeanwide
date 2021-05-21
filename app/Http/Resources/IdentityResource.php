<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class IdentityResource extends JsonResource
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
            'issuance_country' => new CountryResource($this->issuanceCountry),
            'nationality_country' => new CountryResource($this->nationalityCountry),
            'identity_number' => $this->identity_number,
            'document_number' => $this->document_number,
            'document_type' => $this->document_type,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'fullname' => $this->firstname . " " . $this->lastname,
            'dob' => $this->dob,
            'issuance_date' => $this->issuance_date,
            'expiration_date' => $this->expiration_date,
            'gender' => $this->gender,
            'activity' => $this->activity,
            'position' => $this->position,
            'profession' => $this->profession,
            'state' => $this->state,
            'verified_at' => $this->verified_at,
            'rejection_reasons' => $this->rejection_reasons,
            'rejected_at' => $this->rejected_at,
            'front_image_url' => $this->front_image_url,
            'back_image_url' => $this->back_image_url,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
