<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Pair;
use App\Models\Rate;
use App\Models\Currency;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ExchangeRateTest extends TestCase
{
    use DatabaseMigrations, RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /** @test */
    public function anyone_can_view_exchange_rate()
    {
        $base = Currency::factory()->create(['name' => 'USD', 'symbol' => 'USD']);
        $quote = Currency::factory()->create(['name' => 'CLP', 'symbol' => 'CLP']);
        Pair::factory()->create([
            'name' => $base->symbol . '/' . $quote->symbol,
            'base_id' => $base->id,
            'quote_id' => $quote->id,
            'has_fixed_rate' => true,
            'personal_fixed_rate' => 707.0,
            'corps_fixed_rate' => 714.0,
            'imports_fixed_rate' => 721.0
        ]);

        $this->json('get', 'api/exchange-rate/USD/CLP')
            ->assertOk()
            ->assertJsonFragment([
                'api_rate' => null,
                'bid' => 707.0,
                'bid_to_corps' => 714.0,
                'bid_to_imports' => 721.0
            ]);
    }

    /** @test */
    public function can_get_exchange_rate_from_currency_layer()
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
        Pair::factory()->create([
            'name' => $base->symbol . '/' . $quote->symbol,
            'base_id' => $base->id,
            'quote_id' => $quote->id,
            'offset' => 1,
            'offset_to_corps' => 2,
            'offset_to_imports' => 3,
            'api_class' => 'CurrencyLayerApi'
        ]);

        $this->json('get', 'api/exchange-rate/USD/CLP')
            ->assertOk()
            ->assertJsonFragment([
                'api_rate' => 700.0,
                'bid' => 707.0,
                'bid_to_corps' => 714.0,
                'bid_to_imports' => 721.0
            ]);
    }

    /** @test */
    public function can_get_rate_from_exchange_rate()
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
        Pair::factory()->create([
            'name' => $base->symbol . '/' . $quote->symbol,
            'base_id' => $base->id,
            'quote_id' => $quote->id,
            'offset' => 1,
            'offset_to_corps' => 2,
            'offset_to_imports' => 3,
            'api_class' => 'ExchangeRateApi'
        ]);

        $this->json('get', 'api/exchange-rate/USD/CLP')
            ->assertOk()
            ->assertJsonFragment([
                'api_rate' => 700.0,
                'bid' => 707.0,
                'bid_to_corps' => 714.0,
                'bid_to_imports' => 721.0
            ]);
    }

    /** @test */
    public function can_get_rate_from_yadio()
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
        Pair::factory()->create([
            'name' => $base->symbol . '/' . $quote->symbol,
            'base_id' => $base->id,
            'quote_id' => $quote->id,
            'offset' => 1,
            'offset_to_corps' => 2,
            'offset_to_imports' => 3,
            'api_class' => 'YadioApi'
        ]);

        $this->json('get', 'api/exchange-rate/USD/CLP')
            ->assertOk()
            ->assertJsonFragment([
                'api_rate' => 700.0,
                'bid' => 707.0,
                'bid_to_corps' => 714.0,
                'bid_to_imports' => 721.0
            ]);
    }

    /** @test */
    public function can_handle_request_to_pairs_that_does_not_exist()
    {
        $this->json('get', 'api/exchange-rate/ABC/XYZ')
            ->assertNotFound();
    }

    /** @test */
    public function can_handle_errors_from_api()
    {
        Http::fake();

        $base = Currency::factory()->create(['name' => 'USD', 'symbol' => 'USD']);
        $quote = Currency::factory()->create(['name' => 'CLP', 'symbol' => 'CLP']);
        Pair::factory()->create([
            'name' => $base->symbol . '/' . $quote->symbol,
            'base_id' => $base->id,
            'quote_id' => $quote->id,
            'api_class' => 'YadioApi'
        ]);

        $this->json('get', 'api/exchange-rate/USD/CLP')
            ->assertStatus(500);
    }
}
