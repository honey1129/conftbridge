<?php

namespace App\Console\Commands;

use App\Http\Traits\WriteUserMoneyLog;
use App\Models\ChildPool;
use App\Models\MasterPool;
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

class CfaDeleteMasterPool extends Command
{
    use WriteUserMoneyLog;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cfa_delete_master_pool';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '销毁合成CFA池的主池';

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
            MasterPool::where(['status' => -1, 'user_num' => 0])->chunkById(100, function ($masterPools)
            {
                foreach ($masterPools as $key => $masterPool) {
                    # code...
                    $masterPool->delete();
                    ChildPool::where(['master_id' => $masterPool->id])->delete();
                }
            });
            Log::info('cfa_delete_master_pool');
        } catch (\Exception $exception) {
            var_dump($exception);
            Log::info($exception);
        }
    }
}