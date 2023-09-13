<?php

namespace App\Jobs;

use App\Models\AuctionCard;
use App\Models\AuctionCardPaiLog;
use App\Models\UserAssets;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use DB;
use Log;
use App\Http\Traits\WriteUserMoneyLog;

class HandleAuctionOrder implements ShouldQueue
{
    use WriteUserMoneyLog;
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $queueJson;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($queueJson)
    {
        //
        $this->queueJson = $queueJson;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
        Log::info($this->queueJson);
        $orderObj = json_decode($this->queueJson);
        try {
            DB::beginTransaction();
            $speedCardId = $orderObj->speed_card_id;

            // 加记录
            $auctionCard = AuctionCard::where(['id' => $speedCardId])->first();
            $auctionCard->current_piao = $orderObj->cft_num;
            $auctionCard->save();

            // 添加记录
            AuctionCardPaiLog::create([
                'uid'           => $orderObj->uid,
                'speed_card_id' => $orderObj->speed_card_id,
                'piao_num'      => $orderObj->cft_num,
                'status'        => 0,
                'is_reback'     => 0
            ]);

            // 扣钱
            $userCftAsset = UserAssets::getBalance($orderObj->uid, 4, 1, true);
            $this->writeBalanceLog($userCftAsset, 0, -$orderObj->cft_num, 35, '参与拍卖', 'join auction', $userCftAsset->pid, $userCftAsset->pname);
            DB::commit();
        } catch (\Exception $e) {
            Log::error($e);
            DB::rollBack();
        }
    }
}