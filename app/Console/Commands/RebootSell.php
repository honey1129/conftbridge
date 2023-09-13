<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\TradeController;
use App\Http\Controllers\Rebot\RobotController;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RebootSell extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bb_order_sell';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '币币交易机器人下卖单(未使用)';

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
     * @return mixed
     */
    public function handle()
    {
        $reboot = new RobotController();
        $codes = Product::where(['type'=>2,'state'=>1])->pluck('code');
        $n = mt_rand(5, 10);
        sleep($n);
        $userInfo  = DB::table('users')->where('is_robot', 1)->inRandomOrder()->first();
        if(empty($userInfo))
        {
            return;
        }
        foreach ($codes as $k => $code)
        {
            $reboot->post_sell($userInfo->account, $code,$userInfo->down);
        }
        $n = mt_rand(5, 10);
        sleep($n);

    }
}
