<?php

namespace App\Console\Commands;

use App\Http\Traits\WriteUserMoneyLog;
use App\Models\UserAssets;
use App\Models\AssetRelease;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class CashSubscribe extends Command
{
    use WriteUserMoneyLog;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cash:subscribe';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '持币奖励//矿池';

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
            $hour        = date('H');
            $task_status = \Cache::get('Task:CashSubscribe');
            if ($task_status == 200) {
                echo '脚本执行中';
                return '脚本执行中';
            }
            if ($hour == 0) {
                var_dump('进入脚本');
                \Cache::set('Task:CashSubscribe', 200, 3600);
                //获取奖励
                $insert_arr    = array();
                $release_one   = config('release.release_one');
                $release_two   = config('release.release_two');
                $release_three = config('release.release_three');
                $release_four  = config('release.release_four');
                $release_five  = config('release.release_five');
                $asset_list    =
                    UserAssets::where(['ptype' => 5, 'pid' => 8])->where('balance', '>', 0)->get()->toArray();
                if ($asset_list) {
                    foreach ($asset_list as $key => $val) {
                        if ($val['balance'] <= 1000) {
                            $release_rate = $release_one;
                        } else if (($val['balance'] > 1000) && ($val['balance'] <= 3000)) {
                            $release_rate = $release_two;
                        } else if (($val['balance'] > 3000) && ($val['balance'] <= 5000)) {
                            $release_rate = $release_three;
                        } else if (($val['balance'] > 5000) && ($val['balance'] <= 10000)) {
                            $release_rate = $release_four;
                        } else {
                            $release_rate = $release_five;
                        }
                        $release_money = number_format($val['balance'] * $release_rate * 0.01, '6', '.', '');
                        $inster_arr    = [];
                        $inster_arr[]  = [
                            'uid'        => $val['uid'],
                            'order_no'   => 'SF' . md5(md5(time() . $val['uid'] . mt_rand(1000, 9999))),
                            'order_type' => 1,
                            'money'      => $release_money,
                            'status'     => 1,
                            'memo'       => 1,
                            'en_memo'    => 1,
                            'pid'        => 8,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        $asset         = UserAssets::getBalance($val['uid'], 8, 1);
                        $this->writeBalanceLog($asset, 0, $release_money, 17, '矿池释放', 'Mine pool release', 8, 'USDT', 1);
                    }
                }
                if (count($insert_arr) > 5000) {
                    $inst_arr = $this->divisionData($insert_arr, 5000);
                    foreach ($inst_arr as $k => $v) {
                        AssetRelease::insert($v);
                    }
                } else {

                    AssetRelease::insert($insert_arr);
                }
            } else {
                echo '没进入脚本';
            }

        } catch (\Exception $exception) {
            var_dump($exception->getMessage());
            Log::info('static Faild' . $exception->getMessage());
        }
    }
}
