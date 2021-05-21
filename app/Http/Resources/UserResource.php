<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'name'                  => $this->name,
            'email'                 => $this->email,
            'roles'                 => $this->roles,
            'identity'              => new IdentityResource($this->identity),
            'address'               => new AddressResource($this->address),
            'created_at'            => $this->created_at,
            'updated_at'            => $this->updated_at,
            'balance'               => $this->balance,
            'balance_credit_limit'  => $this->balance_credit_limit,
            'balance_currency'      => $this->currency,
            'available_amount'      => $this->available_amount,
            'account_type'          => $this->account_type,
            'fullname'              => $this->identity ? $this->identity->firstname . " " . $this->identity->lastname : null,
            'status'                => $this->status,
            'email_verified_at'     => $this->email_verified_at,
            'company'               => new CompanyResource($this->company)
        ];
    }
}
