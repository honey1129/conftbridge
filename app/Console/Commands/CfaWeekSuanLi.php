<?php

namespace App\Console\Commands;

use App\Http\Traits\WriteUserMoneyLog;
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

class CfaWeekSuanLi extends Command
{
    use WriteUserMoneyLog;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cfa_week_suan_li';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cfa釋放每周算力前21名';

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
            $superNodeBalance = SystemValue::where(['name' => 'super_node_one'])->value('value');
            $userAssests = UserAssets::where(['pid' => 5])->where('balance', '>', 0)->orderBy('balance', 'desc')->limit(21)->get();

            $num = $userAssests->count();
            if ($num > 0) {
                $everyBalance = round($superNodeBalance / $num, 6);
                if ($everyBalance > 0) {
                    foreach ($userAssests as $userAsset) {
                        DB::beginTransaction();
                        $userCfaAsset = UserAssets::getBalance($userAsset->uid, 1, 1, true);
                        $userPiaoAsset = UserAssets::getBalance($userAsset->uid, 4, 1, true);
                        if ($userPiaoAsset->balance > $everyBalance) {
                            $this->writeBalanceLog($userCfaAsset, 0, $everyBalance, 21, '算力前21', '算力前21', $userCfaAsset->pid, $userCfaAsset->pname);
                            $this->writeBalanceLog($userPiaoAsset, 0, -$everyBalance, 21, '算力前21', '算力前21', $userPiaoAsset->pid, $userPiaoAsset->pname);
                            DB::commit();
                        } else {
                            DB::rollBack();
                        }
                    }
                }
            }
            SystemValue::where(['name' => 'super_node_one'])->update([
                'value' => 0
            ]);
            Log::info('command cfa_week_suanli');
        } catch (\Exception $exception) {
            Log::info($exception);
        }
    }
}