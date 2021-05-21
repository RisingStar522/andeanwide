<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Pair;
use App\Models\Param;
use App\Models\Currency;
use App\Actions\Helpers\RateActions;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class RateActionsTest extends TestCase
{
    use DatabaseMigrations, RefreshDatabase;

    /** @test */
    public function rate_action_can_call_currency_layer()
    {
        Http::fake([
            'apilayer.net/*' => Http::response([
                'success' => true,
                'timestamp' => 1,
                'source' => 'USD',
                'quotes' => [
                    'USDCLP' => 700,
                ]
            ], 200, ['Headers'])
        ]);

        $base = Currency::factory()->create(['name' => 'USD', 'symbol' => 'USD']);
        $quote = Currency::factory()->create(['name' => 'CLP', 'symbol' => 'CLP']);
        $pair = Pair::factory()->create([
            'base_id' => $base->id,
            'quote_id' => $quote->id,
            'offset' => 1,
            'offset_to_corps' => 2,
            'offset_to_imports' => 3,
            'api_class' => 'CurrencyLayerApi'
        ]);

        $rates = RateActions::getExchangeRate($pair);

        $this->assertNotNull($rates);
        $this->assertNotNull($rates->api_rate);
        $this->assertNotNull($rates->bid);
        $this->assertNotNull($rates->bid_to_corps);
        $this->assertNotNull($rates->bid_to_imports);
        $this->assertEquals(700, $rates->api_rate);
        $this->assertEquals(707, $rates->bid);
        $this->assertEquals(714, $rates->bid_to_corps);
        $this->assertEquals(721, $rates->bid_to_imports);
    }

    /** @test */
    public function rate_action_can_call_exchangerate_rate()
    {
        Http::fake([
            'exchangerate-api.com/*' => Http::response([
                'result' => 'success',
                'time_last_update_unix' => 1,
                'time_next_update_unix' => 2,
                'base' => 'USD',
                'conversion_rates' => [
                    'USD' => 1,
                    'CLP' => 700,
                ]
            ], 200, ['Headers'])
        ]);

        $base = Currency::factory()->create(['name' => 'USD', 'symbol' => 'USD']);
        $quote = Currency::factory()->create(['name' => 'CLP', 'symbol' => 'CLP']);
        $pair = Pair::factory()->create([
            'base_id' => $base->id,
            'quote_id' => $quote->id,
            'offset' => 1,
            'offset_to_corps' => 2,
            'offset_to_imports' => 3,
            'api_class' => 'ExchangeRateApi'
        ]);

        $rates = RateActions::getExchangeRate($pair);

        $this->assertNotNull($rates);
        $this->assertNotNull($rates->api_rate);
        $this->assertNotNull($rates->bid);
        $this->assertNotNull($rates->bid_to_corps);
        $this->assertNotNull($rates->bid_to_imports);
        $this->assertEquals(700, $rates->api_rate);
        $this->assertEquals(707, $rates->bid);
        $this->assertEquals(714, $rates->bid_to_corps);
        $this->assertEquals(721, $rates->bid_to_imports);
    }

    /** @test */
    public function rate_action_can_call_yadio_api()
    {
        Http::fake([
            'yadio.io/exrates/*' => Http::response([
                'base' => 'USD',
                'timestamp' => 1,
                'USD' => [
                    'CLP' => 700,
                ]
            ], 200, ['Headers'])
        ]);

        $base = Currency::factory()->create(['name' => 'USD', 'symbol' => 'USD']);
        $quote = Currency::factory()->create(['name' => 'CLP', 'symbol' => 'CLP']);
        $pair = Pair::factory()->create([
            'base_id' => $base->id,
            'quote_id' => $quote->id,
            'offset' => 1,
            'offset_to_corps' => 2,
            'offset_to_imports' => 3,
            'api_class' => 'YadioApi'
        ]);

        $rates = RateActions::getExchangeRate($pair);

        $this->assertNotNull($rates);
        $this->assertNotNull($rates->api_rate);
        $this->assertNotNull($rates->bid);
        $this->assertNotNull($rates->bid_to_corps);
        $this->assertNotNull($rates->bid_to_imports);
        $this->assertEquals(700, $rates->api_rate);
        $this->assertEquals(707, $rates->bid);
        $this->assertEquals(714, $rates->bid_to_corps);
        $this->assertEquals(721, $rates->bid_to_imports);
    }

    /** @test */
    public function can_get_fixed_rate_when_call_get_exchange_rate()
    {
        $base = Currency::factory()->create(['name' => 'USD', 'symbol' => 'USD']);
        $quote = Currency::factory()->create(['name' => 'CLP', 'symbol' => 'CLP']);
        $pair = Pair::factory()->create([
            'base_id' => $base->id,
            'quote_id' => $quote->id,
            'offset' => 1,
            'offset_to_corps' => 2,
            'offset_to_imports' => 3,
            'has_fixed_rate' => true,
            'personal_fixed_rate' => 707,
            'corps_fixed_rate' => 714,
            'imports_fixed_rate' => 721
        ]);

        $rates = RateActions::getExchangeRate($pair);

        $this->assertNotNull($rates);
        $this->assertNull($rates->api_rate);
        $this->assertNotNull($rates->bid);
        $this->assertNotNull($rates->bid_to_corps);
        $this->assertNotNull($rates->bid_to_imports);
        $this->assertEquals(707, $rates->bid);
        $this->assertEquals(714, $rates->bid_to_corps);
        $this->assertEquals(721, $rates->bid_to_imports);
    }

    /** @test */
    public function can_fetch_a_exchange_rate_without_model_and_with_currency_layer_as_default_param()
    {
        Http::fake([
            'apilayer.net/*' => Http::response([
                'success' => true,
                'timestamp' => 1,
                'source' => 'USD',
                'quotes' => [
                    'USDCLP' => 700,
                ]
            ], 200, ['Headers'])
        ]);

        Param::factory()->create([
            'name' => 'defaultRateApi',
            'label' => 'Api para tasa de cambio por defecto',
            'value' => 'CurrencyLayerApi'
        ]);
        $rate = RateActions::getExchangeRate(null, 'USD', 'CLP');
        $this->assertNotNull($rate);
        $this->assertEquals(700, $rate);
    }

    /** @test */
    public function can_fetch_a_exchange_rate_without_model_and_with_exchange_rate_api_as_default_param()
    {
        Http::fake([
            'exchangerate-api.com/*' => Http::response([
                'result' => 'success',
                'time_last_update_unix' => 1,
                'time_next_update_unix' => 2,
                'base' => 'USD',
                'conversion_rates' => [
                    'USD' => 1,
                    'CLP' => 700,
                ]
            ], 200, ['Headers'])
        ]);

        Param::factory()->create([
            'name' => 'defaultRateApi',
            'label' => 'Api para tasa de cambio por defecto',
            'value' => 'ExchangeRateApi'
        ]);

        $rate = RateActions::getExchangeRate(null, 'USD', 'CLP');
        $this->assertNotNull($rate);
        $this->assertEquals(700, $rate);
    }

    /** @test */
    public function can_fetch_a_exchange_rate_without_model_and_with_yadio_api_as_default_param()
    {
        Http::fake([
            'yadio.io/exrates/*' => Http::response([
                'base' => 'USD',
                'timestamp' => 1,
                'USD' => [
                    'CLP' => 700,
                ]
            ], 200, ['Headers'])
        ]);

        Param::factory()->create([
            'name' => 'defaultRateApi',
            'label' => 'Api para tasa de cambio por defecto',
            'value' => 'YadioApi'
        ]);

        $rate = RateActions::getExchangeRate(null, 'USD', 'CLP');
        $this->assertNotNull($rate);
        $this->assertEquals(700, $rate);
    }
}
