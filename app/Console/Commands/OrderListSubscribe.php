<?php

namespace App\Console\Commands;

use App\Http\Traits\WriteUserMoneyLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\UserAssets;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class OrderListSubscribe extends Command
{
    use WriteUserMoneyLog;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order_list:subscribe';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '币币交易成交(计划任务)';

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

            $redis        = Redis::connection('subscribe');
            $product_list = Product::select('pid', 'code')->where('state', 1)->get()->toArray();
            if ($product_list) {
                foreach ($product_list as $item => $value) {
                    $code      = $this->coin_cut($value['code']);
                    $sell_code = $code[0];
                    $buy_code  = $code[1];
                    $sell_pid  = $this->get_asset_pid($sell_code);
                    $buy_pid   = $this->get_asset_pid($buy_code);

                    $newprice = $redis->get('vb:ticker:newprice:' . $value['code']);
                    if (!$newprice) {
                        continue;
                    }
                    //买入
                    $order_list1 = Order::select('orders_id', 'member_id', 'currency_id', 'wtprice', 'wtnum')
                                        ->where('currency_id', $value['pid'])
                                        ->where('otype', 1)
                                        ->where('type', 1)
                                        ->where('status', 1)
                                        ->where('is_first', 1)
                                        ->where('wtprice', '>=', floatval($newprice))
                                        ->get()->toArray();
                    if ($order_list1) {
                        foreach ($order_list1 as $key => $val) {
                            //计算购买个数
                            $money = number_format(($val['wtprice'] * $val['wtnum']) / $newprice, 6, '.', '');
                            $asset = UserAssets::getBalance($val['member_id'], $sell_pid, 2);
                            $this->writeBalanceLog($asset, $val['orders_id'], $val['wtnum'], 27, '币币交易', 'Currency transaction', $buy_pid, $buy_code, 2);
                            Order::where('orders_id', $val['orders_id'])
                                 ->update([
                                     'status' => 2, 'trade_time' => time(), 'cjprice' => $newprice,
                                     'cjnum'  => $money, 'totalprice' => $newprice * $val['wtnum']
                                 ]);
                        }

                    }
                    //卖出
                    $order_list2 = Order::select('orders_id', 'member_id', 'wtprice', 'wtnum')
                                        ->where([
                                            'currency_id' => $value['pid'], 'otype' => 1, 'type' => 2, 'status' => 1,
                                            'is_first'    => 1
                                        ])
                                        ->where('wtprice', '<=', floatval($newprice))->get()->toArray();
                    if ($order_list2) {
                        foreach ($order_list2 as $k => $v) {
                            //计算卖出金额
                            $money = number_format($newprice * $v['wtnum'], 6, '.', '');
//                            $asset = UserAssets::getBalance($v['member_id'],$sell_pid,2);
//                            $this->writeBalanceLog($asset,$v['orders_id'],-$v['wtnum'],26,'币币交易','Currency transaction',$sell_pid,$sell_code,2);
//
                            $assets = UserAssets::getBalance($v['member_id'], $buy_pid, 2);
                            $this->writeBalanceLog($assets, $v['orders_id'], $money, 27, '币币交易', 'Currency transaction', $sell_pid, $sell_code, 2);
                            Order::where('orders_id', $v['orders_id'])
                                 ->update([
                                     'status' => 2, 'trade_time' => time(), 'cjprice' => $newprice,
                                     'cjnum'  => DB::raw('wtnum'), 'totalprice' => $money
                                 ]);
                        }
                    }
                }
            }
        } catch (\Exception $exception) {
            var_dump($exception->getMessage());
            \Log::info('RedisSubscribe trade Faild' . $exception->getMessage());
        }
    }

    public function coin_cut($str)
    {
        $arr = explode('_', $str);
        return $arr;
    }

    public function get_asset_pid($code)
    {
        //查询币种
        $info = DB::table('wallet_code')->where('code', strtoupper($code))->first();
        return $info ? $info->pid : false;
    }
}
