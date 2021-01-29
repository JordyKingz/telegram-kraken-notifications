<?php

namespace App\Http\Controllers;

use App\Libraries\KrakenAPI;

use App\Models\HighValue;
use App\Models\Order;
use App\Models\Trade;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Telegram\TelegramDriver;
use PHPUnit\Util\Exception;

class BotController extends Controller
{
    protected $eth = "eth";

    /**
     * Handle user conversation.
     *
     * @return \Illuminate\Http\Response
     */
    public function handle()
    {
        $config = [
            "telegram" => [
                "token" => env('TELEGRAM_BOT_TOKEN')
            ]
        ];
        DriverManager::loadDriver(TelegramDriver::class);
        $botman = BotManFactory::create($config);

        // Set buy order
        $botman->hears('buy_order {coin} {value} {set_sell_high}', function (Botman $bot, $coin, $value, $set_sell_high) {
            try {
                // kraken
                $kraken = new KrakenAPI(env('KRAKEN_API'), env('KRAKEN_SECRET'));

                // get ETHEUR PRICE
                $result = $kraken->QueryPublic('Ticker', array('pair' => 'ETHEUR'));
                $result = $result['result'];

                $ethPrice = "0";
                foreach ($result as $res) {
                    $ethPrice = $res['c'][0];
                }
                // set sell value
                $sell_high = $ethPrice + (int)$set_sell_high;
                // set sell low 3.5%
                $sell_low = round($ethPrice / 1.035, 2, PHP_ROUND_HALF_ODD);
                // calculate volume
                $volume = $value / $ethPrice;

                $date = date_create();
                $res = $kraken->QueryPrivate('AddOrder', array(
                    'pair' => 'ETHEUR',
                    'type' => 'buy',
                    'ordertype' => 'market',
                    'oflags' => 'fciq',
                    'volume' => $volume,
                    'starttm' => date_timestamp_get($date)
                ));
                $res = json_encode($res);

                $bot->reply("Order at Kraken: {$res}");

                $order = Order::create([
                    'currency' => $coin,
                    'amount' => $value,
                    'volume' => $volume,
                    'sell_value_high' => round($sell_high, 4, PHP_ROUND_HALF_ODD),
                    'sell_value_low' => round($sell_low, 4, PHP_ROUND_HALF_ODD)
                ]);

                if ($order === null) {
                    $bot->reply("Failed! Something went wrong creating the Order instance:");
                }

                $highValue = HighValue::create([
                    'high_value' => $set_sell_high
                ]);

                if ($highValue === null) {
                    $bot->reply("Failed! Something went wrong creating the HighValue instance:");
                }
            } catch (exception $e) {
                $bot->reply("BIG ERROR:
                {$e->getMessage()}.");
            }

            $bot->reply("
                Order information:
                Amount: {$value}
                Volume: {$volume}
                Sell high: {$sell_high}
                Sell low: {$sell_low}
            ");
        });

        $botman->hears('cancel_all_orders', function (Botman $bot) {
            Order::truncate();

            $bot->reply("I have deleted all orders");
        });

        $botman->hears('show_all_orders', function (Botman $bot) {
            $orders = Order::all();

            if ($orders->count() > 0) {
                $msg = "";
                foreach($orders as $order) {
                    $msg .= "
                    Volume: {$order->volume}
                    Sell high: {$order->sell_value_high}
                    Sell low: {$order->sell_value_low}";
                }

                $bot->reply($msg);
            } else {
                $bot->reply("No orders in database");
            }
        });

        $botman->listen();
    }
}
