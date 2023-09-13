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
use Swoole\Coroutine\Channel;
use function Swoole\Coroutine\run;
use function Swoole\Coroutine\go;

class CfaCalcDirNum extends Command
{
    use WriteUserMoneyLog;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cfa_calc_dir_num';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '计算直推人数';

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
        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();
        try {
            // 计算用户直推，个人业绩
            User::chunkById(1000, function ($users) use ($today, $tomorrow)
            {
                foreach ($users as $user) {
                    $userYj = UserPoolOrder::where(['uid' => $user->id, 'status' => 1])->sum('usdt_num');
                    $user->per_yj = $userYj;

                    $userTodayYj = UserPoolOrder::where(['uid' => $user->id, 'status' => 1])->whereBetween('created_at', [$today, $tomorrow])->sum('usdt_num');
                    $user->today_add_yj = $userTodayYj;

                    $count = User::where(['recommend_id' => $user->id])->count();
                    $user->dir_num = $count;

                    $user->save();
                }
            });

            // 计算用户团队业绩
            User::chunkById(1000, function ($users)
            {
                foreach ($users as $user) {
                    $teamYj = User::whereRaw("find_in_set({$user->id}, relationship)")->sum('per_yj');

                    $user->team_yj = $teamYj;

                    $user->save();
                }
            });
            Log::info('cfa_calc_dir_num');
        } catch (\Exception $exception) {
            var_dump($exception);
            Log::info($exception);
        }
    }
}