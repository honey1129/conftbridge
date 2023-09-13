<?php

namespace App\Console\Commands;

use App\Http\Traits\WriteUserMoneyLog;
use App\Models\ChildPool;
use App\Models\SystemValue;
use App\Models\UserAssets;
use App\Models\AssetRelease;
use App\Models\UserPoolOrder;
use App\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Swoole\Coroutine\Channel;
use Swoole\Coroutine\WaitGroup;
use function Swoole\Coroutine\run;
use function Swoole\Coroutine\go;

class CfaFenHong extends Command
{
    use WriteUserMoneyLog;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cfa_fen_hong';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cfa 分红';

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
        $this->configs = config();
        try {
            $fenStaticBalance = SystemValue::where(['name' => 'fen_static_balance'])->value('value');

            $level4Num = User::where(['level' => 4])->count();
            if ($level4Num > 0) {
                $level4Balance = $fenStaticBalance * $this->configs['award.v4_fen_rate'] * 0.01;
                $level4Balance = round($level4Balance / $level4Num, 6);

                User::where(['level' => 4])->chunkById(100, function ($users) use ($level4Balance)
                {
                    foreach ($users as $user) {
                        DB::beginTransaction();
                        $userAsset = UserAssets::getBalance($user->id, 3, 1, true);
                        if ($user->static_status) {
                            $this->writeBalanceLog($userAsset, 0, $level4Balance, 20, 'V4分红', 'V4分红', $userAsset->pid, $userAsset->pname);
                            DB::commit();
                        } else {
                            DB::rollBack();
                        }
                    }
                });
            }


            $level5Num = User::where(['level' => 5])->count();
            if ($level5Num > 0) {
                $level5Balance = $fenStaticBalance * $this->configs['award.v5_fen_rate'] * 0.01;
                $level5Balance = round($level5Balance / $level5Num, 6);

                User::where(['level' => 5])->chunkById(100, function ($users) use ($level5Balance)
                {
                    foreach ($users as $user) {
                        DB::beginTransaction();
                        $userAsset = UserAssets::getBalance($user->id, 3, 1, true);
                        if ($user->static_status) {
                            $this->writeBalanceLog($userAsset, 0, $level5Balance, 20, 'V5分红', 'V5分红', $userAsset->pid, $userAsset->pname);
                            DB::commit();
                        } else {
                            DB::rollBack();
                        }
                    }
                });
            }

            $level6Num = User::where(['level' => 6])->count();
            if ($level6Num > 0) {
                $level6Balance = $fenStaticBalance * $this->configs['award.v6_fen_rate'] * 0.01;
                $level6Balance = round($level6Balance / $level6Num, 6);

                User::where(['level' => 6])->chunkById(100, function ($users) use ($level6Balance)
                {
                    foreach ($users as $user) {
                        DB::beginTransaction();
                        $userAsset = UserAssets::getBalance($user->id, 3, 1, true);
                        if ($user->static_status) {
                            $this->writeBalanceLog($userAsset, 0, $level6Balance, 20, 'V6分红', 'V6分红', $userAsset->pid, $userAsset->pname);
                            DB::commit();
                        } else {
                            DB::rollBack();
                        }

                    }
                });
            }

            // 高级节点分5%
            $highNodeNum = User::where(['node_level' => 3])->count();
            if ($highNodeNum > 0) {
                $highNodeBalance = $fenStaticBalance * $this->configs['award.high_node_balance'] * 0.01;
                $highNodeBalance = round($highNodeBalance / $highNodeNum, 6);

                User::where(['node_level' => 3])->chunkById(100, function ($users) use ($highNodeBalance)
                {
                    foreach ($users as $user) {
                        DB::beginTransaction();
                        $userAsset = UserAssets::getBalance($user->id, 3, 1, true);
                        if ($user->static_status) {
                            $this->writeBalanceLog($userAsset, 0, $highNodeBalance, 24, '高级节点分红', '高级节点分红', $userAsset->pid, $userAsset->pname);
                            DB::commit();
                        }
                    }
                });
            }


            SystemValue::where(['name' => 'fen_static_balance'])->update([
                'value' => 0
            ]);
            Log::info('command cfa_fen_hong');
        } catch (\Exception $exception) {
            var_dump($exception->getMessage());
            Log::info('static Faild' . $exception->getMessage());
        }
    }
}