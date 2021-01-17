<?php

namespace App\Console\Commands;

use App\Libraries\KrakenAPI;
use Illuminate\Console\Command;
use App\Notifications\CryptoInfoNotification;

class CryptoPriceInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crypto:info';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get price information of cryptocurrencies';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        // Receive price
        $btc = $this->getPrice('BTCEUR');
        $eth = $this->getPrice('ETHEUR');
        $dot = $this->getPrice('DOTEUR');

        // Message send through Telegram
        $msg = "Here is an overview of the current coin values:
        BTC: {$btc}
        ETH: {$eth}
        DOT: {$dot}";

        // Notify User
        \Notification::send('update', new CryptoInfoNotification([
            'text' => $msg
        ]));
    }

    /**
     * Get price of $coin in euro
     *
     * @return string
     */
    public function getPrice($coin)
    {
        $kraken = new KrakenAPI(env('KRAKEN_API'), env('KRAKEN_SECRET'));

        $res = $kraken->QueryPublic('Ticker', array('pair' => $coin));
        $result = $res['result'];

        $price = "0";
        foreach($result as $value)
        {
            $price = $value['c'][0];
        }

        return $price;
    }
}
