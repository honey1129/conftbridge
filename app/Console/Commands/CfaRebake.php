<?php

namespace App\Console\Commands;

use App\Http\Traits\WriteUserMoneyLog;
use App\Models\UserAssets;
use App\Models\AssetRelease;
use App\Models\UserPoolOrder;
use App\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use function Swoole\Coroutine\run;
use function Swoole\Coroutine\go;
use App\Models\MasterPool;

class CfaRebake extends Command
{
    use WriteUserMoneyLog;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cfa_rebake';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cfa 退还押金';

    protected $configs;
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
            $now = Carbon::now();
            $dayAgo = $now->subDays(1);

            UserPoolOrder::where(['status' => 0, 'is_reback' => 0])->where('exit_at', '<', $dayAgo)->chunkById(100, function ($userPoolOrders)
            {
                foreach ($userPoolOrders as $userPoolOrder) {
                    $rebakePiao = $userPoolOrder->piao_num;
                    $rebakeUsdt = $userPoolOrder->usdt_num;

                    $masterPool = MasterPool::where(['id' => $userPoolOrder->master_pool_id])->first();
                    if ($userPoolOrder->type == 1) {
                        // U+票
                        if ($userPoolOrder->pay_type == 2) {
                            // 票是池主的
                            $masterPiaoAsset = UserAssets::getBalance($masterPool->uid, 6, 1, true);
                        } else {
                            // 票是自己的
                            $masterPiaoAsset = UserAssets::getBalance($userPoolOrder->uid, 4, 1, true);
                        }
                        $this->writeBalanceLog($masterPiaoAsset, 0, $rebakePiao, 11, '退出质押', '退出质押', $masterPiaoAsset->pid, $masterPiaoAsset->pname);
                        $userUsdtAsset = UserAssets::getBalance($userPoolOrder->uid, 8, 1, true);
                        $this->writeBalanceLog($userUsdtAsset, 0, $rebakeUsdt, 11, '退出质押', '退出质押', $userUsdtAsset->pid, $userUsdtAsset->pname);

                    } else {
                        // U
                        $userUsdtAsset = UserAssets::getBalance($userPoolOrder->uid, 8, 1, true);
                        $this->writeBalanceLog($userUsdtAsset, 0, $rebakeUsdt, 11, '退出质押', '退出质押', $userUsdtAsset->pid, $userUsdtAsset->pname);
                    }
                    $userPoolOrder->is_reback = 1;
                    $userPoolOrder->save();
                }
            });
            Log::info('command cfa_rebake');
        } catch (\Exception $exception) {
            Log::info($exception);
        }
    }
}