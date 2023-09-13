<?php

namespace App\Console\Commands;

use App\Http\Traits\WriteUserMoneyLog;
use App\Models\AssetRelease;
use App\Models\QiqOrder;
use App\Models\UserAssets;
use App\Models\UserPosition;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class OptionsSubscribe extends Command
{
    use WriteUserMoneyLog;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'options:subscribe';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '期权交易(计划任务)';

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

            QiqOrder::where('status', 1)
                    ->chunkById(100, function ($order_list)
                    {
                        $qiq_rate = config('site.qiq_rate');
                        foreach ($order_list as $val) {
                            $earnprice = -$val['buynum'];
                            $newPrice  = Redis::get('vb:ticker:newprice:' . $val['pname']);
                            if (now() >= $val['endtime']) {
                                if (($val['type'] == 1) && ($newPrice > $val['wtprice'])) {
                                    $earnprice = number_format($val['buynum'] * (100 + $qiq_rate) * 0.01, '6', '.', '');
                                }
                                if (($val['type'] == 2) && ($newPrice < $val['wtprice'])) {
                                    $earnprice = number_format($val['buynum'] * (100 + $qiq_rate) * 0.01, '6', '.', '');
                                }
                                QiqOrder::where('id', $val['id'])->update([
                                    'status'    => 2, 'cjprice' => $newPrice,
                                    'earnprice' => $earnprice
                                ]);
                                if ($earnprice > 0) {
                                    $asset = UserAssets::getBalance($val['uid'], 8, 4);
                                    $this->writeBalanceLog($asset, $val['id'], $earnprice, 46, '期权交易收益', 'Option trading income', 8, 'USDT', 4);
                                }
                            }
                            $this->updateTeamAward($val['uid'], $val['id'], $val['fee']);
                        }
                    });
        } catch (\Exception $exception) {
            Log::info('static Faild' . $exception->getMessage());
        }
    }

    /**
     * 更新团队奖励
     * @param
     * @return array
     */
    public function updateTeamAward($uid, $oid, $money)
    {
        try {
            $inster_arr = array();
            $zt_one     = config('award.zt_one');
            $zt_two     = config('award.zt_two');
            $zt_three   = config('award.zt_three');
            $zt_four    = config('award.zt_four');
            $zt_five    = config('award.zt_five');
            $user_list  = UserPosition::where('uid', $uid)->orderBy('lay')->get()->toArray();
            if ($user_list) {
                foreach ($user_list as $key => $val) {
                    $lay_count = UserPosition::where(['pid' => $val['pid'], 'lay' => 1])->count() + 0;
                    if ($lay_count == 1) {
                        $zt_rate = $zt_one;
                    } else if ($lay_count == 2) {
                        $zt_rate = $zt_two;
                    } else if ($lay_count == 3) {
                        $zt_rate = $zt_three;
                    } else if ($lay_count == 4) {
                        $zt_rate = $zt_four;
                    } else {
                        $zt_rate = $zt_five;
                    }
                    $awar_money   = number_format($money * $zt_rate * 0.01, '6', '.', '');
                    $inster_arr[] = [
                        'uid'        => $val['pid'],
                        'order_no'   => 'JL' . md5(md5(time() . $val['pid'] . mt_rand(1000, 9999))),
                        'order_type' => 3,
                        'money'      => $awar_money,
                        'status'     => 0,
                        'memo'       => 3,
                        'en_memo'    => 3,
                        'pid'        => 8,
                        'oid'        => $oid,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $asset        = UserAssets::getBalance($val['pid'], 8, 4);
                    $this->writeBalanceLog($asset, 0, $awar_money, '47', 3, 3, 8, 'USDT', 4);
                }
            }
            if ($inster_arr) {
                AssetRelease::insert($inster_arr);
            }
            return ['code' => 200, 'data' => array()];
        } catch (\Exception $exception) {
            return ['code' => 500, 'msg' => '失败'];
        }
    }
}
