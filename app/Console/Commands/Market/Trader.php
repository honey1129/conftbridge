<?php

namespace App\Console\Commands\Market;

use App\Service\Hangqing;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class Trader extends Command
{
    protected $signature   = 'Market:trader';
    protected $description = '交易数据（定时请求）';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        date_default_timezone_set('PRC');
        $hb = new Hangqing();
        //查询自定义币行情
        $product = DB::table('products')
                     ->select('pid', 'image', 'pname', 'code', 'mark_cn', 'beishu', 'dianwei', 'basis', 'deal')
                     ->where('state', 1)
                     ->where('type', 2)
                     ->get()->toArray();
        foreach ($product as $key => $item) {
            $item  = (array)$item;
            $basis = 'adausdt';
            if (!empty($item['basis'])) {
                $basis = $item['basis'];
            }
            $trade = $hb->get_history_trade($basis, 60);
            $trade = json_decode(json_encode($trade), true);
            if ($trade['status'] !== 'ok') {
//                die('火币接口请求失败');
                continue;
            }
            $data     = [
                'code'      => $item['code'],
                'name'      => $item['pname'],
                'timestamp' => $trade['ts'],
                'type'      => 'trader',
            ];
            $datalist = [];
            foreach ($trade['data'] as $k => $v) {
                $datalist[$k]['dt'] = $v['data'][0]['ts'];
//                $datalist[$k]['dt']     = bcmul(time(), 1000);
                $datalist[$k]['dc']     = $v['data'][0]['direction'];
                $datalist[$k]['amount'] = bcmul($v['data'][0]['amount'], $item['deal'], 6);
                $datalist[$k]['price']  = sprintf("%.6f", $v['data'][0]['price'] * $item['beishu'] + $item['dianwei']);
            }
            $data['data'] = $datalist;
            //推送到内部频道
            $jsonData = json_encode($data);
            $status   = Redis::publish('inside:vb:channel:trader', $jsonData);
            //缓存交易数据
            Redis::setex('vb:trader:newitem:' . $data['code'], 30, $jsonData);
            dump($status);
        }
    }
}