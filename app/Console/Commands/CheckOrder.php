<?php

namespace App\Console\Commands;

use App\Libraries\KrakenAPI;
use App\Models\HighValue;
use App\Models\Order;
use App\Notifications\CryptoInfoNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Util\Exception;

class CheckOrder extends Command
{
    protected $orderModel;
    protected $highValue;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cronjob for checking when Order has to sell';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->orderModel = Order::all();
        $this->highValue = HighValue::first();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->orderModel->count() > 0) {
            // Receive price
            $etherPrice = $this->getPrice('ETHEUR');
            $sellVolume = 0;
            $amount = 0;
            $newBuyOrderProfit = false;
            $newBuyOrderLoss = false;

            foreach ($this->orderModel as $order) {
                if ((int)$etherPrice > $order->sell_value_high) {
                    // Value sold based on profit
                    // set new buy order
                    $newBuyOrderProfit = true;
                    // add bought volume
                    $sellVolume += $order->volume;
                    $amount += $order->amount;
                } else if ((int)$etherPrice <= $order->sell_value_low) {
                    $newBuyOrderLoss = true;
                    // add bought volume
                    $sellVolume += $order->volume;
                    $amount += $order->amount;
                }
            }

            if ($sellVolume > 0) {
                if ($newBuyOrderProfit) {
                    // remove 5% from sell volume to keep in account
                    $sellVolume = $sellVolume / 1.05;
                }

                $this->sellVolume($sellVolume, $newBuyOrderProfit, $newBuyOrderLoss, $amount);
            }
        }
    }

    /**
     * Sell ETH value
     *
     * @return void
     */
    public function sellVolume($volume, $newBuyOrder, $newSellOrder, $amount)
    {
        $kraken = new KrakenAPI(env('KRAKEN_API'), env('KRAKEN_SECRET'));
        $etherPrice = $this->getPrice('ETHEUR');

        $etherPrice = (int)$etherPrice - 1; // sell price
        $res = $kraken->QueryPrivate('AddOrder', array(
            'pair' => 'ETHEUR',
            'type' => 'sell',
            'ordertype' => 'limit',
            'price' => $etherPrice,
            'volume' => $volume
        ));

        $res = json_encode($res);
        Notification::send('update', new CryptoInfoNotification([
            'text' => "Sell volume at kraken: {$res}. {$volume} . {$etherPrice}"
        ]));

        if ($newBuyOrder) {
            // set buy volume back to first buy
            $volume *= 1.05;
            // You've sold your ETH volume with profit
            // set new Order with new ETH price
            $this->buyOrderHigh($amount, true);
        }

        if ($newSellOrder) {
            // You've sold your ETH volume with loss
            // set new Order with new ETH price
            $this->buyOrderLow($amount, false);
        }
    }

    /**
     * Set high order values
     *
     * @return void
     */
    public function buyOrderHigh($amount, $profit)
    {
        $etherPrice = $this->getPrice('ETHEUR');

        $sell_high = $etherPrice + $this->highValue->high_value;
        $etherPrice = (int)$etherPrice;
        $etherPrice += 0.15; // higher the price for buying
        // set buy value low - 3.5%
        $sell_low = round($etherPrice / 1.035, 2, PHP_ROUND_HALF_ODD);

        // create the order
        $this->createOrder($etherPrice, $sell_high, $sell_low, $profit, $amount);
    }

    /**
     * Set low order values
     * Instant buy
     * @return void
     */
    public function buyOrderLow($amount, $profit)
    {
        $etherPrice = $this->getPrice('ETHEUR');

        $sell_high = $etherPrice + $this->highValue->high_value;
        $etherPrice = (int)$etherPrice;
        $etherPrice += 0.15; // higher the price for buying
        // set buy value -5.5%
        $sell_low = round($etherPrice / 1.055, 2, PHP_ROUND_HALF_ODD);

        $this->createOrder$etherPrice, $sell_high, $sell_low, $profit, $amount);
    }

    /**
     * Create order
     * place order at Kraken
     * Notify
     * @return void
     */
    public function createOrder($etherPrice, $sell_high, $sell_low, $profit, $amount) {
        try {
            $volume = $amount / $etherPrice;

            Order::create([
                'currency' => 'eth',
                'amount' => $amount,
                'volume' => $volume,
                'sell_high' => round($sell_high, 4, PHP_ROUND_HALF_ODD),
                'sell_low' => round($sell_low, 4, PHP_ROUND_HALF_ODD)
            ]);
        } catch (exception $e) {
            Notification::send('update', new CryptoInfoNotification([
                'text' => "Failed to create Order:
                {$e->getMessage()}"
            ]));

            return;
        }

        // place new order
        $date = date_create();

        $kraken = new KrakenAPI(env('KRAKEN_API'), env('KRAKEN_SECRET'));
        $res = $kraken->QueryPrivate('AddOrder', array(
            'pair' => 'ETHEUR',
            'type' => 'buy',
            'ordertype' => 'market',
            'oflags' => 'fciq',
            'volume' => $volume,
            'starttm' => date_timestamp_get($date)
        ));

        $res = json_encode($res);
        Notification::send('update', new CryptoInfoNotification([
            'text' => "Buy new volume at Kraken: {$res}"
        ]));

        $etherPrice = $this->getPrice('ETHEUR');
        $value = (int)$etherPrice * $volume;

        if ($profit) {
            Notification::send('update', new CryptoInfoNotification([
            'text' => "Successfull sold with profit!

            Order placed at Kraken:
            Ether price: {$etherPrice}
            Volume: {$volume}
            Sell high: {$sell_high}
            Sell low: {$sell_low}

            Transferred back to balance: {$value}"
            ]));
        } else {
            Notification::send('update', new CryptoInfoNotification([
            'text' => "Sold without profit!

            Order placed at Kraken:
            Ether price: {$etherPrice}
            Volume: {$volume}
            Sell high: {$sell_high}
            Sell low: {$sell_low}

            Transferred back to balance: {$value}"
            ]));
        }

        // delete old order
        foreach ($this->orderModel as $order) {
            try {
                $order->delete();
            } catch (exception $e) {
                Notification::send('update', new CryptoInfoNotification([
                    'text' => "Failed to delete record"
                ]));
            }
        }

        // Check for orders in database
        $msg = "";
        if ($this->orderModel->count() >= 1) {
            foreach($this->orderModel as $order) {
                $msg .= "
                Order in database:
                Volume: {$order->volume}
                Sell high: {$order->sell_value_high}
                Sell low: {$order->sell_value_low}
                ";
            }
        }

        Notification::send('update', new CryptoInfoNotification([
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



