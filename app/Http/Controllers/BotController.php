<?php

namespace App\Http\Controllers;

use App\Models\Cryptocurrency;
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

        // Set buy value
        $botman->hears('Buy {coin} {amount}', function (BotMan $bot, $coin, $amount) {
            if ($coin === $this->btc) {
                // Bitcoin
                $btc = Cryptocurrency::where('currency', $coin)->first();
                if ($btc === null) {
                    $return = $this->createCoin($coin, $amount, $bot);

                    if (!$return) return;
                } else {
                    $return = $this->updateCoin($btc, $coin, $amount, $bot);

                    if (!$return) return;
                }
            } else if ($coin === $this->eth) {
                // Ethereum
                $eth = Cryptocurrency::where('currency', $coin)->first();
                if ($eth === null) {
                    $return = $this->createCoin($coin, $amount, $bot);

                    if (!$return) return;
                } else {
                    $return = $this->updateCoin($eth, $coin, $amount, $bot);

                    if (!$return) return;
                }
            } else if ($coin ===  $this->dot) {
                // Polkadot
                $dot = Cryptocurrency::where('currency', $coin)->first();
                if ($dot === null) {
                    $return = $this->createCoin($coin, $amount, $bot);

                    if (!$return) return;
                } else {
                    $return = $this->updateCoin($dot, $coin, $amount, $bot);

                    if (!$return) return;
                }
            }

            $bot->reply("Success! I have set the buy value of {$coin} at {$amount}.");
        });

        // Set sell value
        $botman->hears('Sell {coin} {amount}', function (BotMan $bot, $coin, $amount) {
            if ($coin === $this->btc) {
                // Bitcoin
                $btc = Cryptocurrency::where('currency', $coin)->first();
                if ($btc === null) {
                    $return = $this->createCoin($coin, $amount, $bot, true);

                    if (!$return) return;
                } else {
                    $return = $this->updateCoin($btc, $coin, $amount, $bot, true);

                    if (!$return) return;
                }
            } else if ($coin === $this->eth) {
                // Ethereum
                $eth = Cryptocurrency::where('currency', $coin)->first();
                if ($eth === null) {
                    $return = $this->createCoin($coin, $amount, $bot, true);

                    if (!$return) return;
                } else {
                    $return = $this->updateCoin($eth, $coin, $amount, $bot, true);

                    if (!$return) return;
                }
            } else if ($coin ===  $this->dot) {
                // Polkadot
                $dot = Cryptocurrency::where('currency', $coin)->first();
                if ($dot === null) {
                    $return = $this->createCoin($coin, $amount, $bot, true);

                    if (!$return) return;
                } else {
                    $return = $this->updateCoin($dot, $coin, $amount, $bot, true);

                    if (!$return) return;
                }
            }

            $bot->reply("Success! I have set the sell value of {$coin} at {$amount}.");
        });

        // Turn of notification coin
        $botman->hears('Off {status} {coin}', function (BotMan $bot, $status, $coin) {
            $updateSuccess = false;
            if ($coin === $this->btc) {
                // Bitcoin
                $btc = Cryptocurrency::where('currency', $coin)->first();

                if ($btc != null) {
                    if ($status === "sell") {
                        $btc->notify_sell = false;
                        $btc->save();
                        $updateSuccess = true;
                    } else if ($status === "buy") {
                        $btc->notify_buy = false;
                        $btc->save();
                        $updateSuccess = true;
                    }
                }
            } else if ($coin === $this->eth) {
                // Ethereum
                $eth = Cryptocurrency::where('currency', $coin)->first();
                if ($eth != null) {
                    if ($status === "sell") {
                        $eth->notify_sell = false;
                        $eth->save();
                        $updateSuccess = true;
                    } else if ($status === "buy") {
                        $eth->notify_buy = false;
                        $eth->save();
                        $updateSuccess = true;
                    }
                }
            } else if ($coin ===  $this->dot) {
                // Polkadot
                $dot = Cryptocurrency::where('currency', $coin)->first();
                if ($dot != null) {
                    if ($status === "sell") {
                        $dot->notify_sell = false;
                        $dot->save();
                        $updateSuccess = true;
                    } else if ($status === "buy") {
                        $dot->notify_buy = false;
                        $dot->save();
                        $updateSuccess = true;
                    }
                }
            }

            if ($updateSuccess) {
                if ($status === "sell") {
                    $bot->reply("Success! I have turned off selling notifications for {$coin}");
                } else if ($status === "buy") {
                    $bot->reply("Success! I have turned off buying notifications for {$coin}");
                }
            } else {
                $bot->reply("Failed! There are no {$coin} records in the db");
            }
        });

        $botman->listen();
    }

    public function createCoin(string $coin, int $amount, BotMan $bot, bool $sell = false): bool
    {
        try {
            if ($sell) {
                Cryptocurrency::create([
                    'currency' => $coin,
                    'sell_value' => $amount,
                    'notify' => true,
                    'notify_buy' => true,
                ]);
            } else {
                Cryptocurrency::create([
                    'currency' => $coin,
                    'buy_value' => $amount,
                    'notify_sell' => true,
                    'notify_buy' => true,
                ]);
            }
        }
        catch(exception $e) {
            $bot->reply("Failed! Something went wrong creating the Cryptocurrency instance:
                {$e->getMessage()}.
                Coin: {$coin}
                Amount: {$amount}"
            );
            return false;
        }

        return true;
    }

    public function updateCoin(Cryptocurrency $model, string $coin, int $amount, BotMan $bot, bool $sell = false): bool
    {
        try {
            $sell ? $model->sell_value = $amount : $model->buy_value = $amount;
            
            $model->notify_sell = true;
            $model->notify_buy = true;
            $model->save();
        }
        catch(exception $e) {
            $bot->reply("Failed! Something went wrong updating the Cryptocurrency instance:
                {$e->getMessage()}.
                Coin: {$coin}
                Amount: {$amount}"
            );

            return false;
        }

        return true;
    }
}
