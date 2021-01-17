<?php

namespace App\Console\Commands;

use App\Notifications\CryptoInfoNotification;
use Illuminate\Console\Command;
use App\Models\Cryptocurrency;


class BuyCrypto extends Command
{
    protected $btcModel;
    protected $ethModel;
    protected $dotModel;

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
    protected $description = 'Check every minute of specific coin match the buy value in database';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->btcModel = Cryptocurrency::where('currency', 'btc')->first();
        $this->ethModel = Cryptocurrency::where('currency', 'eth')->first();
        $this->dotModel = Cryptocurrency::where('currency', 'dot')->first();
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
        $btc = (new CryptoPriceInfo)->getPrice('BTCEUR');
        $eth = (new CryptoPriceInfo)->getPrice('ETHEUR');
        $dot = (new CryptoPriceInfo)->getPrice('DOTEUR');

        $msg = "";
        // Check if price matched
        if ((int)$btc <= $this->btcModel->buy_value && $this->btcModel->notify_buy) {
            $sendNotification = true;
            $msg .= "Bitcoin has reached the value of {$btc}. Your buy value is {$this->btcModel->buy_value} Buy now!";
        }

        // Check if price matched
        if ((int)$eth <= $this->ethModel->buy_value && $this->ethModel->notify_buy) {
            $sendNotification = true;
            $msg .= "Ethereum has reached the value of {$eth}. Your buy value is {$this->ethModel->buy_value} Buy now!";
        }

        // Check if price matched
        if ((int)$dot <= $this->dotModel->buy_value && $this->dotModel->notify_buy) {
            $sendNotification = true;
            $msg .= "Pokadot has reached the value of {$dot}. Your buy value is {$this->dotModel->buy_value} Buy now!";
        }

        // Only send message when the price matched buy value
        if ($sendNotification) {
            // Notify User
            \Notification::send('update', new CryptoInfoNotification([
                'text' => $msg
            ]));
        }
    }
}
