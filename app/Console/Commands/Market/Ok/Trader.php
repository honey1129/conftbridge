<?php

namespace App\Console\Commands\Market\Ok;

use App\Service\Hangqing;
use App\Service\HttpService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class Trader extends Command
{
    protected $signature   = 'Market:OkTrader';
    protected $description = '交易数据（定时请求）';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        date_default_timezone_set('PRC');
        //查询自定义币行情
        $product = DB::table('products')
                     ->where('pid', 36)
                     ->select('pid', 'image', 'pname', 'code', 'mark_cn', 'beishu', 'dianwei', 'basis', 'deal')
                     ->where('state', 1)
                     ->get()->toArray();
        foreach ($product as $key => $item) {
            $item   = (array)$item;
            $instId = strtoupper(str_replace('_', '-', $item['code']));
            $query  = http_build_query([
                'instId' => $instId,
                'limit'  => 60
            ]);
            $url    = 'https://www.okx.com/api/v5/market/trades?' . $query;
            $trade  = HttpService::send_get($url);
            $trade = json_decode($trade, true);
            if ($trade['code'] != 0) {
                echo 'OK接口请求失败';
                return false;
            }
            $data     = [
                'code'      => $item['code'],
                'name'      => $item['pname'],
                'timestamp' => intval(microtime(true) * 1000),
                'type'      => 'trader',
            ];
            $datalist = [];
            foreach ($trade['data'] as $k => $v) {
                //毫秒时间戳
                $datalist[$k]['dt'] = $v['ts'];
                //buy sell 买还是卖
                $datalist[$k]['dc']     = $v['side'];
                $datalist[$k]['amount'] = bcmul($v['sz'], $item['deal'], 6);
                $datalist[$k]['price']  = sprintf("%.6f", $v['px'] * $item['beishu'] + $item['dianwei']);
            }
            $data['data'] = $datalist;
            //推送到内部频道
            $jsonData = json_encode($data);
            dump($jsonData);
            $status   = Redis::publish('inside:vb:channel:trader', $jsonData);
            //缓存交易数据
            Redis::setex('vb:trader:newitem:' . $data['code'], 30, $jsonData);
            dump($status);
        }
    }
}