<?php

namespace App\Console\Commands\Market;

use App\Service\Hangqing;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class Ticker extends Command
{
    protected $signature   = 'Market:ticker';
    protected $description = '行情列表数据刷新 //废弃改用socket';

    public function __construct()
    {

        parent::__construct();
    }

    public function handle()
    {
        die();
        //废弃
        date_default_timezone_set('PRC');
        //获取市场usdt_cny价格
        $cny_price = Redis::get('exchangeRate');
        if (empty($cny_price)) {
            $cny_price = DB::table('admin_config')->where(['name' => 'tibi.usdtcny'])->value('value');
        }
        $hb = new Hangqing();
        //查询自定义币行情
        $product = DB::table('products')
                     ->select('pid', 'image', 'pname', 'code', 'mark_cn', 'beishu', 'dianwei', 'basis')
                     ->where('state', 1)
                     ->where('type', 2)
                     ->get()->toArray();
        foreach ($product as $key => $item) {
            $item  = (array)$item;
            $basis = 'adausdt';
            if (!empty($item['basis'])) {
                $basis = $item['basis'];
            }
            var_dump($basis);
            $ticker = $hb->get_detail_merged($basis);
            $ticker = json_decode(json_encode($ticker), true);
            if ($ticker['status'] !== 'ok') {
                die('火币接口请求失败');
            }
            $tick                  = $ticker['tick'];
            $dataArr               = [
                'code'      => $item['code'],
                'name'      => $item['pname'],
                'date'      => date('Y-m-d'),
                'time'      => date('H:i:s'),
                'timestamp' => $ticker['ts'],
                'price'     => (float)sprintf("%.6f", $tick['close'] * $item['beishu'] + $item['dianwei']),
                'open'      => (float)sprintf("%.6f", $tick['open'] * $item['beishu'] + $item['dianwei']),
                'high'      => (float)sprintf("%.6f", $tick['high'] * $item['beishu'] + $item['dianwei']),
                'low'       => (float)sprintf("%.6f", $tick['low'] * $item['beishu'] + $item['dianwei']),
                'close'     => (float)sprintf("%.6f", $tick['close'] * $item['beishu'] + $item['dianwei']),
                'volume'    => $tick['amount'] * rand(10, 20) / 10,
            ];
            $dataArr['cnyPrice']   = $dataArr['price'] * $cny_price;
            $dataArr['change']     = (string)($dataArr['close'] - $dataArr['open']);
            $dataArr['changeRate'] = sprintf("%.2f", ($dataArr['close'] - $dataArr['open']) / $dataArr['open'] * 100);
            $dataArr['changeRate'] = $dataArr['changeRate'] . '%';
            $dataArr['type']       = 'ticker';
            //广播数据到行情订阅
            $status = Redis::publish('vb:channel:ticker', json_encode($dataArr));
//            var_dump(json_encode($dataArr));
            dump($status);
        }

    }
}



