<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('currency');
            $table->decimal('amount', $precision = 15, $scale = 4)->nullable();
            $table->decimal('volume', $precision = 15, $scale = 4)->nullable();
            $table->decimal('sell_value_high', $precision = 15, $scale = 4)->nullable();
            $table->decimal('sell_value_low', $precision = 15, $scale = 4)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
