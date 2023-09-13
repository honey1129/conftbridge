<?php

namespace App\Console\Commands;

use App\Http\Traits\WriteUserMoneyLog;
use App\Models\AuctionCard;
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

class CfaStartPai extends Command
{
    use WriteUserMoneyLog;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cfa_start_pai';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '开启拍卖';

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
            $now = time();
            AuctionCard::where(['status' => 0])->chunkById(100, function ($auctions) use ($now)
            {
                foreach ($auctions as $auction) {
                    $startTime = strtotime($auction->start_time);
                    if ($now >= $startTime) {
                        dump($auction);
                        $auction->status = 1;
                        $auction->save();

                        $end = strtotime($auction->end_time) + 3600;
                        $id = $auction->id;
                        $curPrice = $auction->begin_piao;
                        $auction = json_encode($auction);
                        Redis::set('auction:' . $id, $auction, 'EXAT', $end);
                        Redis::set('auction:' . $id . ':curprice', $curPrice);
                    }
                }
            });

            Log::info('command start pai');
        } catch (\Exception $exception) {
            Log::info($exception);
        }
    }
}