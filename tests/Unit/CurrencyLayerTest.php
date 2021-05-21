<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Pair;
use App\Models\Rate;
use App\Models\Currency;
use Illuminate\Support\Facades\Http;
use App\Actions\Helpers\RateApis\CurrencyLayerApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class CurrencyLayerTest extends TestCase
{
    use DatabaseMigrations, RefreshDatabase;

    protected $url = 'apilayer.net/*';

    /** @test */
    public function can_call_currencylayer_and_get_the_rate_from_database()
    {
        $base = Currency::factory()->create(['name' => 'USD', 'symbol' => 'USD']);
        $quote = Currency::factory()->create(['name' => 'CLP', 'symbol' => 'CLP']);
        $pair = Pair::factory()->create([
            'base_id' => $base->id,
            'quote_id' => $quote->id,
            'offset' => 1,
            'offset_to_corps' => 2,
            'offset_to_imports' => 3
        ]);
        Rate::factory()->create([
            'base_currency_id' => $base->id,
            'quote_currency_id' => $quote->id,
            'pair_id' => $pair->id,
            'quote' => 700
        ]);

        $currencyLayer = new CurrencyLayerApi($pair);
        $rates = $currencyLayer->getExchangeRate();

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
    public function can_call_currency_layer_if_not_exists_rate_call_the_api()
    {
        Http::fake([
            $this->url => Http::response([
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
            'offset_to_imports' => 3
        ]);

        $currencyLayer = new CurrencyLayerApi($pair);
        $rates = $currencyLayer->getExchangeRate();

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
    public function if_there_is_no_response()
    {
        Http::fake();

        $base = Currency::factory()->create(['name' => 'USD', 'symbol' => 'USD']);
        $quote = Currency::factory()->create(['name' => 'CLP', 'symbol' => 'CLP']);
        $pair = Pair::factory()->create([
            'base_id' => $base->id,
            'quote_id' => $quote->id,
            'offset' => 1,
            'offset_to_corps' => 2,
            'offset_to_imports' => 3
        ]);

        $currencyLayer = new CurrencyLayerApi($pair);
        $rates = $currencyLayer->getExchangeRate();

        $this->assertNotNull($rates);
        $this->assertNotNull($rates['error']);
        $this->assertEquals('No results', $rates['error']);
    }

    /** @test */
    public function can_fetch_exchange_rate_from_api_without_pair_model()
    {
        Http::fake([
            $this->url => Http::response([
                'success' => true,
                'timestamp' => 1,
                'source' => 'USD',
                'quotes' => [
                    'USDCLP' => 700,
                ]
            ], 200, ['Headers'])
        ]);

        $currencyLayer = new CurrencyLayerApi();
        $rate = $currencyLayer->fetchExchangeRate('USD', 'CLP');

        $this->assertNotNull($rate);
        $this->assertEquals(700, $rate);
    }
}
