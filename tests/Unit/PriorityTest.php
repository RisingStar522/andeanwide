<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Priority;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class PriorityTest extends TestCase
{
    use DatabaseMigrations, RefreshDatabase;

    /** @test */
    public function can_create_a_priority()
    {
        $priority = Priority::create([
            'name'          => 'Priority Test',
            'label'         => 'priorty label',
            'sublabel'      => 'priority sub label',
            'description'   => 'description',
            'cost_pct'      => '10',
            'is_active'     => true
        ]);

        $this->assertInstanceOf(Priority::class, $priority);
        $this->assertDatabaseHas('priorities', [
            'name'          => 'Priority Test',
            'label'         => 'priorty label',
            'sublabel'      => 'priority sub label',
            'description'   => 'description',
            'cost_pct'      => '10',
            'is_active'     => true
        ]);
    }
}
