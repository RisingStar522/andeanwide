<?php

namespace Tests\Unit;

use App\Actions\Helpers\RateApis\YadioApi;
use Tests\TestCase;
use App\Models\Pair;
use App\Models\Currency;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class YadioApiTest extends TestCase
{
    use DatabaseMigrations, RefreshDatabase;

    protected $url = 'yadio.io/exrates/*';

    /** @test */
    public function can_call_exchangerate_api_and_get_the_rate()
    {
        Http::fake([
            $this->url => Http::response([
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
            'offset_to_imports' => 3
        ]);

        $yadioApi = new YadioApi($pair);
        $rates = $yadioApi->getExchangeRate();

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

        $yadioApi = new YadioApi($pair);
        $rates = $yadioApi->getExchangeRate();

        $this->assertNotNull($rates);
        $this->assertNotNull($rates['error']);
        $this->assertEquals('No results', $rates['error']);
    }

    /** @test */
    public function test_case()
    {
        Http::fake([
            $this->url => Http::response([
                'base' => 'USD',
                'timestamp' => 1,
                'USD' => [
                    'CLP' => 700,
                ]
            ], 200, ['Headers'])
        ]);

        $yadioApi = new YadioApi();
        $rate = $yadioApi->fetchExchangeRate('USD', 'CLP');

        $this->assertNotNull($rate);
        $this->assertEquals(700, $rate);
    }
}
