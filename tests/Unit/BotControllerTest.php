<?php

namespace Tests\Unit;

use App\Libraries\KrakenAPI;
use App\Models\HighValue;
use App\Models\Order;
use BotMan\BotMan\BotMan;
use PHPUnit\Framework\TestCase;
use PHPUnit\Util\Exception;

class BotControllerTest extends TestCase
{
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function test_bot_controller()
    {
        $coin = "eth";
        $value = 100;
        $set_sell_high = 50;
        $etherPrice = 1000;
        try {
            // set sell value
            $sell_high = $etherPrice + (int)$set_sell_high;
            // set sell low 3.5%
            $sell_low = round($etherPrice / 1.035, 2, PHP_ROUND_HALF_ODD);
            // calculate volume
            $volume = $value / $etherPrice;

            Order::factory()->makeOne([
                'currency' => $coin,
                'amount' => $value,
                'volume' => $volume,
                'sell_high' => round($sell_high, 4, PHP_ROUND_HALF_ODD),
                'sell_low' => round($sell_low, 4, PHP_ROUND_HALF_ODD)
            ]);

            HighValue::factory()->makeOne([
                'high_value' => (int)$set_sell_high
            ]);
        } catch (exception $e) {
            $this->assertFalse(true);
        }

        $this->assertTrue(true);
    }
}
