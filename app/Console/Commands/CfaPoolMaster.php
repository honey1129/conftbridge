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

class CfaPoolMaster extends Command
{
    use WriteUserMoneyLog;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cfa_pool_master';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cfa 池主 10% 收益';

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
            MasterPool::where('user_num', '>', 0)->where(['status' => 1])->chunkById(100, function ($masterPools)
            {
                foreach ($masterPools as $masterPool) {
                    $totalBalance = 0;
                    if ($masterPool->level < 7) {
                        $userPiaoAsset = UserAssets::getBalance($masterPool->uid, 4, 1, true);
                        if ($userPiaoAsset->balance > 1) {
                            // 每天扣池主一张票，扣完送池主10%静态收益
                            $this->writeBalanceLog($userPiaoAsset, 0, -1, 19, '燃料消耗', 'piao card', $userPiaoAsset->pid, $userPiaoAsset->pname);

                            $user = User::where(['id' => $masterPool->uid])->first();
                            if ($user->static_status) {
                                $childPools = ChildPool::where(['master_id' => $masterPool->id, 'status' => 1])->get();

                                foreach ($childPools as $childPool) {
                                    $childPoolBalance = UserPoolOrder::where(['child_pool_id' => $childPool->id, 'status' => 1])->sum('price');
                                    $totalBalance += ($childPoolBalance * $childPool->balance_rate * 0.01);
                                }

                                $userUsdtAsset = UserAssets::getBalance($masterPool->uid, 3, 1, true);
                                $balance = $totalBalance * config('site.pool_master_balance') * 0.01;
                                $this->writeBalanceLog($userUsdtAsset, 0, $balance, 25, '燃料消耗收益', 'master pool banalce', $userUsdtAsset->pid, $userUsdtAsset->pname);
                            }
                        }
                    }
                }
            });
            Log::info('command cfa_pool_master');
        } catch (\Exception $exception) {
            var_dump($exception->getMessage());
            Log::info('static Faild' . $exception->getMessage());
        }
    }
}