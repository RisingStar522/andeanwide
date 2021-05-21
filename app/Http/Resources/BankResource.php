<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BankResource extends JsonResource
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
            'country_id'    => $this->country_id,
            'name'          => $this->name,
            'abbr'          => $this->abbr,
            'code'          => $this->code,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at
        ];
    }
}
