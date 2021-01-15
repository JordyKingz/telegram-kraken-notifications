<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCryptocurrenciesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cryptocurrencies', function (Blueprint $table) {
            $table->id();
            $table->string('currency')->unique();
            $table->decimal('bought_value', $precision = 15, $scale = 4);
            $table->decimal('sold_value', $precision = 15, $scale = 4);
            $table->decimal('sell_value', $precision = 15, $scale = 4);
            $table->decimal('buy_value', $precision = 15, $scale = 4);
            $table->integer('nonce');
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
        Schema::dropIfExists('cryptocurrencies');
    }
}
