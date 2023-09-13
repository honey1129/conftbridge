<?php

namespace App\Console\Commands;

use App\Http\Traits\WriteUserMoneyLog;
use App\Models\QiqOrder;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class TradeSubscribe extends Command
{
    use WriteUserMoneyLog;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trade:subscribe';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '期权交易机器人（计划任务）';

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
        try {
            $cycle     = config('site.qiq_cycle', 0);
            $trans_fee = config('site.qiq_fee', 0);//手续费比例
            $newPrice  = Redis::get('vb:ticker:newprice:btc_usdt');
            $buynum    = mt_rand(100, 999);
            $type      = mt_rand(1, 2);
            $zj        = $buynum;//金额
            $total     = $zj;
            $userInfo  = DB::table('users')->where('is_robot', 1)->inRandomOrder()->first();
            $info      = [
                'uid'        => $userInfo->id,
                'account'    => $userInfo->account,
                'pid'        => 1,
                'pname'      => 'btc_usdt',
                'wtprice'    => $newPrice,
                'buynum'     => $buynum,
                'totalprice' => $buynum,
                'type'       => $type,
                'status'     => 1,
                'is_robot'   => 1,
                'cycle'      => $cycle,
                'endtime'    => date('Y-m-d H:i:s', time() + $cycle),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $position  = QiqOrder::create($info);
            QiqOrder::where('status', '<>', 1)->where('uid', $userInfo->id)->where('is_robot', 1)->delete();
        } catch (\Exception $exception) {
            Log::info('static Faild' . $exception->getMessage());
        }
    }
}
