<?php

namespace App\Http\Controllers;

use App\Libraries\KrakenAPI;

use App\Models\Order;
use App\Models\Trade;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Telegram\TelegramDriver;
use PHPUnit\Util\Exception;

class BotController extends Controller
{
    protected $btc = "btc";
    protected $eth = "eth";
    protected $dot = "dot";

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
        $botman->hears('buy_order {coin} {amount}', function (Botman $bot, $coin, $amount) {
            try {
                // kraken
                $kraken = new KrakenAPI(env('KRAKEN_API'), env('KRAKEN_SECRET'));

                // get ETHEUR PRICE
                $result = $kraken->QueryPublic('Ticker', array('pair' => 'ETHEUR'));
                $result = $result['result'];

                $ethPrice = "0";
                foreach ($result as $value) {
                    $ethPrice = $value['c'][0];
                }
                // set sell value
                $sell_high = $ethPrice + 70; // make this $variable
                // set sell low 3.5%
                $sell_low = round($ethPrice / 1.035, 2, PHP_ROUND_HALF_ODD);
                // calculate volume
                $volume = $amount / $ethPrice;

                try {
                    Order::create([
                        'currency' => $coin,
                        'amount' => $amount,
                        'volume' => $volume,
                        'sell_value_high' => round($sell_high, 4, PHP_ROUND_HALF_ODD),
                        'sell_value_low' => round($sell_low, 4, PHP_ROUND_HALF_ODD)
                    ]);
                } catch (exception $e) {
                    $bot->reply("Failed! Something went wrong creating the Order instance:
                    {$e->getMessage()}.");

                    return false;
                }

                $date = date_create();
                try {
                    $kraken->QueryPrivate('AddOrder', array(
                        'pair' => 'ETHEUR',
                        'type' => 'buy',
                        'ordertype' => 'market',
                        'oflags' => 'fciq',
                        'volume' => $volume,
                        'starttm' => date_timestamp_get($date)
                    ));
                } catch (exception $e) {
                    $bot->reply("Failed! Something went wrong placing the order at Kraken");
                    $bot->reply("Failed! Something went wrong placing the order at Kraken
                    {$e->getMessage()}.");

                    return;
                }

                $bot->reply("
                Order information:
                Amount: {$amount}
                Volume: {$volume}
                Sell high: {$sell_high}
                Sell low: {$sell_low}
                ");
            } catch (exception $e) {
                $bot->reply("BIG ERROR:
                {$e->getMessage()}.");
            }
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
