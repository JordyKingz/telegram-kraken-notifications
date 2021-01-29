<?php

namespace Tests\Unit;

use App\Libraries\KrakenAPI;
use App\Models\Order;
use PHPUnit\Framework\TestCase;

class SellOrderLowTest extends TestCase
{
    /**
     * sell_order_low
     *
     * @return void
     */
    public function test_sell_order_low()
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

            // TODO check price match order

        } catch (exception $e) {
            $this->assertFalse(true, $e->getMessage());
        }
        $this->assertTrue(true);
    }
}
