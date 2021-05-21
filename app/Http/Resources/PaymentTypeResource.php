<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentTypeResource extends JsonResource
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
            'label'         => $this->label,
            'description'   => $this->description,
            'class_name'    => $this->class_name,
            'is_active'     => $this->is_active,
            'updated_at'    => $this->updated_at,
            'created_at'    => $this->created_at,
        ];
    }
}
