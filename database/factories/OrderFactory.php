<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'currency' => 'eth',
            'amount' => 100,
            'volume' => 0.09912,
            'sell_value_high' => 1058,
            'sell_value_low' => 1000,
        ];
    }
}
