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

class CfaOtcLine extends Command
{
    use WriteUserMoneyLog;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cfa_otc_line';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'CFA OTC 走势图';

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
            $price = config('site.piao2usdt');

            DB::table('zzd_zoushi')->insert([
                'price'      => $price,
                'max_price'  => $price,
                'min_price'  => $price,
                'date'       => date('Y-m-d'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            Log::info('command cfa_otc_line');
        } catch (\Exception $exception) {
            Log::info($exception);
        }
    }
}