<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Param;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ParamTest extends TestCase
{
    use DatabaseMigrations, RefreshDatabase;

    /** @test */
    public function can_create_params()
    {
        $param = Param::create([
            'name'          => 'name',
            'label'         => 'label',
            'description'   => 'description',
            'value'         => 'value',
            'value_type'    => 'string',
            'default_value' => null
        ]);

        $this->assertInstanceOf(Param::class, $param);
        $this->assertDatabaseHas('params', [
            'name'          => 'name',
            'label'         => 'label',
            'description'   => 'description',
            'value'         => 'value',
            'value_type'    => 'string',
            'default_value' => null
        ]);
    }
}
