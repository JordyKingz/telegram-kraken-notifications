<?php

namespace Tests\Unit;

use App\Libraries\KrakenAPI;
use App\Models\Order;
use PHPUnit\Framework\TestCase;

class SellOrderLowTest extends TestCase
{
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function test_example()
    {
        try {
            // kraken
            $kraken = new KrakenAPI(env('KRAKEN_API'), env('KRAKEN_SECRET'));
            // get ETHEUR PRICE
            $result = $kraken->QueryPublic('Ticker', array('pair' => 'ETHEUR'));
            $result = $result['result'];

            $ethPrice = 0;
            foreach ($result as $res) {
                $ethPrice = $res['c'][0];
            }

            // set sell value
            $sell_high = $ethPrice + 35;
            // set sell low 3.5%
            $sell_low = round($ethPrice / 1.035, 2, PHP_ROUND_HALF_ODD);
            // calculate volume
            $amountSpend = 100;
            $volume = $amountSpend / $ethPrice;

            try {
                Order::factory()->makeOne([
                    'currency' => 'eth',
                    'amount' => $amountSpend,
                    'volume' => $volume,
                    'sell_value_high' => round($sell_high, 4, PHP_ROUND_HALF_ODD),
                    'sell_value_low' => round($sell_low, 4, PHP_ROUND_HALF_ODD)
                ]);

                $this->assertTrue(true);
            } catch (exception $e) {
                $this->assertFalse(true, $e->getMessage());
            }
        } catch (exception $e) {
            $this->assertFalse(true, $e->getMessage());
        }
    }
}
