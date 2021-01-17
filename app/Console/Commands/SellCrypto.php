<?php

namespace App\Console\Commands;

use App\Models\Cryptocurrency;
use App\Notifications\CryptoInfoNotification;
use Illuminate\Console\Command;

class SellCrypto extends Command
{
    protected $btcModel;
    protected $ethModel;
    protected $dotModel;

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
    protected $description = 'Check every minute of specific coin match the sell value in database';

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
     * @return int
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
        if ((int)$btc >= $this->btcModel->sell_value) {
            $sendNotification = true;
            $msg .= "Bitcoin has reached the value of {$btc}. Your sell value is {$this->btcModel->sell_value} Sell now!";
        }

        // Check if price matched
        if ((int)$eth >= $this->ethModel->sell_value) {
            $sendNotification = true;
            $msg .= "Ethereum has reached the value of {$eth}. Your sell value is {$this->ethModel->sell_value} Sell now!";
        }

        // Check if price matched
        if ((int)$dot >= $this->dotModel->sell_value) {
            $sendNotification = true;
            $msg .= "Pokadot has reached the value of {$dot}. Your sell value is {$this->dotModel->sell_value} Sell now!";
        }

        // Only send message when the price matched sell value
        if ($sendNotification) {
            // Notify User
            \Notification::send('update', new CryptoInfoNotification([
                'text' => $msg
            ]));
        }
    }
}
