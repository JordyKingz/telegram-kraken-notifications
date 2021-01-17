<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SellCrypto extends Command
{
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
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        return 0;
    }
}
