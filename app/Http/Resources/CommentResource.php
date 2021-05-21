<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
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
            'comments' => $this->comments,
            'cashout_id' => $this->cashout_id,
            'date' => $this->date,
            'bank_reference_id' => $this->bank_reference_id,
            'commentable_id' => $this->commentable_id,
            'commentable_type' => $this->commentable_type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
