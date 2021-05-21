<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PairResource extends JsonResource
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
            'id'                => $this->id,
            'is_active'         => $this->is_active,
            'is_default'        => $this->is_default,
            'default_amount'    => $this->default_amount,
            'min_amount'        => $this->min_amount,
            'name'              => $this->name,
            'api_class'         => $this->api_class,
            'observation'       => $this->observation,
            'base'              => new CurrencyResource($this->base),
            'quote'             => new CurrencyResource($this->quote),
            'base_id'           => $this->base_id,
            'quote_id'          => $this->quote_id,
            'offset_by'         => $this->offset_by,
            'offset'            => $this->offset,
            'offset_to_corps'   => $this->offset_to_corps,
            'offset_to_imports' => $this->offset_to_imports,
            'min_pip_value'     => $this->min_pip_value,
            'show_inverse'      => $this->show_inverse,
            'max_tier_1'        => $this->max_tier_1,
            'max_tier_2'        => $this->max_tier_2,
            'personal_cost_pct' => $this->personal_cost_pct ?? 0,
            'corps_cost_pct'    => $this->corps_cost_pct ?? 0,
            'imports_cost_pct'  => $this->imports_cost_pct ?? 0,
            'has_fixed_rate'    => $this->has_fixed_rate ?? false,
            'personal_fixed_rate'   => $this->personal_fixed_rate ?? 0,
            'corps_fixed_rate'  => $this->corps_fixed_rate ?? 0,
            'imports_fixed_rate'=> $this->imports_fixed_rate ?? 0,
            'more_rate'         => $this->more_rate,
            'decimals'          => $this->decimals,
            'is_more_enabled'   => $this->is_more_enabled,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}
