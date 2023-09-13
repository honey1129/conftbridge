<?php

namespace App\Jobs;

use App\Models\Fbbuying;
use App\Models\Fbsell;
use App\Models\Fbtrans;
use App\Models\UserAssets;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Http\Traits\WriteUserMoneyLog;
use DB;
use Log;

class AutoConfirmation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, WriteUserMoneyLog;

    public $order;
    public $type;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($order,$type)
    {
        $this->order = $order;
        $this->type = $type;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try{
            $trans = Fbtrans::find($this->order->id);
            //如果订单不是待付款状态直接return


            //1自动确认 2自动取消
            if($this->type == 1){
                if($trans->status != Fbtrans::ORDER_PAID){
                    Log::info('AutoConfirmation status not 2 id'.$trans->id.' status '.$trans->status);
                    return;
                }
                $this->confirm($trans);
            }

            if($this->type == 2){
                if($trans->status != Fbtrans::ORDER_PENDING){
                    Log::info('AutoConfirmation status not 1 '.$trans->id.' status '.$trans->status);
                    return;
                }
                $this->cancel($trans);
            }

        } catch (Exception $exception){
            Log::info('AutoConfirmation catch Exception'.$exception->getMessage().$exception->getLine());
        }

    }

    public function confirm($trans){
        DB::beginTransaction();

        try {
            //购买人加余额
            $goAsset = UserAssets::getBalance($trans->gou_uid);

            $bool1 = $this->writeBalanceLog($goAsset, $trans->id, 'USDT', $trans->total_num, 24, '系统自动确认-增加余额');
            //减出售人冻结
            $chuAsset = UserAssets::getBalance($trans->chu_uid);
            $bool2 = $this->writeFrostLog($chuAsset, $trans->id, 'USDT', -$trans->total_num, 24, '系统自动确认-扣除冻结');

            if(!$bool1 || !$bool2){
                DB::rollBack();
            }
            //更新订单状态为已完成
            $trans->status = 3;
            $trans->save();

            DB::commit();
            return;
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::info('AutoConfirmation confirm rollBack'.$exception->getMessage().$exception->getLine());
            return;
        }
    }

    public function cancel($trans){
        if ($trans->type == 1) {
            $Fbquery = new Fbsell();
        } else {
            $Fbquery = new Fbbuying();
        }

        DB::beginTransaction();

        try {
            $Fbquery->where('order_no',$trans->jy_order)
                ->decrement('deals_num', $trans->total_num);

            $Fbquery->where('order_no',$trans->jy_order)
                ->increment('sxfee', $trans->sxfee);

            $chuAsset = UserAssets::getBalance($trans->chu_uid);

            if ($trans->type == 2) {
                $this->writeBalanceLog($chuAsset, $trans->id, 'USDT', $trans->total_num + $trans->sxfee, 25, '系统自动取消-增加余额');
                $this->writeFrostLog($chuAsset, $trans->id, 'USDT', -$trans->total_num, 25, '系统自动取消-减少冻结');
            }

            $trans->status = Fbtrans::ORDER_CANCEL;
            $trans->cancel_uid = $trans->chu_uid;
            $trans->cancel_at = now();
            $trans->save();

            DB::commit();
            return;
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::info('AutoConfirmation cancel rollBack'.$exception->getMessage().$exception->getLine());
            return;
        }


    }

}
