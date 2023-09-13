<?php

namespace App\Console\Commands\Market\Ok;

use App\Service\Hangqing;
use App\Service\HttpService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class Depth extends Command
{
    protected $signature   = 'Market:OkDepth';
    protected $description = '委托列表刷新(定时请求)OK接口';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        date_default_timezone_set('PRC');
        //查询自定义币行情
        $product = DB::table('products')
                     ->select('pid', 'image', 'pname', 'code', 'mark_cn', 'beishu', 'dianwei', 'basis', 'deal')
                     ->where('state', 1)
                     ->where('pid', 36)
                     ->get()->toArray();
        foreach ($product as $key => $item) {
            $item   = (array)$item;
            $instId = strtoupper(str_replace('_', '-', $item['code']));
            $query  = http_build_query([
                'instId' => $instId,
                'sz'     => 30
            ]);
            $url    = 'https://www.okx.com/api/v5/market/books?' . $query;
            $depth  = HttpService::send_get($url);
            $depth  = json_decode($depth, true);
            if ($depth['code'] != 0) {
                echo 'OK接口请求失败';
                return false;
            }
            //买入卖出
            $asks = array();
            $bids = array();
            if ($depth['data'][0]['asks']) {
                foreach ($depth['data'][0]['asks'] as $key1 => $val1) {
                    $asks[$key1]['totalSize'] = sprintf("%.2f", $val1[1] * $item['deal']);
                    $asks[$key1]['price']     = sprintf("%.6f", $val1[0] * $item['beishu'] + $item['dianwei']);
                }
                $asks = array_reverse($asks);
            }
            if ($depth['data'][0]['bids']) {
//                $depth['data'][0]['bids'] = array_reverse($depth['tick']['bids']);
                foreach ($depth['data'][0]['bids'] as $key2 => $val2) {
                    $bids[$key2]['totalSize'] = sprintf("%.2f", $val2[1] * $item['deal']);
                    $bids[$key2]['price']     = sprintf("%.6f", $val2[0] * $item['beishu'] + $item['dianwei']);
                }
            }
            $data   = [
                'code'      => $item['code'],
                'name'      => $item['pname'],
                'timestamp' => $depth['data'][0]['ts'],
                'asks'      => $asks,
                'bids'      => $bids,
                'type'      => 'depth'
            ];
            $status = Redis::publish('vb:channel:depth', json_encode($data));
            Redis::set('vb:depth:newitem:' . $item['code'], json_encode($data));
            var_dump($status);
//            //深度图
            $asks = array();
            $bids = array();
            if ($depth['data'][0]['asks']) {
                $asks_sum = 0;

                foreach ($depth['data'][0]['asks'] as $key1 => $val1) {
                    $asks_sum                 = sprintf("%.2f", $val1[1] * $item['deal'] + $asks_sum);
                    $asks[$key1]['totalSize'] = $asks_sum;
                    $asks[$key1]['price']     = sprintf("%.6f", $val1[0] * $item['beishu'] + $item['dianwei']);
                }
            }
            if ($depth['data'][0]['bids']) {
//                $depth['tick']['bids'] = array_reverse($depth['tick']['bids']);
                $bids_sum = 0;
                foreach ($depth['data'][0]['bids'] as $key2 => $val2) {
                    $bids_sum                 = sprintf("%.2f", $val2[1] * $item['deal'] + $bids_sum);
                    $bids[$key2]['totalSize'] = $bids_sum;
                    $bids[$key2]['price']     = sprintf("%.6f", $val2[0] * $item['beishu'] + $item['dianwei']);
                }
                $bids = array_reverse($bids);
            }
            $data         = [
                'code'      => $item['code'],
                'name'      => $item['pname'],
                'timestamp' => $depth['data'][0]['ts'],
                //                'timestamp' => time(),
                'asks'      => $asks,
                'bids'      => $bids,
                'type'      => 'depth'
            ];
            $data['tyoe'] = 'depth_pct';
            $status       = Redis::publish('vb:channel:depth:pct', json_encode($data));
            Redis::set('vb:depth:pct:newitem:' . $item['code'], json_encode($data));
            var_dump($data);
            var_dump($status);
        }

    }
}