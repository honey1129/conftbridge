<?php

namespace App\Console\Commands;

use App\Http\Traits\WriteUserMoneyLog;
use App\Models\UserAssets;
use App\Models\AssetRelease;
use App\Models\UserPoolOrder;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use function Swoole\Coroutine\run;
use function Swoole\Coroutine\go;

class CfaUpdateUserLevel extends Command
{
    use WriteUserMoneyLog;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cfa_update_user_level';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '更新用户等级';

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
            run(function ()
            {
                $this->configs = config();

                $v1U = $this->configs['award.vip_one_u'];
                $v1Piao = $this->configs['award.vip_one_piao'];

                $v2U = $this->configs['award.vip_two_u'];
                $v2Piao = $this->configs['award.vip_two_piao'];

                $v3U = $this->configs['award.vip_three_u'];
                $v3Piao = $this->configs['award.vip_three_piao'];

                $v4U = $this->configs['award.vip_four_u'];
                $v4Piao = $this->configs['award.vip_four_piao'];

                $v5U = $this->configs['award.vip_five_u'];
                $v5Piao = $this->configs['award.vip_five_piao'];

                $v6U = $this->configs['award.vip_six_u'];
                $v6Piao = $this->configs['award.vip_six_piao'];

                User::chunkById(1000, function ($users) use ($v1U, $v1Piao, $v2U, $v2Piao, $v3U, $v3Piao, $v4U, $v4Piao, $v5U, $v5Piao, $v6U, $v6Piao)
                {
                    foreach ($users as $user) {
                        if (!$user->is_vip) {
                            $minYj = 0;
                            $childs = User::where(['recommend_id' => $user->id])->get();
                            if ($childs->count() > 1) {
                                $yjs = [];
                                foreach ($childs as $child) {
                                    $yjs[$child->id] = $child->per_yj + $child->team_yj;
                                }

                                $maxYj = max($yjs);
                                $maxKey = array_search($maxYj, $yjs);
                                unset($yjs[$maxKey]);
                                $minYj = array_sum($yjs);
                            } else {
                                $maxYj = 0;
                                $minYj = 0;
                            }


                            $userPiaoAsset = UserAssets::getBalance($user->id, 4);
                            $piaoNum = UserPoolOrder::where(['status' => 1, 'uid' => $user->id])->sum('piao_num');
                            $userPiaoBalance = $userPiaoAsset->balance + $piaoNum;

                            Log::info($user->email);
                            Log::info('小区业绩：' . $minYj);
                            Log::info('用户票：' . $userPiaoBalance);
                            $yuanLevel = $user->level;

                            if ($maxYj >= $minYj && $minYj >= $v1U && $userPiaoBalance >= $v1Piao) {
                                $user->level = 1;
                            } else {
                                $user->level = 0;
                            }

                            if ($maxYj >= $minYj && $minYj >= $v2U && $userPiaoBalance >= $v2Piao) {
                                $user->level = 2;
                            }

                            if ($maxYj >= $minYj && $minYj >= $v3U && $userPiaoBalance >= $v3Piao) {
                                $user->level = 3;
                            }

                            if ($maxYj >= $minYj && $minYj >= $v4U && $userPiaoBalance >= $v4Piao) {
                                $user->level = 4;
                            }

                            if ($maxYj >= $minYj && $minYj >= $v5U && $userPiaoBalance >= $v5Piao) {
                                $user->level = 5;
                            }

                            if ($maxYj >= $minYj && $minYj >= $v6U && $userPiaoBalance >= $v6Piao) {
                                $user->level = 6;
                            }

                            if (($yuanLevel == $user->level) && ($user->level != 0)) {
                                $user->level_days++;
                            } else {
                                $user->level_days = 1;
                            }
                            Log::info('等级：' . $user->level);
                            $user->save();
                        }
                    }
                });
            });
            Log::info('command cfa_update_user_level');
        } catch (\Exception $exception) {
            Log::info($exception);
        }
    }
}