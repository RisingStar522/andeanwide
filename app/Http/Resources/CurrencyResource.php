<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CurrencyResource extends JsonResource
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
            'name'          => $this->name,
            'symbol'        => $this->symbol,
            'is_active'     => $this->is_active,
            'can_send'      => $this->can_send,
            'can_receive'   => $this->can_receive,
            'country'       => new CountryResource($this->country),
            'country_id'    => $this->country_id,
            'abbr'          => $this->country->abbr
        ];
    }
}
