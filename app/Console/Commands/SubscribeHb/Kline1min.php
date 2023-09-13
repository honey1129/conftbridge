<?php

namespace App\Console\Commands\SubscribeHb;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Swoole\Coroutine\Http\Client;
use Swoole\Process;
use function Co\run;
use function Swoole\Coroutine\go;

class Kline1min extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'SubscribeHb:kline1min';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ws订阅1分钟数据（脚本）';


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->task();
    }

    public function task()
    {
        date_default_timezone_set('PRC');
        //            //查询自己币列表
        $products = DB::table('products')
                      ->where('state', 1)
//                      ->where('type', 2)
                      ->select(['pid', 'pname', 'code', 'basis', 'beishu', 'dianwei', 'deal', 'type'])
                      ->get();
        $coin_map = [];
        //初始化数据
        foreach ($products as $k => $v) {
            $coin_map[strtolower($v->code)]
                = [
                'openprice'      => null,
                'closeprice'     => null,
                'highprice'      => null,
                'lowprice'       => null,
                'volume'         => null,
                'old_time_index' => null
            ];

            $coin_info[strtolower($v->code)] = [
                'pid'  => $v->pid,
                'type' => $v->type
            ];
        }
        Redis::subscribe(['vb:channel:ticker', 'Task:tickerNew'], function ($msg, $chan) use (&$coin_map, $coin_info)
        {
            if ($chan == 'Task:tickerNew') {
                //todo 新增代币重启脚本来订阅新币的线
                var_dump('restart');
                $this->task();
                exit();
            }
            $item = json_decode($msg, true);
            if (!empty($item) && is_array($item)) {
//                var_dump($item['code']);
                //获取推送过来的code
                if (isset($coin_map[$item['code']])) {
                    $timestamp      = substr($item['timestamp'], 0, 10);            //时间戳
                    $datetime       = date('Y-m-d H:i:00', $timestamp);             //年月日十分秒
                    $timestamp1     = strtotime($datetime);                         //年月日十分秒
                    $new_time_index = date('i', $timestamp);                        //当前分钟
                    $code           = $item['code'];
                    $name           = $item['name'];
                    if ($coin_map[$item['code']]['old_time_index'] == null) {
                        //初始化数据不推送
                        $coin_map[$item['code']]['openprice']  = $item['close'];
                        $coin_map[$item['code']]['closeprice'] = $item['close'];
                        $coin_map[$item['code']]['highprice']  = $item['close'];
                        $coin_map[$item['code']]['lowprice']   = $item['close'];
                        $coin_map[$item['code']]['volume']     = $item['volume'];
                    } else {
                        if ($coin_map[$item['code']]['old_time_index'] != $new_time_index) {
                            //存储上一根线
                            //只存储主币线，平台币脚本单独维护
                            if ($coin_info[$item['code']]['type'] == 1) {
                                //设置上一根时间
                                $timestamp_db = $timestamp - 60;
                                try {
                                    DB::table('xy_1min_info')
                                      ->insert([
                                          'pid'          => $coin_info[$item['code']]['pid'],
                                          'code'         => $code,
                                          'name'         => $name,
                                          'openingPrice' => $coin_map[$item['code']]['openprice'],
                                          'highestPrice' => $coin_map[$item['code']]['highprice'],
                                          'closingPrice' => $coin_map[$item['code']]['closeprice'],
                                          'lowestPrice'  => $coin_map[$item['code']]['lowprice'],
                                          'volume'       => $coin_map[$item['code']]['volume'],
                                          //上一根线的时间
                                          'date'         => date('Y-m-d', $timestamp_db),
                                          'time'         => date('H:i:00', $timestamp_db),
                                          'dateTime'     => date('Y-m-d H:i:00', $timestamp_db),
                                          'timestamp'    => $timestamp_db,
                                      ]);
                                } catch (\Throwable $e) {
                                    echo 'insert model err';
                                }
                            }
                            //当前时间不为当前k线为上一根k线,可以存数据库也可以忽略
                            //开盘价格等于上一根关盘价格
                            $coin_map[$item['code']]['openprice'] = $coin_map[$item['code']]['closeprice'];
                            //最新价格等于最新推送过来的
                            $coin_map[$item['code']]['closeprice'] = $item['close'];
                            //如果推送过来的价格大于开盘价格
                            $coin_map[$item['code']]['highprice'] =
                                $item['close'] > $coin_map[$item['code']]['openprice'] ? $item['close']
                                    : $coin_map[$item['code']]['openprice'];
                            $coin_map[$item['code']]['lowprice']  =
                                $item['close'] > $coin_map[$item['code']]['openprice'] ?
                                    $coin_map[$item['code']]['openprice'] : $item['close'];
                            $coin_map[$item['code']]['volume']    = $item['volume'];
                        } else {
                            //本阶段如果当前收盘价格大于存储最高价格，最高价格为收盘价格
                            if ($item['close'] > $coin_map[$item['code']]['highprice']) {
                                $coin_map[$item['code']]['highprice'] = $item['close'];
                            }
                            //本阶段如果当前收盘价格小于存储最低价格，最低价格为收盘价格
                            if ($item['close'] < $coin_map[$item['code']]['lowprice']) {
                                $coin_map[$item['code']]['lowprice'] = $item['close'];
                            }
                            //收盘价格登录推送过来收盘价格
                            $coin_map[$item['code']]['closeprice'] = $item['close'];
                            //最新交易量小于历史交易量最新交易量设置为历史最高
                            $volume = bcsub($item['volume'], $coin_map[$item['code']]['volume'], 6);
                            if ($volume < 0) {
                                $volume = $item['volume'];
                            }
                            $volume = $item['volume'];
                            //初始化数据
                            $dataArr = [
                                'type'       => 'minute',
                                'code'       => $code,
                                'name'       => $name,
                                'datetime'   => $datetime,
                                'timestamp'  => (int)$timestamp1,
                                'open'       => (float)$coin_map[$item['code']]['openprice'],
                                'close'      => (float)$coin_map[$item['code']]['closeprice'],
                                'high'       => (float)$coin_map[$item['code']]['highprice'],
                                'low'        => (float)$coin_map[$item['code']]['lowprice'],
                                'cnyPrice'   => (float)$item['cnyPrice'],
                                'changeRate' => $item['changeRate'],
                                'volume'     => (float)$volume
                            ];
////                //重新调起客户端
                            run(function () use ($dataArr)
                            {
                                $redis = new \Swoole\Coroutine\Redis();
                                $redis->connect('127.0.0.1', 6379);
                                $result = $redis->publish('vb:channel:newkline:minute1', json_encode($dataArr));
                            });
//
                        }
                    }
                    $coin_map[$item['code']]['old_time_index'] = $new_time_index;

                }

//                    $status = Redis::publish('vb:channel:trader', json_encode($data));
            }
        });

    }

}