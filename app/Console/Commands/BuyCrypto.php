<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Notifications\CryptoInfoNotification;
use Illuminate\Console\Command;
use App\Models\Cryptocurrency;


class BuyCrypto extends Command
{
    protected $orderModel;
    protected $cryptoOrder;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crypto:buy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check every hour to buy ETH';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->orderModel = Order::first();
        $this->cryptoOrder = Trade::first();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $sendNotification = false;
        // Receive price
        $eth = (new CryptoPriceInfo)->getPrice('ETHEUR');
        $msg = "";

        // For now: only place order when trade is placed in database
        if ($this->cryptoOrder != null || $this->orderModel === null) {
            if ($sendNotification) {
                // Notify User
                \Notification::send('update', new CryptoInfoNotification([
                    'text' => $msg
                ]));
            }
        } else {
            // Notify User
            \Notification::send('update', new CryptoInfoNotification([
                'text' => 'No buy order in database.'
            ]));
        }
    }
}
