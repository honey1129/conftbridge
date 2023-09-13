<?php

namespace App\Http\Traits;

use App\Models\UserAssets;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

trait ClosePositions
{
    use WriteUserMoneyLog;
    /**
     * 爆仓检测
     * @param $message
     */
    public function doClosePositions($subscribe)
    {
        DB::table('user_positions')
        ->where('code', $subscribe['code'])
        ->chunkById(100, function ($positions) {
            foreach ($positions as $position) {
               // var_dump($position);
                if (Redis::exists($position->uid . ':bc_lock')) {
                    Log::info('REDIS 已上锁'.$position->uid);
                    continue;
                }
                //计算浮动盈亏
                $userPositions = DB::table('user_positions')
                ->where('uid',$position->uid)
                ->get();

                $minprofit = 0;
                $allprofit = 0;
                $alldeposit = 0;
                $minid = 0;

                //计算总保证金，总浮动盈亏，最小浮动盈亏
                foreach ($userPositions as $userPosition)
                {
                    //得到最新价
                    $newprice = Redis::get('vb:ticker:newprice:' . $userPosition->code);
                    if (!$newprice)
                    {
                        Log::info('没有得到最新价');
                        break;
                    }

                    $pinfo = DB::table('products')
                        ->where('code',$position->code)
                        ->select('pid', 'pname', 'code','spread', 'var_price')
                        ->first();

                    //加减点差
                    $spread = $pinfo->spread;
                    if ($position->otype == 1)
                    {
                        $newprice -= $spread;
                    }
                    else {
                        $newprice += $spread;
                    }

                    if ($userPosition->otype == 1) {
                        $profit = ($newprice - $userPosition->buyprice)  * $userPosition->buynum;
                    } else {
                        $profit = ($userPosition->buyprice - $newprice) * $userPosition->buynum;
                    }

                    if ($profit < $minprofit) {
                        $minprofit = $profit;
                        $minid     = $userPosition->id;
                    }

                    $allprofit += $profit;
                    $alldeposit += $userPosition->totalprice;

                }

                unset($userPositions);

                if($alldeposit <= 0){
                    continue;
                }

                $balance = DB::table('user_assets')
                            ->where(['uid'=>$position->uid,'pid'=>8,'ptype'=>3])
                            ->value('balance');
                //计算爆仓率
                //（余额 + 保证金 + 浮动盈亏）/ 保证金
                $risk = round(($balance + $alldeposit + $allprofit) / $alldeposit, 2);

                //取到后台设置爆仓率
                //计算爆仓率 <= 爆仓率 触发 爆仓
                $bcRate = config('site.bc_rate')?? 0;
                if($risk > $bcRate*0.01){
                    continue;
                }
                $minPosition = DB::table('user_positions')
                                ->where('id',$minid)
                                ->first();

                if(empty($minPosition)){
                    $minPosition = DB::table('user_positions')
                        ->where('uid',$position->uid)
                        ->first();

                    if(empty($minPosition)){
                        continue;
                    }
                }

                //得到最新价
                $newprice = Redis::get('vb:ticker:newprice:' . $minPosition->code);
                if (!$newprice) {
                    Log::info('没有得到最新价');
                    break;
                }

                $queueData['pc_type']  = 4;
                $queueData['price']    = $newprice;
                $queueData['position'] = $minPosition;
                $queueData['memo']     = '系统爆仓';
                $queueData['en_memo']     = 'System liquidation';
                $server = Redis::connection('server');
                $tid = $server->lpush('positions_process', json_encode($queueData));
                unset($queueData);
                
                if ($tid === false)
                {
                    Log::info('推入队列失败'.$minPosition->id.'=='.$minPosition->code);
                    continue;
                }
                else {
                    Log::info('REDIS上锁=='.$minPosition->uid.'=='.$minPosition->id);
                    Redis::setex($minPosition->uid . ':bc_lock',10,1);

                    DB::beginTransaction();

                    try{
                        //  查询余额 并上锁
                        $assets = UserAssets::getBalance($minPosition->uid, 8,3,true);
                        $totalprice = DB::table('user_entrusts')
                            ->where('uid',$minPosition->uid)
                            ->where('status',1)
                            ->sum('totalprice');

                        $fee = DB::table('user_entrusts')
                            ->where('uid',$minPosition->uid)
                            ->where('status',1)
                            ->sum('fee');

                        if($totalprice + $fee > 0)
                        {
                            $this->writeBalanceLog($assets, 0,  $totalprice + $fee, 3, '合约交易撤单','Contract transaction cancellation',8,'USDT',3);
                            DB::table('user_entrusts')
                                ->where('uid',$minPosition->uid)
                                ->where('status',1)
                                ->update(['status' => 3]);
                        }
                        DB::commit();
                    } catch (\Exception $e){
                        DB::rollBack();
                        Log::info($e->getLine().$e->getMessage());
                    }
                }

            }
        });
    }

}
