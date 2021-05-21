<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Pair;
use App\Models\Currency;
use App\Models\Priority;
use App\Models\Rate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class PairTest extends TestCase
{
    use DatabaseMigrations, RefreshDatabase;

    /** @test */
    public function can_create_a_pair()
    {
        $pair = Pair::create([
            'is_active'         => true,
            'is_default'        => true,
            'default_amount'    => 100000,
            'min_amount'        => 10000,
            'name'              => 'name',
            'api_class'         => 'api_class',
            'observation'       => 'lorem ipsum',
            'base_id'           => 1,
            'quote_id'          => 2,
            'offset_by'         => 'percentage',
            'offset'            => 5,
            'offset_to_corps'   => 2,
            'offset_to_imports' => 1,
            'min_pip_value'     => 1,
            'show_inverse'      => false,
            'max_tier_1'        => 1000000,
            'max_tier_2'        => 5000000,
            'more_rate'         => 2.5,
            'is_more_enabled'   => false,
            'decimals'          => 2
        ]);

        $this->assertInstanceOf(Pair::class, $pair);
        $this->assertDatabaseHas('pairs', [
            'is_active'         => 1,
            'is_default'        => 1,
            'default_amount'    => 100000,
            'min_amount'        => 10000,
            'name'              => 'name',
            'api_class'         => 'api_class',
            'observation'       => 'lorem ipsum',
            'base_id'           => 1,
            'quote_id'          => 2,
            'offset_by'         => 'percentage',
            'offset'            => 5,
            'offset_to_corps'   => 2,
            'offset_to_imports' => 1,
            'min_pip_value'     => 1,
            'show_inverse'      => false,
            'max_tier_1'        => 1000000,
            'max_tier_2'        => 5000000,
            'more_rate'         => 2.5,
            'is_more_enabled'   => false,
            'decimals'          => 2
        ]);
    }

    /** @test */
    public function a_base_belongs_to_currency()
    {
        $baseCurrency = Currency::factory()->create();
        $quoteCurrency = Currency::factory()->create();
        $pair=Pair::factory()->create([
            'base_id'   => $baseCurrency->id,
            'quote_id'  => $quoteCurrency->id
        ]);

        $this->assertInstanceOf(Currency::class, $pair->base);
        $this->assertInstanceOf(Currency::class, $pair->quote);
        $this->assertEquals($pair->base->id, $baseCurrency->id);
        $this->assertEquals($pair->quote->id, $quoteCurrency->id);
    }

    /** @test */
    public function a_pair_has_many_priorities()
    {
        $pair = Pair::factory()->create();
        $priorityOne = Priority::factory()->create();
        $priorityTwo = Priority::factory()->create();
        $pair->priorities()->attach($priorityOne);
        $pair->priorities()->attach($priorityTwo);

        $this->assertInstanceOf(Collection::class, $pair->priorities);
        $this->assertInstanceOf(Priority::class, $pair->priorities[0]);
        $this->assertCount(2, $pair->priorities);
    }

    /** @test */
    public function a_pair_can_update_his_parameter()
    {
        $pair = Pair::factory()->create();
        $priority = Priority::factory()->create([
            'cost_pct' => 5
        ]);
        $pair->priorities()->attach($priority);
        $pair->priorities[0]->pivot->pct = 1;
        $pair->priorities[0]->pivot->is_active = false;
        $pair->priorities[0]->pivot->save();

        $this->assertDatabaseHas('pair_priority' ,[
            'priority_id'   => $priority->id,
            'pair_id'       => $pair->id,
            'pct'          => 1,
            'is_active'     => 0,
        ]);
    }

    /** @test */
    public function a_pair_has_a_rate_attribute()
    {
        $pair = Pair::factory()->create();
        Rate::factory()->create([
            'base_currency_id' => $pair->base->id,
            'quote_currency_id' => $pair->quote->id,
            'pair_id' => $pair->id,
            'quote' => 10
        ]);
        $this->assertNotNull($pair->rate);
        $this->assertNotNull($pair->rate->quote);
        $this->assertEquals(10, $pair->rate->quote);
    }

    /** @test */
    public function rate_return_the_last_rate_created()
    {
        $pair = Pair::factory()->create();
        $rate = null;
        for ($i=0; $i < 10 ; $i++) {
            $rate = Rate::factory()->create([
                'base_currency_id' => $pair->base->id,
                'quote_currency_id' => $pair->quote->id,
                'pair_id' => $pair->id,
                'quote' => $i
            ]);
        }
        $this->assertEquals($rate->quote, $pair->rate->quote);
    }
}
