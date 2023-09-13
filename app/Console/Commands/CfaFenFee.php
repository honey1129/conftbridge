<?php

namespace App\Console\Commands;

use App\Http\Traits\WriteUserMoneyLog;
use App\Models\CfaPool;
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

class CfaFenFee extends Command
{
    use WriteUserMoneyLog;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cfa_fen_fee';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cfa 分10% 手续费';

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
            $this->configs = config();
            $today = Carbon::yesterday();
            $tommorow = Carbon::today();
            // $today = Carbon::today();
            // $tommorow = Carbon::tomorrow();

            $sevenDayAgo = Carbon::yesterday()->subDays(7);
            dump($sevenDayAgo);

            //$totalBase = SystemValue::where(['name' => 'pool_master_balance_full'])->value('value');




            // 3%
            $pool1 = User::where(['email' => 'pool1@qq.com'])->first();
            $pool1UserAsset = UserAssets::where(['pid' => 3, 'uid' => $pool1->id])->first();
            $total3Base = $pool1UserAsset->balance;

            // 50% =  30% 给今天点亮5池的池主 + 20% 4号池前10人
            $poolerBalance = $total3Base * 0.3;
            $userBalance = $total3Base * 0.2;

            // 今天点亮5池的人
            $fiveChildPools = ChildPool::where(['level' => 5, 'status' => 1])->whereBetween('open_time', [$today, $tommorow])->get();
            $num = $fiveChildPools->count();
            if ($num > 0) {
                $everyPoolerBalance = round($poolerBalance / $num, 6);
                if ($everyPoolerBalance > 0) {
                    foreach ($fiveChildPools as $fiveChildPool) {
                        $user = User::where(['id' => $fiveChildPool->uid])->first();
                        if ($user->static_status) {
                            DB::beginTransaction();
                            $pool1UserAsset = UserAssets::getBalance($pool1->id, 3, 1, true);
                            $this->writeBalanceLog($pool1UserAsset, 0, -$everyPoolerBalance, 26, $user->email . '点亮5号池', $user->email . '点亮5号池', $pool1UserAsset->pid, $pool1UserAsset->pname);

                            $poolerUserAsset = UserAssets::getBalance($fiveChildPool->uid, 3, 1, true);
                            $this->writeBalanceLog($poolerUserAsset, 0, $everyPoolerBalance, 18, '点亮5号池池主收益', 'pooler balance', $poolerUserAsset->pid, $poolerUserAsset->pname);
                            DB::commit();
                        }
                    }
                }
                $masterPoolIds = $fiveChildPools->pluck(['master_id']);
                $fourChildPools = ChildPool::where(['level' => 4, 'status' => 1])->whereIn('master_id', $masterPoolIds)->get();
                $fourUids = [];
                foreach ($fourChildPools as $fourChildPool) {
                    //->where('join_at', '<=', $sevenDayAgo)
                    $userPoolOrders = UserPoolOrder::where(['master_pool_id' => $fourChildPool->master_id, 'child_pool_id' => $fourChildPool->id, 'status' => 1])->where('join_at', '<=', $sevenDayAgo)->orderBy('price', 'desc')->limit(10)->get();
                    $uids = $userPoolOrders->pluck('uid')->toArray();
                    $fourUids = array_merge($fourUids, $uids);
                }

                $fourUidNum = count($fourUids);
                if ($fourUidNum > 0) {
                    $everyFourBalance = round($userBalance / $fourUidNum, 6);
                    if ($everyFourBalance > 0) {
                        foreach ($fourUids as $fourUid) {
                            $user = User::where(['id' => $fourUid])->first();
                            if ($user->static_status) {
                                DB::beginTransaction();
                                $pool1UserAsset = UserAssets::getBalance($pool1->id, 3, 1, true);
                                $this->writeBalanceLog($pool1UserAsset, 0, -$everyFourBalance, 26, $user->email . '点亮5号池4号池员收益', $user->email . '点亮5号池4号池员收益', $pool1UserAsset->pid, $pool1UserAsset->pname);

                                $poolerUserAsset = UserAssets::getBalance($fourUid, 3, 1, true);
                                $this->writeBalanceLog($poolerUserAsset, 0, $everyFourBalance, 18, $user->email . '点亮5号池4号池员收益', 'pool user balance', $poolerUserAsset->pid, $poolerUserAsset->pname);
                                DB::commit();
                            }
                        }
                    }
                }
            }

            // 2%
            $pool2 = User::where(['email' => 'pool2@qq.com'])->first();
            $pool2UserAsset = UserAssets::where(['pid' => 3, 'uid' => $pool2->id])->first();
            $total2Base = $pool2UserAsset->balance; // 今天的加昨天剩下的

            // 50% =  30% 给今天点亮6池的池主 + 20% 5号池前10人
            $poolerBalance = $total2Base * 0.3;
            $userBalance = $total2Base * 0.2;

            // 今天点亮6池的人
            $sixChildPools = ChildPool::where(['level' => 6, 'status' => 1])->whereBetween('open_time', [$today, $tommorow])->get();
            $num = $sixChildPools->count();

            if ($num > 0) {
                $everyPoolerBalance = round($poolerBalance / $num, 6);
                if ($everyPoolerBalance > 0) {
                    foreach ($sixChildPools as $sixChildPool) {
                        $user = User::where(['id' => $sixChildPool->uid])->first();
                        if ($user->static_status) {
                            DB::beginTransaction();
                            $pool2UserAsset = UserAssets::getBalance($pool2->id, 3, 1, true);
                            $this->writeBalanceLog($pool2UserAsset, 0, -$everyPoolerBalance, 26, $user->email . '点亮6号池', $user->email . '点亮6号池', $pool2UserAsset->pid, $pool2UserAsset->pname);

                            $poolerAsset = UserAssets::getBalance($sixChildPool->uid, 3, 1, true);
                            $this->writeBalanceLog($poolerAsset, 0, $everyPoolerBalance, 18, '点亮6号池池主收益', 'pooler balance', $poolerAsset->pid, $poolerAsset->pname);
                            DB::commit();
                        }
                    }
                }
                $masterPoolIds = $sixChildPools->pluck(['master_id']);
                $fiveChildPools = ChildPool::where(['level' => 5, 'status' => 1])->whereIn('master_id', $masterPoolIds)->get();
                $fiveUids = [];
                foreach ($fiveChildPools as $fourChildPool) {
                    //->where('join_at', '<=', $sevenDayAgo)
                    $userPoolOrders = UserPoolOrder::where(['master_pool_id' => $fourChildPool->master_id, 'child_pool_id' => $fourChildPool->id, 'status' => 1])->where('join_at', '<=', $sevenDayAgo)->orderBy('price', 'desc')->limit(10)->get();
                    $uids = $userPoolOrders->pluck('uid')->toArray();
                    $fiveUids = array_merge($fiveUids, $uids);
                }

                $fiveUidNum = count($fiveUids);
                if ($fiveUidNum > 0) {
                    $everyFiveBalance = round($userBalance / $fiveUidNum, 6);
                    if ($everyFiveBalance > 0) {
                        foreach ($fiveUids as $fiveUid) {
                            $user = User::where(['id' => $fiveUid])->first();
                            if ($user->static_status) {
                                DB::beginTransaction();
                                $pool2UserAsset = UserAssets::getBalance($pool2->id, 3, 1, true);
                                $this->writeBalanceLog($pool2UserAsset, 0, -$everyFiveBalance, 26, $user->email . '点亮6号池5号池员收益', $user->email . '点亮6号池5号池员收益', $pool2UserAsset->pid, $pool2UserAsset->pname);


                                $poolerUserAsset = UserAssets::getBalance($fiveUid, 3, 1, true);
                                $this->writeBalanceLog($poolerUserAsset, 0, $everyFiveBalance, 18, $user->email . '点亮6号池5号池员收益', 'pool user balance', $poolerUserAsset->pid, $poolerUserAsset->pname);
                                DB::commit();
                            }
                        }
                    }
                }
            }

            // 1%
            $pool3 = User::where(['email' => 'pool3@qq.com'])->first();
            $pool3UserAsset = UserAssets::where(['pid' => 3, 'uid' => $pool3->id])->first();
            $total1Base = $pool3UserAsset->balance;

            // 50% =  30% 给今天点亮6池的池主 + 20% 5号池前10人
            $poolerBalance = $total1Base * 0.3;
            $userBalance = $total1Base * 0.2;

            // 今天点亮CFA池的人
            // $sevenChildPools = ChildPool::where(['level' => 7, 'status' => 1])->whereBetween('open_time', [$today, $tommorow])->get();
            $sevenChildPools = CfaPool::whereBetween('created_at', [$today, $tommorow])->get();
            $num = $sevenChildPools->count();

            if ($num > 0) {
                $everyPoolerBalance = round($poolerBalance / $num, 6);
                if ($everyPoolerBalance > 0) {
                    foreach ($sevenChildPools as $sevenChildPool) {
                        $user = User::where(['id' => $sevenChildPool->uid])->first();
                        if ($user->static_status) {
                            DB::beginTransaction();
                            $pool3UserAsset = UserAssets::getBalance($pool3->id, 3, 1, true);
                            $this->writeBalanceLog($pool3UserAsset, 0, -$everyPoolerBalance, 26, $user->email . '点亮CFA池', $user->email . '点亮CFA池', $pool3UserAsset->pid, $pool3UserAsset->pname);

                            $poolerAsset = UserAssets::getBalance($sevenChildPool->uid, 3, 1, true);
                            $this->writeBalanceLog($poolerAsset, 0, $everyPoolerBalance, 18, '点亮CFA池池主收益', 'pooler balance', $poolerAsset->pid, $poolerAsset->pname);
                            DB::commit();
                        }
                    }
                }
                $masterPoolIds = $sevenChildPools->pluck(['master_id']);
                $sixChildPools = ChildPool::withTrashed()->where(['level' => 6, 'status' => -1])->whereIn('master_id', $masterPoolIds)->get();
                $sixUids = [];
                foreach ($sixChildPools as $sixChildPool) {
                    //->where('join_at', '<=', $sevenDayAgo)
                    $userPoolOrders = UserPoolOrder::withTrashed()->where(['master_pool_id' => $sixChildPool->master_id, 'child_pool_id' => $sixChildPool->id, 'status' => 1])->where('join_at', '<=', $sevenDayAgo)->orderBy('price', 'desc')->limit(10)->get();
                    dump($userPoolOrders);
                    $uids = $userPoolOrders->pluck('uid')->toArray();
                    $sixUids = array_merge($sixUids, $uids);
                }

                $sixUidNum = count($sixUids);
                if ($sixUidNum > 0) {
                    $everySixBalance = round($userBalance / $sixUidNum, 6);
                    if ($everySixBalance > 0) {
                        foreach ($sixUids as $sixUid) {
                            $user = User::where(['id' => $sixUid])->first();
                            if ($user->static_status) {
                                DB::beginTransaction();
                                $pool3UserAsset = UserAssets::getBalance($pool3->id, 3, 1, true);
                                $this->writeBalanceLog($pool3UserAsset, 0, -$everySixBalance, 26, $user->email . '点亮CFA池6号池员收益', $user->email . '点亮CFA池', $pool3UserAsset->pid, $pool3UserAsset->pname);

                                $poolerUserAsset = UserAssets::getBalance($sixUid, 3, 1, true);
                                $this->writeBalanceLog($poolerUserAsset, 0, $everySixBalance, 18, '点亮CFA池池员收益', 'pool user balance', $poolerUserAsset->pid, $poolerUserAsset->pname);
                                DB::commit();
                            }
                        }
                    }
                }
            }


            // $balance = $totalBase * 0.1;
            // SystemValue::where(['name' => 'high_node_balance'])->increment('value', $balance);


            // SystemValue::where(['name' => 'pool_master_balance_full'])->update([
            //     'value' => 0
            // ]);
            Log::info('command cfa_fen_fee');
        } catch (\Exception $exception) {
            dump($exception);
            Log::info($exception);
        }
    }
}