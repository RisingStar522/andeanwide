<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
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
            'name' => $this->name,
            'id_number' => $this->id_number,
            'activity' => $this->activity,
            'country_id' => $this->country_id,
            'country' => new CountryResource($this->country),
            'has_politician_history' => $this->has_politician_history,
            'politician_history_carge' => $this->politician_history_carge,
            'politician_history_country_id' => $this->politician_history_country_id,
            'politician_history_country' => new CountryResource($this->politician_history_country),
            'politician_history_from' => $this->politician_history_from,
            'politician_history_to' => $this->politician_history_to,
            'activities' => $this->activities,
            'anual_revenues' => $this->anual_revenues,
            'company_size' => $this->company_size,
            'fund_origins' => $this->fund_origins,
            'verified_at' => $this->verified_at,
            'rejected_at' => $this->rejected_at,
            'rejection_reasons' => $this->rejection_reasons,
            'documents' => $this->documents,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
