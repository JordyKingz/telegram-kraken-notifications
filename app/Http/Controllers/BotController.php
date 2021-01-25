<?php

namespace App\Http\Controllers;

use App\Libraries\KrakenAPI;

use App\Models\Balance;
use App\Models\Cryptocurrency;
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

        $botman->hears('start_balance {amount}', function (Botman $bot, $amount) {
            try {
                Balance::create([
                    'start' => $amount,
                    'available' => $amount,
                ]);
            } catch (exception $e) {
                $bot->reply("Failed! Something went wrong creating the balance instance:
                {$e->getMessage()}.");

                return false;
            }

            $bot->reply("Set balance: {$amount}");
        });

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
                $sell_value = $ethPrice * 1.1;
                // set buy value
                $automated_sell_value = round($ethPrice / 1.05, 2, PHP_ROUND_HALF_ODD);
                // calculate volume
                $volume = $amount / $ethPrice;

                try {
                    Order::create([
                        'currency' => $coin,
                        'amount' => $amount,
                        'volume' => $volume,
                        'sell_value' => round($sell_value, 4, PHP_ROUND_HALF_ODD),
                        'automated_sell_value' => round($automated_sell_value, 4, PHP_ROUND_HALF_ODD)
                    ]);
                } catch (exception $e) {
                    $bot->reply("Failed! Something went wrong creating the Order instance:
                    {$e->getMessage()}.");

                    return false;
                }

                $date = date_create();
                try {
                    $res = $kraken->QueryPrivate('AddOrder', array(
                        'pair' => 'ETHEUR',
                        'type' => 'buy',
                        'ordertype' => 'market',
                        'oflags' => 'fciq',
                        'volume' => $volume,
                        'starttm' => date_timestamp_get($date)
                    ));
                    $result = json_encode($res);

                    $bot->reply($result);
                } catch (exception $e) {
                    $bot->reply("Failed! Something went wrong placing the order at Kraken
                    {$e->getMessage()}.");

                    return false;
                }

                $bot->reply("Success! Buy order set:
                currency: {$coin}
                amount: {$amount}
                volume: {$volume}
                sell value: {$sell_value}
                automated sell value {$automated_sell_value}");
            } catch (exception $e) {
                $bot->reply("BIG ERROR:
                {$e->getMessage()}.");
            }
        });

        // // buy
        $botman->hears('auto_buy {coin}', function (BotMan $bot, $coin) {
            // Ethereum
            $eth = Trade::where('currency', $coin)->first();
            if ($eth === null) {
                $return = $this->createTrade($coin, $bot);

                if (!$return) return;
            }

            $bot->reply("Buy order set!");
        });

        // Set sell value
//        $botman->hears('Sell {coin} {amount}', function (BotMan $bot, $coin, $amount) {
//            if ($coin === $this->btc) {
//                // Bitcoin
//                $btc = Cryptocurrency::where('currency', $coin)->first();
//                if ($btc === null) {
//                    $return = $this->createCoin($coin, $amount, $bot, true);
//
//                    if (!$return) return;
//                } else {
//                    $return = $this->updateCoin($btc, $coin, $amount, $bot, true);
//
//                    if (!$return) return;
//                }
//            } else if ($coin === $this->eth) {
//                // Ethereum
//                $eth = Cryptocurrency::where('currency', $coin)->first();
//                if ($eth === null) {
//                    $return = $this->createCoin($coin, $amount, $bot, true);
//
//                    if (!$return) return;
//                } else {
//                    $return = $this->updateCoin($eth, $coin, $amount, $bot, true);
//
//                    if (!$return) return;
//                }
//            } else if ($coin ===  $this->dot) {
//                // Polkadot
//                $dot = Cryptocurrency::where('currency', $coin)->first();
//                if ($dot === null) {
//                    $return = $this->createCoin($coin, $amount, $bot, true);
//
//                    if (!$return) return;
//                } else {
//                    $return = $this->updateCoin($dot, $coin, $amount, $bot, true);
//
//                    if (!$return) return;
//                }
//            }
//
//            $bot->reply("Success! I have set the sell value of {$coin} at {$amount}.");
//        });

        // Turn of notification coin
//        $botman->hears('Off {status} {coin}', function (BotMan $bot, $status, $coin) {
//            $updateSuccess = false;
//            if ($coin === $this->btc) {
//                // Bitcoin
//                $btc = Cryptocurrency::where('currency', $coin)->first();
//
//                if ($btc != null) {
//                    if ($status === "sell") {
//                        $btc->notify_sell = false;
//                        $btc->save();
//                        $updateSuccess = true;
//                    } else if ($status === "buy") {
//                        $btc->notify_buy = false;
//                        $btc->save();
//                        $updateSuccess = true;
//                    }
//                }
//            } else if ($coin === $this->eth) {
//                // Ethereum
//                $eth = Cryptocurrency::where('currency', $coin)->first();
//                if ($eth != null) {
//                    if ($status === "sell") {
//                        $eth->notify_sell = false;
//                        $eth->save();
//                        $updateSuccess = true;
//                    } else if ($status === "buy") {
//                        $eth->notify_buy = false;
//                        $eth->save();
//                        $updateSuccess = true;
//                    }
//                }
//            } else if ($coin ===  $this->dot) {
//                // Polkadot
//                $dot = Cryptocurrency::where('currency', $coin)->first();
//                if ($dot != null) {
//                    if ($status === "sell") {
//                        $dot->notify_sell = false;
//                        $dot->save();
//                        $updateSuccess = true;
//                    } else if ($status === "buy") {
//                        $dot->notify_buy = false;
//                        $dot->save();
//                        $updateSuccess = true;
//                    }
//                }
//            }
//
//            if ($updateSuccess) {
//                if ($status === "sell") {
//                    $bot->reply("Success! I have turned off selling notifications for {$coin}");
//                } else if ($status === "buy") {
//                    $bot->reply("Success! I have turned off buying notifications for {$coin}");
//                }
//            } else {
//                $bot->reply("Failed! There are no {$coin} records in the db");
//            }
//        });

        $botman->listen();
    }

    public function createTrade(string $coin, BotMan $bot): bool
    {
        try {
            Trade::create([
                'currency' => $coin,
            ]);
        }
        catch(exception $e) {
            $bot->reply("Failed! Something went wrong creating the Trade instance:
                {$e->getMessage()}.");
            return false;
        }

        return true;
    }
}
