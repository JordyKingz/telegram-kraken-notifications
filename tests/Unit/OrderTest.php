<?php

namespace Tests\Unit;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    /** @test  */
    public function create_order()
    {
        try {
            Order::factory()->make();
            $this->assertTrue(true);
        } catch (exception $e) {
            $this->assertFalse(true);
        }
    }
}
