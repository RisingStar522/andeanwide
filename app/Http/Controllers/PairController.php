<?php

namespace App\Http\Controllers;

use App\Http\Resources\PairResource;
use App\Models\Pair;
use App\Models\Rate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PairController extends Controller
{
    public function index()
    {
        $pairs = Pair::where('is_active', true)->get();
        return PairResource::collection($pairs);
    }

    public function indexAll()
    {
        $pairs = Pair::paginate();
        return PairResource::collection($pairs);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'              => 'required|unique:pairs,name',
            'base_id'           => 'required|exists:currencies,id',
            'quote_id'          => 'required|exists:currencies,id',
            'default_amount'    => 'nullable|numeric',
            'min_amount'        => 'nullable|numeric',
            'offset'            => 'nullable|numeric',
            'min_pip_value'     => 'nullable|numeric',
            'max_tier_1'        => 'nullable|numeric',
            'max_tier_2'        => 'nullable|numeric',
            'more_rate'         => 'nullable|numeric',
            'offset_by'         => 'nullable|in:point,percentage',
            'decimals'          => 'nullable|numeric'
        ]);

        $pair = Pair::create([
            'is_active'         => $request->input('is_active', true),
            'is_default'        => $request->input('is_default', false),
            'default_amount'    => $request->input('default_amount', 10000),
            'min_amount'        => $request->input('min_amount', 10000),
            'name'              => $request->input('name'),
            'api_class'         => $request->input('api_class'),
            'observation'       => $request->input('observation'),
            'base_id'           => $request->input('base_id'),
            'quote_id'          => $request->input('quote_id'),
            'offset_by'         => $request->input('offset_by', 'percentage'),
            'offset'            => $request->input('offset', 0),
            'offset_to_corps'   => $request->input('offset_to_corps', 0),
            'offset_to_imports' => $request->input('offset_to_imports', 0),
            'min_pip_value'     => $request->input('min_pip_value', 1),
            'show_inverse'      => $request->input('show_inverse'),
            'max_tier_1'        => $request->input('max_tier_1'),
            'max_tier_2'        => $request->input('max_tier_2'),
            'more_rate'         => $request->input('more_rate'),
            'is_more_enabled'   => $request->input('is_more_enabled', false),
            'decimals'          => $request->input('decimals', 2)
        ]);

        return new PairResource($pair);
    }

    public function show(Pair $pair)
    {
        return new PairResource($pair);
    }

    public function update(Request $request, Pair $pair)
    {
        $request->validate([
            'name'              => "required|unique:pairs,name,$request->id,id",
            'base_id'           => 'required|exists:currencies,id',
            'quote_id'          => 'required|exists:currencies,id',
            'default_amount'    => 'nullable|numeric',
            'min_amount'        => 'nullable|numeric',
            'offset'            => 'nullable|numeric',
            'min_pip_value'     => 'nullable|numeric',
            'max_tier_1'        => 'nullable|numeric',
            'max_tier_2'        => 'nullable|numeric',
            'more_rate'         => 'nullable|numeric',
            'offset_by'         => 'nullable|in:point,percentage',
            'decimals'          => 'nullable|numeric',
            'personal_cost_pct' => 'nullable|numeric',
            'corps_cost_pct'    => 'nullable|numeric',
            'imports_cost_pct'  => 'nullable|numeric',
            'has_fixed_rate'    => 'nullable',
            'personal_fixed_rate' => 'nullable|numeric',
            'coprs_fixed_rate'  => 'nullable|numeric',
            'imports_fixed_rate' => 'nullable|numeric',
        ]);

        $pair->name = $request->input('name', $pair->name);
        $pair->is_active = $request->input('is_active', true);
        $pair->is_default = $request->input('is_default', false);
        $pair->api_class = $request->input('api_class', $pair->api_class);
        $pair->default_amount = $request->input('default_amount', $pair->default_amount);
        $pair->min_amount = $request->input('min_amount', $pair->min_amount);
        $pair->observation = $request->input('observation', $pair->observation);
        $pair->base_id = $request->input('base_id', $pair->base_id);
        $pair->quote_id = $request->input('quote_id', $pair->quote_id);
        $pair->offset = $request->input('offset', $pair->offset);
        $pair->offset_to_corps = $request->input('offset_to_corps', $pair->offset_to_corps);
        $pair->offset_to_imports = $request->input('offset_to_imports', $pair->offset_to_imports);
        $pair->offset_by = $request->input('offset_by', $pair->offset_by);
        $pair->min_pip_value = $request->input('min_pip_value', $pair->min_pip_value);
        $pair->show_inverse = $request->input('show_inverse', $pair->show_inverse);
        $pair->max_tier_1 = $request->input('max_tier_1', $pair->max_tier_1);
        $pair->max_tier_2 = $request->input('max_tier_2', $pair->max_tier_2);
        $pair->more_rate = $request->input('more_rate', $pair->more_rate);
        $pair->is_more_enabled = $request->input('is_more_enabled', false);
        $pair->decimals = $request->input('decimals', 2);
        $pair->personal_cost_pct = $request->input('personal_cost_pct', $pair->personal_cost_pct ?? 0);
        $pair->corps_cost_pct = $request->input('corps_cost_pct', $pair->corps_cost_pct ?? 0);
        $pair->imports_cost_pct = $request->input('imports_cost_pct', $pair->imports_cost_pct ?? 0);
        $pair->has_fixed_rate = $request->input('has_fixed_rate', $pair->has_fixed_rate ?? false);
        $pair->personal_fixed_rate = $request->input('personal_fixed_rate', $pair->personal_fixed_rate ?? 0);
        $pair->corps_fixed_rate = $request->input('corps_fixed_rate', $pair->corps_fixed_rate ?? 0);
        $pair->imports_fixed_rate = $request->input('imports_fixed_rate', $pair->imports_fixed_rate ?? 0);
        $pair->save();

        return new PairResource($pair);
    }

    public function destroy(Pair $pair)
    {
        $pair->delete();
        return new PairResource($pair);
    }

    public function updateRates()
    {
        $pairs = Pair::where([
            ['is_active', true],
            ['api_class', 'CurrencyLayerApi']
        ])->get();

        foreach ($pairs as $pair) {
            $source = $pair->base->symbol;
            $currency = $pair->quote->symbol;
            $response = Http::get(config('services.exchange_api.currencylayer.url') . '?access_key=' . config('services.exchange_api.currencylayer.key') . '&currencies=' . $currency . '&source=' . $source . '&format=1');
            if($response->successful())
            {
                $body = $response->json();
                if($body["success"]) {
                    Rate::create([
                        'base_currency_id' => $pair->base->id,
                        'quote_currency_id' => $pair->quote->id,
                        'pair_id' => $pair->id,
                        'pair_name' => $pair->name,
                        'quote' => $body['quotes'][$source . $currency],
                        'api_timestamp' => Carbon::createFromTimestamp($body['timestamp']),
                    ]);
                }
            }
        }
        return $pairs;
    }
}
