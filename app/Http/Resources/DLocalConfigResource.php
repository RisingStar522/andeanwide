<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DLocalConfigResource extends JsonResource
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
            'name' => $this->name,
            'url' => $this->url,
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => $this->grant_type,
            'access_token' => $this->access_token,
        ];
    }
}
