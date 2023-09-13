<?php

namespace App\Console\Commands;

use App\Http\Traits\ClosePositions;
use App\Models\CfaPoolOrder;
use App\Models\SystemValue;
use App\Models\UserAssets;
use App\Models\UserPoolOrder;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use function Swoole\Coroutine\Http\get;
use function Swoole\Coroutine\run;
use function Swoole\Coroutine\go;
use Log;

class CfaReleaseCoin extends Command
{
    use ClosePositions;

    //脚本命令
    protected $signature = 'cfa_release_coin';
    //脚本名称
    protected $description = 'cfa 释放';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        try {
            // 180 天开始计算
            $day = SystemValue::where(['name' => 'cfa_release_day'])->value('value');
            // 昨天释放的CFA
            $releaseNum = SystemValue::where(['name' => 'cfa_release_num'])->value('value');

            // 昨天计算的今天的释放类型
            $releaseType = SystemValue::where(['name' => 'cfa_release_type'])->value('value');


            if ($releaseType == 0) {
                $releaseCfaNum = $releaseNum -= 1;
                $day -= 1;
            } else if ($releaseType == 1) {
                $releaseCfaNum = $releaseNum += 1;
                $day += 1;
            }

            // $result = (int)($day / 180);

            // if ($result % 2 == 0) {
            //     $type = 'sub';
            // } else {
            //     $type = 'add';
            // }

            // if ($type == 'sub') {
            //     $releaseCfaNum = $releaseNum - 1;
            // } else {
            //     $releaseCfaNum = $releaseNum + 1;
            // }

            $marketRate = config('release.market'); //市场分配
            $superNodeOneRate = config('release.super_node_one'); // 每周算力前21名
            $marketMangerRate = config('release.super_node_market'); // 市场管理
            $itRate = config('release.it'); // 开发者分配比例
            $companyRate = config('release.company'); // 基金会分配比例



            $marketNum = $releaseCfaNum * $marketRate * 0.01;
            $superNodeOneNum = $releaseCfaNum * $superNodeOneRate * 0.01;
            $marketManagerNum = $releaseCfaNum * $marketMangerRate * 0.01;
            $itNum = $releaseCfaNum * $itRate * 0.01;
            $companyNum = $releaseCfaNum * $companyRate * 0.01;


            dump($releaseCfaNum);

            $this->market($marketNum);
            $this->superNodeOne($superNodeOneNum);
            $this->superNodeManager($marketManagerNum);
            $this->developer($itNum);
            $this->company($companyNum);


            if ($day == 180) {
                SystemValue::where(['name' => 'cfa_release_type'])->update(['value' => 0]);
            } else if ($day == 0) {
                SystemValue::where(['name' => 'cfa_release_type'])->update(['value' => 1]);
            }

            SystemValue::where(['name' => 'cfa_release_day'])->update(['value' => $day]);
            SystemValue::where(['name' => 'cfa_release_num'])->update(['value' => $releaseCfaNum]);


            Log::info('command cfa_release_coin');
        } catch (\Exception $exception) {
            dump($exception);
            \Log::info($exception->__toString());
        }
    }

    public function market($num)
    {
        // 资产算力
        $assetCompute = UserAssets::where(['pid' => 5])->sum('balance');
        // 质押算力
        // $orderCompute = UserPoolOrder::where(['order_type' => 2, 'status' => 1])->sum('compute_num');
        $orderCompute = CfaPoolOrder::where(['status' => 1])->sum('price');
        $totalCompute = $assetCompute + $orderCompute * config('release.cfa_order_bei');

        // 1、节点算力
        User::where('node_level', '>', 0)->chunkById(100, function ($users) use ($totalCompute, $num)
        {
            foreach ($users as $user) {
                DB::beginTransaction();

                // 用户算力
                $userComputeBalance = UserAssets::where(['uid' => $user->id, 'pid' => 5])->value('balance');
                $rate = $userComputeBalance / $totalCompute;
                dump('用户算力：' . $userComputeBalance);
                dump('总算力：' . $totalCompute);
                $cfaNum = round($num * $rate, 6);
                dump('cfa数：' . $cfaNum);
                if ($cfaNum > 0 && ($user->has_release_cfa < $user->total_release_cfa)) {
                    $userCfaBalance = UserAssets::where(['uid' => $user->id, 'pid' => 1])->lockForUpdate()->first();
                    $this->writeBalanceLog($userCfaBalance, 0, $cfaNum, 13, '节点释放CFA', 'release CFA', $userCfaBalance->pid, $userCfaBalance->pname);

                    $user->has_release_cfa += $cfaNum;
                    $user->save();
                }
                DB::commit();
            }
        });
        // 2、质押算力
        CfaPoolOrder::chunkById(100, function ($orders) use ($totalCompute, $num)
        {
            foreach ($orders as $order) {
                DB::beginTransaction();
                $user = User::where(['id' => $order->uid])->select(['id', 'has_release_cfa', 'total_release_cfa'])->first();

                $orderCompute = $order->price;
                $bei = config('release.cfa_order_bei');
                $rate = $orderCompute * $bei / $totalCompute;
                $cfaNum = round($num * $rate, 6);

                if ($cfaNum > 0) {
                    $userCfaBalance = UserAssets::where(['uid' => $user->id, 'pid' => 1])->lockForUpdate()->first();
                    $this->writeBalanceLog($userCfaBalance, 0, $cfaNum, 13, '质押释放CFA', 'release CFA', $userCfaBalance->pid, $userCfaBalance->pname);
                    // $user->has_release_cfa += $cfaNum;
                    // $user->save();
                }
                DB::commit();
            }
        });
    }

    public function superNodeOne($num)
    {
        // 1、算力前21名,周释放,先累计起来
        SystemValue::where(['name' => 'super_node_one'])->increment('value', $num);
    }

    // 超级节点 市场管理
    public function superNodeManager($num)
    {
        $user = User::where(['id' => 1])->first();
        DB::beginTransaction();
        $userCfaAsset = UserAssets::where(['pid' => 1, 'uid' => $user->id])->lockForUpdate()->first();
        $this->writeBalanceLog($userCfaAsset, 0, $num, 13, '释放CFA', 'release CFA', $userCfaAsset->pid, $userCfaAsset->pname);
        DB::commit();
    }

    // 开发者
    public function developer($num)
    {
        $user = User::where(['id' => 2])->first();
        DB::beginTransaction();
        $userCfaAsset = UserAssets::where(['pid' => 1, 'uid' => $user->id])->lockForUpdate()->first();
        $this->writeBalanceLog($userCfaAsset, 0, $num, 13, '释放CFA', 'release CFA', $userCfaAsset->pid, $userCfaAsset->pname);
        DB::commit();
    }

    // 基金会
    public function company($num)
    {
        $user = User::where(['id' => 3])->first();
        DB::beginTransaction();
        $userCfaAsset = UserAssets::where(['pid' => 1, 'uid' => $user->id])->lockForUpdate()->first();
        $this->writeBalanceLog($userCfaAsset, 0, $num, 13, '释放CFA', 'release CFA', $userCfaAsset->pid, $userCfaAsset->pname);
        DB::commit();
    }

}