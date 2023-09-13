<?php

namespace App\Console\Commands;

use App\Http\Traits\ClosePositions;
use App\Models\ChildPool;
use App\Models\MasterPool;
use App\Models\SystemValue;
use App\Models\UserAssets;
use App\Models\UserMoneyLog;
use App\Models\UserPoolOrder;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use function Swoole\Coroutine\Http\get;
use function Swoole\Coroutine\run;
use function Swoole\Coroutine\go;
use Log;

class CfaBuStatic extends Command
{
    use ClosePositions;

    //脚本命令
    protected $signature = 'cfa_bu_static';
    //脚本名称
    protected $description = 'cfa 补静态收益';


    protected $configs = [];
    /**
     * Create a new command instance.     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->configs = config();
        try {
            // User::chunkById(100, function ($users)
            // {
            //     foreach ($users as $user) {
            //         $userAsset = UserAssets::getBalance($user->id, 3);
            //         // usdt 收益 账户大于 10U, 无静态收益
            //         if ($userAsset->balance < 10) {
            //             $user->static_status = 1;
            //         } else {
            //             $user->static_status = 0;
            //         }
            //         $user->save();
            //     }
            // });

            $logs = UserMoneyLog::where(['type' => 14])->where('created_at', '>', '2023-07-18 01:00:00')->get();
            foreach ($logs as $log) {
                $staticMoney = $log->money;
                $user = User::where(['id' => $log->uid])->first();
                $order = UserPoolOrder::where(['uid' => $log->uid])->first();
                $this->recommendBalance($staticMoney, $user, $order);
            }

        } catch (\Exception $exception) {
            dump($exception);
            Log::info($exception->__toString());
        }
    }


    public function levelBalance($staticBalance, $user, $order)
    {
        $relastionship = $user->relationship;
        if (!$relastionship) {
            return;
        }
        $parentIds = explode(',', $relastionship);
        $newParentIds = array_reverse($parentIds);
        $newParentStr = implode(',', $newParentIds);
        $parents = User::whereIn('id', $parentIds)->where('level', '>', 0)->orderByRaw("FIELD(id,{$newParentStr})")->select(['id', 'level', 'email', 'static_status'])->get();



        $oneRate = $this->configs['award.vip_one_rate'] * 0.01;
        $twoRate = $this->configs['award.vip_two_rate'] * 0.01;
        $threeRate = $this->configs['award.vip_three_rate'] * 0.01;
        $fourRate = $this->configs['award.vip_four_rate'] * 0.01;
        $fiveRate = $this->configs['award.vip_five_rate'] * 0.01;
        $sixRate = $this->configs['award.vip_six_rate'] * 0.01;


        $levels = [];
        $pings = [];

        $oneBalance = 0;
        $twoBalance = 0;
        $threeBalance = 0;
        $fourBalance = 0;
        $fiveBalance = 0;
        $sixBalance = 0;
        foreach ($parents as $parent) {
            $isPing = false;
            $balance = 0;
            switch ($parent->level) {
                case 1:
                    if (
                        !in_array(2, $levels)
                        && !in_array(3, $levels)
                        && !in_array(4, $levels)
                        && !in_array(5, $levels)
                        && !in_array(
                            6,
                            $levels
                        )
                    ) {
                        if (in_array(1, $levels)) {
                            if (!in_array(1, $pings)) {
                                $rate = 0.05;
                                $pings[] = 1;
                                $balance = $oneBalance * $rate;
                                $isPing = true;
                            }
                        } else {
                            $rate = $oneRate;
                            $balance = $staticBalance * $rate;
                            $oneBalance = $balance;
                            $levels[] = 1;
                        }
                    }
                    break;
                case 2:
                    if (
                        !in_array(3, $levels)
                        && !in_array(4, $levels)
                        && !in_array(5, $levels)
                        && !in_array(
                            6,
                            $levels
                        )
                    ) {
                        if (in_array(2, $levels)) {
                            if (!in_array(2, $pings)) {
                                $rate = 0.05;
                                $pings[] = 2;
                                $balance = $twoBalance * $rate;
                                $isPing = true;
                            }
                        } else {
                            $rate = $twoRate;
                            if (in_array(1, $levels)) {
                                $rate -= $oneRate;
                            }
                            $balance = $staticBalance * $rate;
                            $twoBalance = $balance;
                            $levels[] = 2;
                        }


                    }
                    break;
                case 3:
                    if (
                        !in_array(4, $levels)
                        && !in_array(5, $levels)
                        && !in_array(
                            6,
                            $levels
                        )
                    ) {
                        if (in_array(3, $levels)) {
                            if (!in_array(3, $pings)) {
                                $rate = 0.05;
                                $pings[] = 3;
                                $balance = $threeBalance * $rate;
                                $isPing = true;
                            }
                        } else {
                            $rate = $threeRate;
                            if (in_array(2, $levels)) {
                                $rate -= $twoRate;
                            } else if (in_array(1, $levels)) {
                                $rate -= $oneRate;
                            }

                            $balance = $staticBalance * $rate;
                            $threeBalance = $balance;
                            $levels[] = 3;
                        }
                    }
                    break;
                case 4:
                    if (
                        !in_array(5, $levels)
                        && !in_array(
                            6,
                            $levels
                        )
                    ) {
                        if (in_array(4, $levels)) {
                            if (!in_array(4, $pings)) {
                                $rate = 0.05;
                                $pings[] = 4;
                                $balance = $fourBalance * $rate;
                                $isPing = true;
                            }
                        } else {
                            $rate = $fourRate;
                            if (in_array(3, $levels)) {
                                $rate -= $threeRate;
                            } else if (in_array(2, $levels)) {
                                $rate -= $twoRate;
                            } else if (in_array(1, $levels)) {
                                $rate -= $oneRate;
                            }

                            $balance = $staticBalance * $rate;
                            $fourBalance = $balance;
                            $levels[] = 4;
                        }
                    }
                    break;
                case 5:
                    if (
                        !in_array(
                            6,
                            $levels
                        )
                    ) {
                        if (in_array(5, $levels)) {
                            if (!in_array(5, $pings)) {
                                $rate = 0.05;
                                $pings[] = 5;
                                $balance = $fiveBalance * $rate;
                                $isPing = true;
                            }
                        } else {
                            $rate = $fiveRate;
                            if (in_array(4, $levels)) {
                                $rate -= $fourRate;
                            } else if (in_array(3, $levels)) {
                                $rate -= $threeRate;
                            } else if (in_array(2, $levels)) {
                                $rate -= $twoRate;
                            } else if (in_array(1, $levels)) {
                                $rate -= $oneRate;
                            }

                            $balance = $staticBalance * $rate;
                            $fiveBalance = $balance;
                            $levels[] = 5;
                        }
                    }
                    break;
                case 6:
                    if (in_array(6, $levels)) {
                        if (!in_array(6, $pings)) {
                            $rate = 0.05;
                            $pings[] = 6;
                            $balance = $sixBalance * $rate;
                            $isPing = true;
                        }
                    } else {
                        $rate = $sixRate;

                        if (in_array(5, $levels)) {
                            $rate -= $fiveRate;
                        } else if (in_array(4, $levels)) {
                            $rate -= $fourRate;
                        } else if (in_array(3, $levels)) {
                            $rate -= $threeRate;
                        } else if (in_array(2, $levels)) {
                            $rate -= $twoRate;
                        } else if (in_array(1, $levels)) {
                            $rate -= $oneRate;
                        }

                        $balance = $staticBalance * $rate;
                        $sixBalance = $balance;
                        $levels[] = 6;
                    }
                    break;
            }
            if ($isPing) {
                $mark = 'level ' . $parent->level . ' 平级奖励：' . $user->email;
            } else {
                $mark = 'level ' . $parent->level . ' 奖励：' . $user->email;
            }
            if ($balance > 0 && $parent->static_status) {

                DB::beginTransaction();
                // usdt 收益资产
                $usdtAsset = UserAssets::getBalance($parent->id, 3, 1, true);
                $this->writeBalanceLog($usdtAsset, $order->id, $balance, 15, $mark, 'level ' . $parent->level . ' balance', $usdtAsset->pid, $usdtAsset->pname);
                DB::commit();
            }

        }
    }


    public function recommendBalance($staticBalance, $user, $order)
    {
        dump($user->email);
        $relastionship = $user->relationship;
        if (!$relastionship) {
            return;
        }
        $parentIds = explode(',', $relastionship);
        $newParentIds = array_reverse($parentIds);
        $newParentStr = implode(',', $newParentIds);

        $userDeep = $user->deep;
        $parents = User::whereIn('id', $parentIds)->orderByRaw("FIELD(id,{$newParentStr})")->select(['id', 'email', 'level', 'deep', 'dir_num', 'static_status'])->get();

        // 拿下级的收益
        foreach ($parents as $parent) {
            $piaoAsset = UserAssets::getBalance($parent->id, 4);
            $recommendUsersNum = $parent->dir_num;
            dump($parent->email);
            dump('直推人数:' . $recommendUsersNum);
            $parentPoolOrder = UserPoolOrder::where(['uid' => $parent->id, 'status' => 1])->first();
            $piaoNum = UserPoolOrder::where(['uid' => $parent->id, 'status' => 1])->sum('piao_num');
            $piaoNum = $piaoAsset->balance + $piaoNum;
            if ($piaoNum > 100 && $parentPoolOrder) {
                DB::beginTransaction();
                if ($recommendUsersNum >= 3) {
                    // 取1~2层
                    $subDeep = $userDeep - $parent->deep;
                    dump('相差层级:' . $subDeep);
                    if ($subDeep >= 1 && $subDeep <= 2) {
                        $balance = round($staticBalance * $this->configs['award.zt_one'] * 0.01, 6);
                        dump('下层奖励：' . $balance);
                        if ($balance > 0 && $parent->static_status) {
                            $parentAsset = UserAssets::getBalance($parent->id, 3, 1, true);
                            $this->writeBalanceLog($parentAsset, $order->id, $balance, 16, '下' . $subDeep . '层：' . $user->email, 'recommend balance', $parentAsset->pid, $parentAsset->pname);
                        }
                    }
                }

                if ($recommendUsersNum >= 5) {
                    // 取3~5层
                    $subDeep = $userDeep - $parent->deep;
                    dump('相差层级:' . $subDeep);
                    if ($subDeep >= 3 && $subDeep <= 5) {
                        $balance = round($staticBalance * $this->configs['award.zt_three'] * 0.01, 6);
                        dump('下层奖励：' . $balance);
                        if ($balance > 0 && $parent->static_status) {
                            $parentAsset = UserAssets::getBalance($parent->id, 3, 1, true);
                            $this->writeBalanceLog($parentAsset, $order->id, $balance, 16, '下' . $subDeep . '层：' . $user->email, 'recommend balance', $parentAsset->pid, $parentAsset->pname);
                        }
                    }
                }

                // if ($recommendUsersNum >= 5) {
                //     // 取6~10层
                //     $subDeep = $userDeep - $parent->deep;
                //     dump('相差层级:' . $subDeep);
                //     if ($subDeep >= 6 && $subDeep <= 10) {
                //         $balance = round($staticBalance * $this->configs['award.zt_five'] * 0.01, 6);
                //         if ($balance > 0 && $parent->static_status) {
                //             dump('下层奖励：' . $balance);
                //             $parentAsset = UserAssets::getBalance($parent->id, 3, 1, true);
                //             $this->writeBalanceLog($parentAsset, $order->id, $balance, 16, '下' . $subDeep . '层：' . $user->email, 'recommend balance', $parentAsset->pid, $parentAsset->pname);
                //         }
                //     }
                // }
                DB::commit();
            } else {
                Log::info($parent->email . '票数量不足或没质押');
            }

        }
        // 拿上级的收益
        // 向上拿一层
        // $maxDeep = $userDeep + 5;
        // $subUsers = User::where('deep', '>', $userDeep)->where('deep', '<=', $maxDeep)->whereRaw("find_in_set({$user->id}, relationship)")->get();
        // foreach ($subUsers as $subUser) {
        //     $subDeep = $subUser->deep - $userDeep;
        //     $recommendUsersNum = $subUser->dir_num;
        //     $piaoAsset = UserAssets::getBalance($subUser->id, 4);
        //     $subUserPoolOrder = UserPoolOrder::where(['uid' => $subUser->id, 'status' => 1])->first();
        //     $piaoNum = UserPoolOrder::where(['uid' => $subUser->id, 'status' => 1])->sum('piao_num');
        //     $piaoNum = $piaoAsset->balance + $piaoNum;
        //     if ($piaoNum > 100 && $subUserPoolOrder) {
        //         if ($recommendUsersNum >= 1 && $recommendUsersNum < 3) {
        //             // 向上取一层
        //             if ($subDeep == 1) {
        //                 $subUserAsset = UserAssets::getBalance($subUser->id, 3, 1, true);
        //                 $balance = round($staticBalance * $this->configs['award.zt_up_rate'] * 0.01, 6);
        //                 if ($balance > 0 && $subUser->static_status) {
        //                     $this->writeBalanceLog($subUserAsset, $order->id, $balance, 16, '上' . $subDeep . '代：' . $user->email, 'recommend balance', $subUserAsset->pid, $subUserAsset->pname);
        //                 }
        //             }
        //         }

        //         if ($recommendUsersNum >= 3 && $recommendUsersNum < 5) {
        //             // 向上取3层
        //             if ($subDeep <= 3) {
        //                 $subUserAsset = UserAssets::getBalance($subUser->id, 3, 1, true);
        //                 $balance = round($staticBalance * $this->configs['award.zt_up_rate'] * 0.01, 6);
        //                 if ($balance > 0 && $subUser->static_status) {
        //                     $this->writeBalanceLog($subUserAsset, $order->id, $balance, 16, '上' . $subDeep . '代：' . $user->email, 'recommend balance', $subUserAsset->pid, $subUserAsset->pname);
        //                 }
        //             }
        //         }

        //         if ($recommendUsersNum >= 5) {
        //             // 向上取5层
        //             if ($subDeep <= 5) {
        //                 $subUserAsset = UserAssets::getBalance($subUser->id, 3, 1, true);
        //                 $balance = round($staticBalance * $this->configs['award.zt_up_rate'] * 0.01, 6);
        //                 if ($balance > 0 && $subUser->static_status) {
        //                     $this->writeBalanceLog($subUserAsset, $order->id, $balance, 16, '上' . $subDeep . '代：' . $user->email, 'recommend balance', $subUserAsset->pid, $subUserAsset->pname);
        //                 }
        //             }
        //         }
        //     } else {
        //         Log::info($subUser->email . '票数量不足或没质押');
        //     }
        // }

    }
}