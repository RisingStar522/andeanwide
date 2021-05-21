<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ExchangeRateResource extends JsonResource
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
            'api_rate' => $this->api_rate,
            'bid' => $this->bid,
            'bid_to_corps' => $this->bid_to_corps,
            'bid_to_imports' => $this->bid_to_imports,
            'created_at' => $this->created_at,
            'update_at' => $this->updated_at
        ];
    }
}
