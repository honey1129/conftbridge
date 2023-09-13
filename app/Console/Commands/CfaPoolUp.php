<?php

namespace App\Console\Commands;

use App\Http\Traits\WriteUserMoneyLog;
use App\Models\CfaPool;
use App\Models\ChildPool;
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

class CfaPoolUp extends Command
{
    use WriteUserMoneyLog;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cfa_pool_up';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'CFA合成池';

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
            $cfaUserNum = config('pool.open_seven_num');
            ChildPool::where(['level' => 6])->where('user_num', '>', $cfaUserNum)->chunkById(100, function ($childPools)
            {
                foreach ($childPools as $childPool) {
                    $masterPool = MasterPool::where(['id' => $childPool->master_id])->first();
                    // 1、更新主池
                    $masterPool->status = -1;
                    $masterPool->save();

                    // 是否要判断还是不是池主?

                    // 2、更新子池
                    $childPools = ChildPool::where(['master_id' => $masterPool->id])->get();

                    foreach ($childPools as $childPool) {
                        $childPool->status = -1;
                        $childPool->save();
                    }

                    // 3、创建CFA池
                    $childPool = CfaPool::create([
                        'uid'         => $masterPool->uid,
                        'master_id'   => $masterPool->id,
                        'pool_name'   => $masterPool->pool_name . '-Ⅶ',
                        'pool_image'  => $masterPool->pool_image,
                        'piao_num'    => 0,
                        'usdt_num'    => 0,
                        'total_price' => 0,
                        'status'      => 1
                    ]);
                    $cfaChildPoolId = $childPool->id;
                    // 4、更新订单
                    $userPoolOrders = UserPoolOrder::where(['master_pool_id' => $masterPool->id])->get();
                    foreach ($userPoolOrders as $userPoolOrder) {
                        $userPoolOrder->old_child_pool_id = $cfaChildPoolId;

                        // 已合成CFA，不再释放静态收益
                        $userPoolOrder->order_type = 2;
                        $userPoolOrder->save();
                    }

                    $num = MasterPool::where(['uid' => $masterPool->uid, 'status' => 1])->count();
                    if ($num < 1) {
                        $masterPoolUser = User::where(['id' => $masterPool->uid])->first();
                        $masterPoolUser->is_pooler = 0;
                        $masterPoolUser->save();
                    }
                }
            });

            Log::info('command 合成CFA池');
        } catch (\Exception $exception) {
            Log::info($exception);
        }
    }
}