<?php

namespace App\Console\Commands;

use App\Http\Traits\WriteUserMoneyLog;
use App\Models\ChildPool;
use App\Models\MasterPool;
use App\Models\SystemValue;
use App\Models\UserAssets;
use App\Models\AssetRelease;
use App\Models\UserPoolOrder;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Swoole\Coroutine\Channel;
use function Swoole\Coroutine\run;
use function Swoole\Coroutine\go;

class CfaPoolRecommend extends Command
{
    use WriteUserMoneyLog;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cfa_pool_recommend';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cfa池主推池主';

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
            $rate = config('award.master_to_master');
            User::where(['is_pooler' => 1])->chunkById(100, function ($users) use ($rate)
            {
                foreach ($users as $user) {
                    dump($user->email);
                    $parent = User::where(['id' => $user->recommend_id])->first();
                    // usdt 收益
                    $userBalance = UserAssets::getBalance($user->id, 3);
                    if ($parent && $parent->is_pooler && $parent->static_status) {
                        // 上级是池主
                        $parentPool = MasterPool::where(['uid' => $parent->id, 'status' => 1])->orderBy('level', 'desc')->first();
                        $userPool = MasterPool::where(['uid' => $user->id, 'status' => 1])->orderBy('level', 'desc')->first();

                        if ($parentPool && $userPool) {
                            if ($parentPool->level > $userPool->level) {
                                $level = $userPool->level;
                            } else {
                                $level = $parentPool->level;
                            }

                            $childPools = ChildPool::where(['master_id' => $userPool->id])->where('level', '<=', $level)->get();
                            $totalStaticBalance = 0;
                            foreach ($childPools as $childPool) {
                                $childPoolOrderMoney = UserPoolOrder::where(['master_pool_id' => $userPool->id, 'child_pool_id' => $childPool->id, 'status' => 1])->sum('price');
                                $staticBalance = $childPoolOrderMoney * $childPool->balance_rate * 0.01;
                                $totalStaticBalance += $staticBalance;
                            }

                            $parentBalance = round($totalStaticBalance * $rate * 0.01, 6);
                            dump($parentBalance);
                            if ($parentBalance > 0) {
                                DB::beginTransaction();
                                $parentUsdtAsset = UserAssets::getBalance($parent->id, 3, 1, true);
                                $this->writeBalanceLog($parentUsdtAsset, 0, $parentBalance, 22, '池主推荐：' . $user->email, 'pooler recommend', $parentUsdtAsset->pid, $parentUsdtAsset->pname);
                                DB::commit();
                            }
                        } else {
                            Log::info($parent->email . '或' . $user->email . '没有开启中的池子');
                        }
                    }
                }
            });

            Log::info('command cfa_pool_recommend');
        } catch (\Exception $exception) {
            dump($exception);
            Log::error($exception);
        }
    }
}