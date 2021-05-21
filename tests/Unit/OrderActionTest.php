<?php

namespace Tests\Unit;

use stdClass;
use Tests\TestCase;
use App\Models\Pair;
use App\Models\User;
use App\Models\Currency;
use App\Actions\Helpers\OrderAction;
use App\Models\Param;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class OrderActionTest extends TestCase
{
    use DatabaseMigrations, RefreshDatabase;

    /** @test */
    public function can_calculate_transaction_cost()
    {
        $payment_amount = 1000;
        $input = $this->generateInput();
        $result = OrderAction::calculateCosts($payment_amount, $input->transaction_pct, $input->tax, $input->priority_pct);
        $this->assertNotNull($result->transaction_cost);
        $this->assertEquals($result->transaction_cost, 50);
    }

    /** @test */
    public function can_calculate_priority_cost()
    {
        $payment_amount = 1000;
        $input = $this->generateInput();
        $result = OrderAction::calculateCosts($payment_amount, $input->transaction_pct, $input->tax, $input->priority_pct);
        $this->assertNotNull($result->priority_cost);
        $this->assertEquals($result->priority_cost, 100);
    }

    /** @test */
    public function can_calculate_total_cost()
    {
        $payment_amount = 1000;
        $input = $this->generateInput();
        $result = OrderAction::calculateCosts($payment_amount, $input->transaction_pct, $input->tax, $input->priority_pct);
        $this->assertNotNull($result->total_cost);
        $this->assertEquals($result->total_cost, 150);
    }

    /** @test */
    public function can_calculate_tax_cost()
    {
        $payment_amount = 1000;
        $input = $this->generateInput();
        $result = OrderAction::calculateCosts($payment_amount, $input->transaction_pct, $input->tax, $input->priority_pct);
        $this->assertNotNull($result->tax_cost);
        $this->assertEquals($result->tax_cost, 30);
    }

    /** @test */
    public function can_calculate_amount_to_send()
    {
        $payment_amount = 1000;
        $input = $this->generateInput();
        $result = OrderAction::calculateCosts($payment_amount, $input->transaction_pct, $input->tax, $input->priority_pct);
        $this->assertNotNull($result->amount_to_send);
        $this->assertEquals($result->amount_to_send, 820);
    }

    /** @test */
    public function calculate_amount_to_receive()
    {
        $payment_amount = 1000;
        $rate = 2;
        $input = $this->generateInput();
        $result = OrderAction::calculateCosts($payment_amount, $input->transaction_pct, $input->tax, $input->priority_pct);
        $amount_to_receive = OrderAction::calculateAmountToReceive($result->amount_to_send, $rate);
        $this->assertNotNull($amount_to_receive);
        $this->assertEquals($amount_to_receive, 1640);
    }

    /** @test */
    public function can_validate_exchange_rate_and_return_true_if_the_given_rate_has_not_changed_to_personal_acount()
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
        $rate=707;
        $validated = OrderAction::validateRate($pair, $rate, 'personal');
        $this->assertNotNull($validated);
        $this->assertTrue($validated);
    }

    /** @test */
    public function can_validate_exchange_rate_and_return_false_if_the_rate_has_changed_to_personal_acount()
    {
        Http::fake([
            'exchangerate-api.com/*' => Http::response([
                'result' => 'success',
                'time_last_update_unix' => 1,
                'time_next_update_unix' => 2,
                'base' => 'USD',
                'conversion_rates' => [
                    'USD' => 1,
                    'CLP' => 701,
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
        $rate=707;
        $validated = OrderAction::validateRate($pair, $rate, 'personal');
        $this->assertNotNull($validated);
        $this->assertFalse($validated);
    }

    /** @test */
    public function can_validate_exchange_rate_and_return_true_if_the_given_rate_has_not_changed_to_corporative_acount()
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
        $rate=714;
        $validated = OrderAction::validateRate($pair, $rate, 'corporative');
        $this->assertNotNull($validated);
        $this->assertTrue($validated);
    }

    /** @test */
    public function can_validate_exchange_rate_and_return_false_if_the_rate_has_changed_to_corporative_acount()
    {
        Http::fake([
            'exchangerate-api.com/*' => Http::response([
                'result' => 'success',
                'time_last_update_unix' => 1,
                'time_next_update_unix' => 2,
                'base' => 'USD',
                'conversion_rates' => [
                    'USD' => 1,
                    'CLP' => 701,
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
        $rate=714;
        $validated = OrderAction::validateRate($pair, $rate, 'corporative');
        $this->assertNotNull($validated);
        $this->assertFalse($validated);
    }

    /** @test */
    public function can_validate_exchange_rate_and_return_true_if_the_given_rate_has_not_changed_to_imports_acount()
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
        $rate=721;
        $validated = OrderAction::validateRate($pair, $rate, 'imports');
        $this->assertNotNull($validated);
        $this->assertTrue($validated);
    }

    /** @test */
    public function can_validate_exchange_rate_and_return_false_if_the_rate_has_changed_to_imports_acount()
    {
        Http::fake([
            'exchangerate-api.com/*' => Http::response([
                'result' => 'success',
                'time_last_update_unix' => 1,
                'time_next_update_unix' => 2,
                'base' => 'USD',
                'conversion_rates' => [
                    'USD' => 1,
                    'CLP' => 701,
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
        $rate=721;
        $validated = OrderAction::validateRate($pair, $rate, 'imports');
        $this->assertNotNull($validated);
        $this->assertFalse($validated);
    }

    /** @test */
    public function validate_rate_if_the_pair_is_inverse_for_personal()
    {
        Http::fake([
            'exchangerate-api.com/*' => Http::response([
                'result' => 'success',
                'time_last_update_unix' => 1,
                'time_next_update_unix' => 2,
                'base' => 'CLP',
                'conversion_rates' => [
                    'USD' => 0.001429,
                    'CLP' => 1,
                ]
            ], 200, ['Headers'])
        ]);
        $base = Currency::factory()->create(['name' => 'CLP', 'symbol' => 'CLP']);
        $quote = Currency::factory()->create(['name' => 'USD', 'symbol' => 'USD']);
        $pair = Pair::factory()->create([
            'base_id' => $base->id,
            'quote_id' => $quote->id,
            'offset' => -1,
            'offset_to_corps' => -2,
            'offset_to_imports' => -3,
            'api_class' => 'ExchangeRateApi',
            'show_inverse' => true
        ]);
        $rate=707;
        $validated = OrderAction::validateRate($pair, $rate, 'personal');
        $this->assertNotNull($validated);
        $this->assertTrue($validated);
    }

    /** @test */
    public function can_not_validate_rate_if_the_pair_is_inverse_for_personal_and_the_rate_is_changed()
    {
        Http::fake([
            'exchangerate-api.com/*' => Http::response([
                'result' => 'success',
                'time_last_update_unix' => 1,
                'time_next_update_unix' => 2,
                'base' => 'CLP',
                'conversion_rates' => [
                    'USD' => 0.001429,
                    'CLP' => 1,
                ]
            ], 200, ['Headers'])
        ]);
        $base = Currency::factory()->create(['name' => 'CLP', 'symbol' => 'CLP']);
        $quote = Currency::factory()->create(['name' => 'USD', 'symbol' => 'USD']);
        $pair = Pair::factory()->create([
            'base_id' => $base->id,
            'quote_id' => $quote->id,
            'offset' => -1,
            'offset_to_corps' => -2,
            'offset_to_imports' => -3,
            'api_class' => 'ExchangeRateApi',
            'show_inverse' => true
        ]);
        $rate=708;
        $validated = OrderAction::validateRate($pair, $rate, 'personal');
        $this->assertNotNull($validated);
        $this->assertFalse($validated);
    }

    /** @test */
    public function validate_rate_if_the_pair_is_inverse_for_corporative()
    {
        Http::fake([
            'exchangerate-api.com/*' => Http::response([
                'result' => 'success',
                'time_last_update_unix' => 1,
                'time_next_update_unix' => 2,
                'base' => 'CLP',
                'conversion_rates' => [
                    'USD' => 0.001429,
                    'CLP' => 1,
                ]
            ], 200, ['Headers'])
        ]);
        $base = Currency::factory()->create(['name' => 'CLP', 'symbol' => 'CLP']);
        $quote = Currency::factory()->create(['name' => 'USD', 'symbol' => 'USD']);
        $pair = Pair::factory()->create([
            'base_id' => $base->id,
            'quote_id' => $quote->id,
            'offset' => -1,
            'offset_to_corps' => -2,
            'offset_to_imports' => -3,
            'api_class' => 'ExchangeRateApi',
            'show_inverse' => true
        ]);
        $rate=714;
        $validated = OrderAction::validateRate($pair, $rate, 'corporative');
        $this->assertNotNull($validated);
        $this->assertTrue($validated);
    }

    /** @test */
    public function can_not_validate_rate_if_the_pair_is_inverse_for_corporative_if_the_rate_has_changed()
    {
        Http::fake([
            'exchangerate-api.com/*' => Http::response([
                'result' => 'success',
                'time_last_update_unix' => 1,
                'time_next_update_unix' => 2,
                'base' => 'CLP',
                'conversion_rates' => [
                    'USD' => 0.001429,
                    'CLP' => 1,
                ]
            ], 200, ['Headers'])
        ]);
        $base = Currency::factory()->create(['name' => 'CLP', 'symbol' => 'CLP']);
        $quote = Currency::factory()->create(['name' => 'USD', 'symbol' => 'USD']);
        $pair = Pair::factory()->create([
            'base_id' => $base->id,
            'quote_id' => $quote->id,
            'offset' => -1,
            'offset_to_corps' => -2,
            'offset_to_imports' => -3,
            'api_class' => 'ExchangeRateApi',
            'show_inverse' => true
        ]);
        $rate=713;
        $validated = OrderAction::validateRate($pair, $rate, 'corporative');
        $this->assertNotNull($validated);
        $this->assertFalse($validated);
    }

    /** @test */
    public function validate_rate_if_the_pair_is_inverse_for_imports()
    {
        Http::fake([
            'exchangerate-api.com/*' => Http::response([
                'result' => 'success',
                'time_last_update_unix' => 1,
                'time_next_update_unix' => 2,
                'base' => 'CLP',
                'conversion_rates' => [
                    'USD' => 0.001429,
                    'CLP' => 1,
                ]
            ], 200, ['Headers'])
        ]);
        $base = Currency::factory()->create(['name' => 'CLP', 'symbol' => 'CLP']);
        $quote = Currency::factory()->create(['name' => 'USD', 'symbol' => 'USD']);
        $pair = Pair::factory()->create([
            'base_id' => $base->id,
            'quote_id' => $quote->id,
            'offset' => -1,
            'offset_to_corps' => -2,
            'offset_to_imports' => -3,
            'api_class' => 'ExchangeRateApi',
            'show_inverse' => true
        ]);
        $rate=721;
        $validated = OrderAction::validateRate($pair, $rate, 'imports');
        $this->assertNotNull($validated);
        $this->assertTrue($validated);
    }

    /** @test */
    public function cannot_validate_rate_if_the_pair_is_inverse_for_imports_if_the_rate_has_changed()
    {
        Http::fake([
            'exchangerate-api.com/*' => Http::response([
                'result' => 'success',
                'time_last_update_unix' => 1,
                'time_next_update_unix' => 2,
                'base' => 'CLP',
                'conversion_rates' => [
                    'USD' => 0.001429,
                    'CLP' => 1,
                ]
            ], 200, ['Headers'])
        ]);
        $base = Currency::factory()->create(['name' => 'CLP', 'symbol' => 'CLP']);
        $quote = Currency::factory()->create(['name' => 'USD', 'symbol' => 'USD']);
        $pair = Pair::factory()->create([
            'base_id' => $base->id,
            'quote_id' => $quote->id,
            'offset' => -1,
            'offset_to_corps' => -2,
            'offset_to_imports' => -3,
            'api_class' => 'ExchangeRateApi',
            'show_inverse' => true
        ]);
        $rate=720;
        $validated = OrderAction::validateRate($pair, $rate, 'imports');
        $this->assertNotNull($validated);
        $this->assertFalse($validated);
    }

    /** @test */
    public function can_create_usd_amount()
    {
        Http::fake([
            'apilayer.net/*' => Http::response([
                'success' => true,
                'timestamp' => 1,
                'source' => 'USD',
                'quotes' => [
                    'USDCLP' => 0.0016,
                ]
            ], 200, ['Headers'])
        ]);

        Param::factory()->create([
            'name' => 'defaultRateApi',
            'label' => 'Api para tasa de cambio por defecto',
            'value' => 'CurrencyLayerApi'
        ]);

        $usd_amount = OrderAction::convertAmountToUsd('CLP', 10000);
        $this->assertNotNull($usd_amount);
        $this->assertEquals(16, $usd_amount);
    }

    protected function generateInput(float $rate=2, float $transaction_pct=5, float $tax=20, float $priority_pct=10)
    {
        $input = new stdClass();
        $input->rate = $rate;
        $input->transaction_pct =$transaction_pct;
        $input->tax = $tax;
        $input->priority_pct = $priority_pct;
        return $input;
    }
}
