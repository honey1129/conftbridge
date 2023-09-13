<?php

namespace App\Console\Commands;

use App\Http\Traits\WriteUserMoneyLog;
use App\Models\AuctionCard;
use App\Models\AuctionCardPaiLog;
use App\Models\AuctionYuLog;
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

class CfaEndPai extends Command
{
    use WriteUserMoneyLog;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cfa_end_pai';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '结束拍卖';

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
            AuctionCard::where(['status' => 1])->chunkById(100, function ($auctions) use ($now)
            {
                foreach ($auctions as $auction) {
                    $endTime = strtotime($auction->end_time);
                    if ($now > $endTime) {
                        dump($auction);
                        // 结束拍卖, 更新redis状态
                        $auction->status = 2;
                        $auction->save();

                        $end = strtotime($auction->end_time) + 3600;
                        $id = $auction->id;
                        $auctionJson = json_encode($auction);
                        Redis::set('auction:' . $id, $auctionJson, 'EXAT', $end);

                        // 拿到中拍者
                        $zhongOrder = AuctionCardPaiLog::where(['speed_card_id' => $auction->id])->orderBy('piao_num', 'desc')->first();
                        $zhongOrder->status = 1;
                        $zhongOrder->save();

                        $zhongYuOrder = AuctionYuLog::where(['speed_card_id' => $auction->id, 'uid' => $zhongOrder->uid])->first();
                        $zhongYuOrder->status = 1;
                        $zhongYuOrder->save();


                        $auction->zhong_id = $zhongOrder->id;
                        $auction->save();

                        // 退还未中拍U和票
                        $noZhongPiaoLogs = AuctionCardPaiLog::where(['speed_card_id' => $auction->id, 'status' => 0])->get();
                        foreach ($noZhongPiaoLogs as $noZhongPiaoLog) {
                            DB::beginTransaction();
                            // 退票
                            $noZhongPiaoLog->is_reback = 1;
                            $noZhongPiaoLog->save();
                            $rebackPiao = $noZhongPiaoLog->piao_num;
                            $userPiaoAsset = UserAssets::getBalance($noZhongPiaoLog->uid, 4, 1, true);
                            $this->writeBalanceLog($userPiaoAsset, 0, $rebackPiao, 36, '拍卖退还', 'reback CFT', $userPiaoAsset->pid, $userPiaoAsset->pname);


                            $yuLog = AuctionYuLog::where(['speed_card_id' => $auction->id, 'uid' => $noZhongPiaoLog->uid])->first();
                            $yuLog->is_reback = 1;
                            $yuLog->save();
                            $userUsdtAsset = UserAssets::getBalance($noZhongPiaoLog->uid, 8, 1, true);
                            $this->writeBalanceLog($userUsdtAsset, 0, $auction->price, 36, '拍卖退还', 'reback U', $userUsdtAsset->pid, $userUsdtAsset->pname);
                            DB::commit();
                        }
                    }
                }
            });

            Log::info('command end pai');
        } catch (\Exception $exception) {
            Log::info($exception);
        }
    }
}