<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class PositionsSubscribe extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'positions:subscribe';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '合约止盈止损（计划任务）';

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
            $this->closePositions();
//            $redis = Redis::connection('subscribe');
//            $redis->subscribe(['vb:ticker:chan:LV'], function ($message) {
//                // TODO 平仓逻辑 止盈 止损
//                $this->closePositions($message);
//            });
        } catch (\Exception $exception) {
            Log::info('RedisSubscribe Faild' . $exception->getMessage());
        }
    }

    /**
     * 平仓逻辑
     * @param $message
     * 平仓类型 1手动平仓  2止盈平仓  3止损平仓  4爆仓
     */
    public function closePositions()
    {
        //  $subscribe = json_decode($message, true);
        DB::table('user_positions')->where('stopwin', '>', 0)
          ->orWhere('stoploss', '>', 0)
          ->chunkById(100, function ($positions)
          {
              foreach ($positions as $position) {
                  $nowprice = Redis::get('vb:ticker:newprice:' . $position->code);

                  if (!$nowprice) {
                      break;
                  }

                  //平仓加减点差
//                    $spread = $pinfo->var_price * $pinfo->spread;

                  if ($position->otype == 1) { //1涨 2跌
                      // 买涨止盈   当前价格 >= 止盈价格
                      if ($position->stopwin > 0 && $nowprice >= $position->stopwin) {
                          $res =
                              $this->join_clp_queue(2, $position->stopwin, $position, '系统止盈强平仓', 'System take profit and strong liquidation');
                          if (!$res) {
                              continue;
                          }
                      }

                      // 买涨止损  当前价格 <= 止损价格
                      if ($position->stoploss > 0 && $nowprice <= $position->stoploss) {
                          $res =
                              $this->join_clp_queue(3, $position->stoploss, $position, '系统止损强平仓', 'System stop loss forced liquidation');
                          if (!$res) {
                              continue;
                          }
                      }
                  } else {
                      // 买跌止盈   当前价格 <= 止盈价格
                      if ($position->stopwin > 0 && $nowprice <= $position->stopwin) {
                          $res =
                              $this->join_clp_queue(2, $position->stopwin, $position, '系统止盈强平仓', 'System take profit and strong liquidation');
                          if (!$res) {
                              continue;
                          }
                      }

                      // 买跌止损  当前价格 >= 止损价格
                      if ($position->stoploss > 0 && $nowprice >= $position->stoploss) {
                          $res =
                              $this->join_clp_queue(3, $position->stoploss, $position, '系统止损强平仓', 'System stop loss forced liquidation');
                          if (!$res) {
                              continue;
                          }
                      }
                  }
              }
          });
    }


    /**
     * 加入平仓的队列
     * @param $redis
     * @param $pc_type  int 平仓类型 1手动平仓  2止盈平仓  3止损平仓  4爆仓
     * @param $nowprice string 平仓价格(最新价格)
     * @param $position array 持仓单信息
     * @param $member string 集合成员
     * @param $memo string 描述
     * @return bool
     */
    public function join_clp_queue($pc_type, $nowprice, $position, $memo, $en_memo)
    {
        $pinfo  = DB::table('products')
                    ->where('code', $position->code)
                    ->select('pid', 'pname', 'code', 'spread', 'var_price')
                    ->first();
        $spread = $pinfo->spread;
//
        if ($position->otype == 1) {
            $nowprice -= $spread;
        } else {
            $nowprice += $spread;
        }

        $queue_data['pc_type']  = $pc_type;
        $queue_data['price']    = $nowprice;
        $queue_data['position'] = $position;
        $queue_data['memo']     = $memo;
        $queue_data['en_memo']  = $en_memo;
        try {
            $server = Redis::connection('server');
            $tid    = $server->lpush('positions_process', json_encode($queue_data));

            if ($tid === false) {
                return false;
            }
        } catch (\Exception $exception) {
            return false;
            Log::info('join_clp_queue Faild' . $exception->getMessage());
        }

        return true;
    }
}
