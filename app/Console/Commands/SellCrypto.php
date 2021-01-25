<?php

namespace App\Console\Commands;

use App\Libraries\KrakenAPI;

use App\Models\Order;
use App\Notifications\CryptoInfoNotification;
use Illuminate\Console\Command;

class SellCrypto extends Command
{
    protected $orderModel;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crypto:sell';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check every minute for selling ETH';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->orderModel = Order::first();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $kraken = new KrakenAPI(env('KRAKEN_API'), env('KRAKEN_SECRET'));

        $sell = false;
        $msg = "";
        // Check balance
        $balance = $kraken->QueryPrivate('Balance');
        $balance = json_encode($balance['result']['XETH']);
        $balance = (double) $balance;
        if ($balance > 1) {
            $sell = true;
        }

        if ($sell) {
            $eth = (new CryptoPriceInfo)->getPrice('ETHEUR');
            $sendNotification = true;

            $volume = $this->orderModel->volume; // / 2;
            // Check if price matched
            if ((int)$eth >= $this->orderModel->sell_value) {
                // Sell Ether
                $res = $kraken->QueryPrivate('AddOrder', array(
                    'pair' => 'ETHEUR',
                    'type' => 'sell',
                    'ordertype' => 'limit,',
                    'volume' => $volume,
                ));
                $result = json_encode($res);

                // Delete order from database;
                $this->orderModel->delete();

                $sendNotification = true;
                $msg .= "Congratulations. I have sold your Ethereum, because the sell price matched: {$this->orderModel->sell_value}.";
                $msg .= "Ethereum is worth {$eth}";
                $msg .= "Volume sold: {$volume}";
            } else if ((int)$eth <= $this->orderModel->automated_sell_value) {
                // Sell Ether
                $res = $kraken->QueryPrivate('AddOrder', array(
                    'pair' => 'ETHEUR',
                    'type' => 'sell',
                    'ordertype' => 'limit,',
                    'volume' => $volume,
                ));
                $result = json_encode($res);

                // Delete order from database;
                $this->orderModel->delete();

                $sendNotification = true;
                $msg .= "I prevented more loss: {$this->orderModel->automated_sell_value}";
                $msg .= "Ethereum is worth {$eth}";
                $msg .= "Volume sold: {$volume}";
                $msg .= $result;
            } else {
                $sendNotification = true;
                $msg .= "Nothing to do.. ETH value: {$eth}";
                $msg .= "Sell Value: {$this->orderModel->sell_value}";
                $msg .= "Automated sell Value: {$this->orderModel->automated_sell_value}";
                $msg .= "Volume that wil be sold: {$volume}";
            }
        }

        if ($sendNotification) {
            // Notify User
            \Notification::send('update', new CryptoInfoNotification([
                'text' => $msg
            ]));
        }
    }
}
