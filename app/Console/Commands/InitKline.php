<?php

namespace App\Console\Commands;

use App\Service\Hangqing;
use App\Service\HttpService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Lin\Okex\OkexV5;

class InitKline extends Command
{
    protected $signature   = 'initKline';
    protected $description = '初始化历史线（初始化脚本）';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->okTask();
    }

    public function okTask()
    {
        //从ok获取ok币历史行情
        $product = DB::table('products')
                     ->where('pid', 36)
                     ->select('pid', 'image', 'pname', 'code', 'mark_cn', 'deal', 'type', 'beishu', 'dianwei', 'basis')
                     ->where('state', 1)
//                     ->where('type', 1)
                     ->get()->toarray();

        foreach ($product as $key => $val) {
            date_default_timezone_set('PRC');
            $val    = (array)$val;
            $instId = strtoupper(str_replace('_', '-', $val['code']));
            //一分钟
            $dataArr = $this->initData($instId, '1m', $val);
            DB::table('xy_1min_info')
              ->where(['pid' => $val['pid']])->delete();
            DB::table('xy_1min_info')->insert($dataArr);
            //五分钟
            var_dump('五分钟');
            $dataArr = $this->initData($instId, '5m', $val);
            DB::table('xy_5min_info')->where(['pid' => $val['pid']])->delete();
            DB::table('xy_5min_info')->insert($dataArr);
            var_dump('15分钟');
            $dataArr = $this->initData($instId, '15m', $val);
            DB::table('xy_15min_info')->where(['pid' => $val['pid']])->delete();
            DB::table('xy_15min_info')->insert($dataArr);
            var_dump('30分钟');
            $dataArr = $this->initData($instId, '30m', $val);
            DB::table('xy_30min_info')->where(['pid' => $val['pid']])->delete();
            DB::table('xy_30min_info')->insert($dataArr);
            var_dump('60分钟');
            $dataArr = $this->initData($instId, '1H', $val);
            DB::table('xy_60min_info')->where(['pid' => $val['pid']])->delete();
            DB::table('xy_60min_info')->insert($dataArr);
            var_dump('4小时');
            $dataArr = $this->initData($instId, '4H', $val);
            DB::table('xy_4hour_info')->where(['pid' => $val['pid']])->delete();
            DB::table('xy_4hour_info')->insert($dataArr);
            var_dump('周线');
            $dataArr = $this->initData($instId, '1W', $val);
            DB::table('xy_week_info')->where(['pid' => $val['pid']])->delete();
            DB::table('xy_week_info')->insert($dataArr);
            var_dump('月线');
            $dataArr = $this->initData($instId, '1M', $val);
            DB::table('xy_month_info')->where(['pid' => $val['pid']])->delete();
            DB::table('xy_month_info')->insert($dataArr);
            var_dump('日线');
            $dataArr = $this->initData($instId, '1D', $val);
            DB::table('xy_dayk_info')->where(['pid' => $val['pid']])->delete();
            DB::table('xy_dayk_info')->insert($dataArr);


        }
    }
    /**
     * @param $instId
     * @param $bar
     * @param $val
     * @param $loop int 循环次数,循环查询5次
     */
    public function initData($instId, $bar, $val, $dataArr = [])
    {
        sleep(1);
        $query  = http_build_query([
            'instId' => $instId,
            'bar'    => $bar
        ]);
        $url    = 'https://www.okx.com/api/v5/market/history-candles?' . $query;
        $result = HttpService::send_get($url);
        $result = json_decode($result, true);
        if ($result['code'] != 0) {
            echo 'OK接口请求失败';
            return false;
        }
        $history = 0;
        foreach ($result['data'] as $quotation) {
            //第一遍计算倍数
            $data = [];
            //基础数据
            $data['pid']       = $val['pid'];
            $data['code']      = $val['code'];
            $data['name']      = $val['pname'];
            $data['volume']    = $quotation[5] * $val['deal'];                 //交易量
            $dt                = substr($quotation[0], 0, 10);
            $data['date']      = date('Y-m-d', $dt);
            $data['time']      = date('H:i:s', $dt);
            $data['dateTime']  = date('Y-m-d H:i:s', $dt);
            $data['timestamp'] = $dt;
            //k线核心数据
            //                    先计算倍数
            $data['openingPrice'] = $history == 0 ? $quotation[1] * $val['beishu'] + $val['dianwei']
                : $history;                                                        //开盘价等于上一根关盘价格
            $data['closingPrice'] = $quotation[4] * $val['beishu'];
            $data['highestPrice'] = $quotation[2] * $val['beishu'];
            $data['lowestPrice']  = $quotation[3] * $val['beishu'];
            //后计算加减
            $data['closingPrice'] += $val['dianwei'];
            $data['highestPrice'] += $val['dianwei'];
            $data['lowestPrice']  += $val['dianwei'];
            $history              = $data['closingPrice'];
            if ($data['lowestPrice'] > $data['openingPrice']) {
                $data['lowestPrice'] = $data['openingPrice'];
            }
            if ($data['highestPrice'] < $data['openingPrice']) {
                $data['highestPrice'] = $data['openingPrice'];
            }
            $dataArr[] = $data;
        }
        return $dataArr;
    }


    public function oneTask()
    {
        //获取单个币行情
        $product = DB::table('products')
//                     ->where('pid', '=', 1)
                     ->select('pid', 'image', 'pname', 'code', 'mark_cn', 'deal', 'type', 'beishu', 'dianwei', 'basis')
                     ->where('state', 1)
//                     ->where('type', 1)
                     ->get()->toarray();
        foreach ($product as $key => $val) {
            sleep(2);
            $val = (array)$val;
            $req = new Hangqing();
            if ($val['type'] == 2) {
                $basis = 'adausdt';
                if (!empty($val['basis'])) {
                    $basis = $val['basis'];
                }
            } else {
                $basis = strtolower(str_replace('_', '', $val['code']));
            }
            var_dump($basis . '=' . $val['code']);
            var_dump($val['pid']);
            $adausdt_4hour = $req->get_history_kline($basis, '4hour', '500');
            $dataArr       = [];
            DB::table('xy_4hour_info')->where(['pid' => $val['pid']])->delete();
            $history = 0;
            foreach ($adausdt_4hour['data'] as $quotation) {
                //第一遍计算倍数
                $data = [];
                //基础数据
                $data['pid']      = $val['pid'];
                $data['code']     = $val['code'];
                $data['name']     = $val['pname'];
                $data['volume']   = $quotation['vol'] * $val['deal'];                 //交易量
                $data['date']     = date('Y-m-d', $quotation['id']);
                $data['time']     = date('H:i:s', $quotation['id']);
                $data['dateTime'] = date('Y-m-d H:i:s', $quotation['id']);
                var_dump(date('Y-m-d H:i:s', $quotation['id']));
                $data['timestamp'] = $quotation['id'];
                //k线核心数据
                //                    先计算倍数
                $data['openingPrice'] = $history == 0 ? $quotation['open'] * $val['beishu'] + $val['dianwei']
                    : $history;                                                        //开盘价等于上一根关盘价格
                $data['closingPrice'] = $quotation['close'] * $val['beishu'];
                $data['highestPrice'] = $quotation['high'] * $val['beishu'];
                $data['lowestPrice']  = $quotation['low'] * $val['beishu'];
                //后计算加减
                $data['closingPrice'] += $val['dianwei'];
                $data['highestPrice'] += $val['dianwei'];
                $data['lowestPrice']  += $val['dianwei'];
                $history              = $data['closingPrice'];
                if ($data['lowestPrice'] > $data['openingPrice']) {
                    $data['lowestPrice'] = $data['openingPrice'];
                }
                if ($data['highestPrice'] < $data['openingPrice']) {
                    $data['highestPrice'] = $data['openingPrice'];
                }
                $dataArr[] = $data;
            }
            DB::table('xy_4hour_info')->insert($dataArr);
        }
    }

    public function allTask()
    {
//        获取所有币行情
        $product = DB::table('products')
                     ->where('pid', '>', 19)
                     ->select('pid', 'image', 'pname', 'code', 'mark_cn', 'deal', 'type', 'beishu', 'dianwei', 'basis')
                     ->where('state', 1)
//                     ->where('type', 1)
                     ->get()->toarray();
        foreach ($product as $key => $val) {
            sleep(2);
            $val = (array)$val;
            $req = new Hangqing();
            if ($val['type'] == 2) {
                $basis = 'adausdt';
                if (!empty($val['basis'])) {
                    $basis = $val['basis'];
                }
            } else {
                $basis = strtolower(str_replace('_', '', $val['code']));
            }
            var_dump($basis . '=' . $val['code']);
            var_dump($val['pid']);
            $adausdt_1min  =
                $req->get_history_kline($basis, '1min', '500');                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     //修改了service内返回值json_encode第二个参数直接转化为数组
            $adausdt_5min  = $req->get_history_kline($basis, '5min', '500');
            $adausdt_15min = $req->get_history_kline($basis, '15min', '500');
            $adausdt_30min = $req->get_history_kline($basis, '30min', '500');
            $adausdt_60min = $req->get_history_kline($basis, '60min', '500');
            $adausdt_4hour = $req->get_history_kline($basis, '4hour', '500');
            $adausdt_week  = $req->get_history_kline($basis, '1week', '500');
            $adausdt_month = $req->get_history_kline($basis, '1mon', '500');
            $adausdt_1day  = $req->get_history_kline($basis, '1day', '500');
            var_dump($adausdt_1min['status']);
            if ($adausdt_1min['status'] !== 'ok' || $adausdt_5min['status'] !== 'ok' || $adausdt_15min['status'] !== 'ok' || $adausdt_30min['status'] !== 'ok' || $adausdt_60min['status'] !== 'ok' || $adausdt_1day['status'] !== 'ok') {
                continue;
            }
            date_default_timezone_set('PRC');
//            1分钟线
            $dataArr = [];
            $history = 0;
            foreach ($adausdt_1min['data'] as $quotation) {
                //第一遍计算倍数
                $data = [];
                //基础数据
                $data['pid']       = $val['pid'];
                $data['code']      = $val['code'];
                $data['name']      = $val['pname'];
                $data['volume']    = $quotation['vol'] * $val['deal'];                 //交易量
                $data['date']      = date('Y-m-d', $quotation['id']);
                $data['time']      = date('H:i:s', $quotation['id']);
                $data['dateTime']  = date('Y-m-d H:i:s', $quotation['id']);
                $data['timestamp'] = $quotation['id'];
                //k线核心数据
                //                    先计算倍数
                $data['openingPrice'] = $history == 0 ? $quotation['open'] * $val['beishu'] + $val['dianwei']
                    : $history;                                                        //开盘价等于上一根关盘价格
                $data['closingPrice'] = $quotation['close'] * $val['beishu'];
                $data['highestPrice'] = $quotation['high'] * $val['beishu'];
                $data['lowestPrice']  = $quotation['low'] * $val['beishu'];
                //后计算加减
                $data['closingPrice'] += $val['dianwei'];
                $data['highestPrice'] += $val['dianwei'];
                $data['lowestPrice']  += $val['dianwei'];
                $history              = $data['closingPrice'];
                if ($data['lowestPrice'] > $data['openingPrice']) {
                    $data['lowestPrice'] = $data['openingPrice'];
                }
                if ($data['highestPrice'] < $data['openingPrice']) {
                    $data['highestPrice'] = $data['openingPrice'];
                }
                $dataArr[] = $data;
            }
            DB::table('xy_1min_info')
              ->where(['pid' => $val['pid']])->delete();
            DB::table('xy_1min_info')->insert($dataArr);
            var_dump('5分钟');
//            5分钟
            $dataArr = [];
            $history = 0;
            foreach ($adausdt_15min['data'] as $quotation) {
                //第一遍计算倍数
                $data = [];
                //基础数据
                $data['pid']       = $val['pid'];
                $data['code']      = $val['code'];
                $data['name']      = $val['pname'];
                $data['volume']    = $quotation['vol'] * $val['deal'];                 //交易量
                $data['date']      = date('Y-m-d', $quotation['id']);
                $data['time']      = date('H:i:s', $quotation['id']);
                $data['dateTime']  = date('Y-m-d H:i:s', $quotation['id']);
                $data['timestamp'] = $quotation['id'];
                //k线核心数据
                //                    先计算倍数
                $data['openingPrice'] = $history == 0 ? $quotation['open'] * $val['beishu'] + $val['dianwei']
                    : $history;                                                        //开盘价等于上一根关盘价格
                $data['closingPrice'] = $quotation['close'] * $val['beishu'];
                $data['highestPrice'] = $quotation['high'] * $val['beishu'];
                $data['lowestPrice']  = $quotation['low'] * $val['beishu'];
                //后计算加减
                $data['closingPrice'] += $val['dianwei'];
                $data['highestPrice'] += $val['dianwei'];
                $data['lowestPrice']  += $val['dianwei'];
                $history              = $data['closingPrice'];
                if ($data['lowestPrice'] > $data['openingPrice']) {
                    $data['lowestPrice'] = $data['openingPrice'];
                }
                if ($data['highestPrice'] < $data['openingPrice']) {
                    $data['highestPrice'] = $data['openingPrice'];
                }
                $dataArr[] = $data;
            }
            DB::table('xy_5min_info')->where(['pid' => $val['pid']])->delete();
            DB::table('xy_5min_info')->insert($dataArr);
//              15min
            var_dump('15min');
            $dataArr = [];
            $history = 0;
            foreach ($adausdt_5min['data'] as $quotation) {
                //第一遍计算倍数
                $data = [];
                //基础数据
                $data['pid']       = $val['pid'];
                $data['code']      = $val['code'];
                $data['name']      = $val['pname'];
                $data['volume']    = $quotation['vol'] * $val['deal'];                 //交易量
                $data['date']      = date('Y-m-d', $quotation['id']);
                $data['time']      = date('H:i:s', $quotation['id']);
                $data['dateTime']  = date('Y-m-d H:i:s', $quotation['id']);
                $data['timestamp'] = $quotation['id'];
                //k线核心数据
                //                    先计算倍数
                $data['openingPrice'] = $history == 0 ? $quotation['open'] * $val['beishu'] + $val['dianwei']
                    : $history;                                                        //开盘价等于上一根关盘价格
                $data['closingPrice'] = $quotation['close'] * $val['beishu'];
                $data['highestPrice'] = $quotation['high'] * $val['beishu'];
                $data['lowestPrice']  = $quotation['low'] * $val['beishu'];
                //后计算加减
                $data['closingPrice'] += $val['dianwei'];
                $data['highestPrice'] += $val['dianwei'];
                $data['lowestPrice']  += $val['dianwei'];
                $history              = $data['closingPrice'];
                if ($data['lowestPrice'] > $data['openingPrice']) {
                    $data['lowestPrice'] = $data['openingPrice'];
                }
                if ($data['highestPrice'] < $data['openingPrice']) {
                    $data['highestPrice'] = $data['openingPrice'];
                }
                $dataArr[] = $data;
            }
            DB::table('xy_15min_info')->where(['pid' => $val['pid']])->delete();
            DB::table('xy_15min_info')->insert($dataArr);

            var_dump('30min');
//              30min
            $dataArr = [];
            $history = 0;
            foreach ($adausdt_30min['data'] as $quotation) {
                //第一遍计算倍数
                $data = [];
                //基础数据
                $data['pid']       = $val['pid'];
                $data['code']      = $val['code'];
                $data['name']      = $val['pname'];
                $data['volume']    = $quotation['vol'] * $val['deal'];                 //交易量
                $data['date']      = date('Y-m-d', $quotation['id']);
                $data['time']      = date('H:i:s', $quotation['id']);
                $data['dateTime']  = date('Y-m-d H:i:s', $quotation['id']);
                $data['timestamp'] = $quotation['id'];
                //k线核心数据
                //                    先计算倍数
                $data['openingPrice'] = $history == 0 ? $quotation['open'] * $val['beishu'] + $val['dianwei']
                    : $history;                                                        //开盘价等于上一根关盘价格
                $data['closingPrice'] = $quotation['close'] * $val['beishu'];
                $data['highestPrice'] = $quotation['high'] * $val['beishu'];
                $data['lowestPrice']  = $quotation['low'] * $val['beishu'];
                //后计算加减
                $data['closingPrice'] += $val['dianwei'];
                $data['highestPrice'] += $val['dianwei'];
                $data['lowestPrice']  += $val['dianwei'];
                $history              = $data['closingPrice'];
                if ($data['lowestPrice'] > $data['openingPrice']) {
                    $data['lowestPrice'] = $data['openingPrice'];
                }
                if ($data['highestPrice'] < $data['openingPrice']) {
                    $data['highestPrice'] = $data['openingPrice'];
                }
                $dataArr[] = $data;
            }
            DB::table('xy_30min_info')->where(['pid' => $val['pid']])->delete();
            DB::table('xy_30min_info')->insert($dataArr);

            $dataArr = [];
            $history = 0;
            foreach ($adausdt_60min['data'] as $quotation) {
                //第一遍计算倍数
                $data = [];
                //基础数据
                $data['pid']       = $val['pid'];
                $data['code']      = $val['code'];
                $data['name']      = $val['pname'];
                $data['volume']    = $quotation['vol'] * $val['deal'];                 //交易量
                $data['date']      = date('Y-m-d', $quotation['id']);
                $data['time']      = date('H:i:s', $quotation['id']);
                $data['dateTime']  = date('Y-m-d H:i:s', $quotation['id']);
                $data['timestamp'] = $quotation['id'];
                //k线核心数据
                //                    先计算倍数
                $data['openingPrice'] = $history == 0 ? $quotation['open'] * $val['beishu'] + $val['dianwei']
                    : $history;                                                        //开盘价等于上一根关盘价格
                $data['closingPrice'] = $quotation['close'] * $val['beishu'];
                $data['highestPrice'] = $quotation['high'] * $val['beishu'];
                $data['lowestPrice']  = $quotation['low'] * $val['beishu'];
                //后计算加减
                $data['closingPrice'] += $val['dianwei'];
                $data['highestPrice'] += $val['dianwei'];
                $data['lowestPrice']  += $val['dianwei'];
                $history              = $data['closingPrice'];
                if ($data['lowestPrice'] > $data['openingPrice']) {
                    $data['lowestPrice'] = $data['openingPrice'];
                }
                if ($data['highestPrice'] < $data['openingPrice']) {
                    $data['highestPrice'] = $data['openingPrice'];
                }
                $dataArr[] = $data;
            }
            DB::table('xy_60min_info')->where(['pid' => $val['pid']])->delete();
            DB::table('xy_60min_info')->insert($dataArr);


            $dataArr = [];
            $history = 0;
            foreach ($adausdt_4hour['data'] as $quotation) {
                //第一遍计算倍数
                $data = [];
                //基础数据
                $data['pid']       = $val['pid'];
                $data['code']      = $val['code'];
                $data['name']      = $val['pname'];
                $data['volume']    = $quotation['vol'] * $val['deal'];                 //交易量
                $data['date']      = date('Y-m-d', $quotation['id']);
                $data['time']      = date('H:i:s', $quotation['id']);
                $data['dateTime']  = date('Y-m-d H:i:s', $quotation['id']);
                $data['timestamp'] = $quotation['id'];
                //k线核心数据
                //                    先计算倍数
                $data['openingPrice'] = $history == 0 ? $quotation['open'] * $val['beishu'] + $val['dianwei']
                    : $history;                                                        //开盘价等于上一根关盘价格
                $data['closingPrice'] = $quotation['close'] * $val['beishu'];
                $data['highestPrice'] = $quotation['high'] * $val['beishu'];
                $data['lowestPrice']  = $quotation['low'] * $val['beishu'];
                //后计算加减
                $data['closingPrice'] += $val['dianwei'];
                $data['highestPrice'] += $val['dianwei'];
                $data['lowestPrice']  += $val['dianwei'];
                $history              = $data['closingPrice'];
                if ($data['lowestPrice'] > $data['openingPrice']) {
                    $data['lowestPrice'] = $data['openingPrice'];
                }
                if ($data['highestPrice'] < $data['openingPrice']) {
                    $data['highestPrice'] = $data['openingPrice'];
                }
                $dataArr[] = $data;
            }
            DB::table('xy_4hour_info')->where(['pid' => $val['pid']])->delete();
            DB::table('xy_4hour_info')->insert($dataArr);
            var_dump('月线');
            $dataArr = [];
            $history = 0;
            foreach ($adausdt_week['data'] as $quotation) {
                //第一遍计算倍数
                $data = [];
                //基础数据
                $data['pid']       = $val['pid'];
                $data['code']      = $val['code'];
                $data['name']      = $val['pname'];
                $data['volume']    = $quotation['vol'] * $val['deal'];                 //交易量
                $data['date']      = date('Y-m-d', $quotation['id']);
                $data['time']      = date('H:i:s', $quotation['id']);
                $data['dateTime']  = date('Y-m-d H:i:s', $quotation['id']);
                $data['timestamp'] = $quotation['id'];
                //k线核心数据
                //                    先计算倍数
                $data['openingPrice'] = $history == 0 ? $quotation['open'] * $val['beishu'] + $val['dianwei']
                    : $history;                                                        //开盘价等于上一根关盘价格
                $data['closingPrice'] = $quotation['close'] * $val['beishu'];
                $data['highestPrice'] = $quotation['high'] * $val['beishu'];
                $data['lowestPrice']  = $quotation['low'] * $val['beishu'];
                //后计算加减
                $data['closingPrice'] += $val['dianwei'];
                $data['highestPrice'] += $val['dianwei'];
                $data['lowestPrice']  += $val['dianwei'];
                $history              = $data['closingPrice'];
                if ($data['lowestPrice'] > $data['openingPrice']) {
                    $data['lowestPrice'] = $data['openingPrice'];
                }
                if ($data['highestPrice'] < $data['openingPrice']) {
                    $data['highestPrice'] = $data['openingPrice'];
                }
                $dataArr[] = $data;
            }
            DB::table('xy_week_info')->where(['pid' => $val['pid']])->delete();
            DB::table('xy_week_info')->insert($dataArr);

            $dataArr = [];
            $history = 0;
            foreach ($adausdt_month['data'] as $quotation) {
                //第一遍计算倍数
                $data = [];
                //基础数据
                $data['pid']       = $val['pid'];
                $data['code']      = $val['code'];
                $data['name']      = $val['pname'];
                $data['volume']    = $quotation['vol'] * $val['deal'];                 //交易量
                $data['date']      = date('Y-m-d', $quotation['id']);
                $data['time']      = date('H:i:s', $quotation['id']);
                $data['dateTime']  = date('Y-m-d H:i:s', $quotation['id']);
                $data['timestamp'] = $quotation['id'];
                //k线核心数据
                //                    先计算倍数
                $data['openingPrice'] = $history == 0 ? $quotation['open'] * $val['beishu'] + $val['dianwei']
                    : $history;                                                        //开盘价等于上一根关盘价格
                $data['closingPrice'] = $quotation['close'] * $val['beishu'];
                $data['highestPrice'] = $quotation['high'] * $val['beishu'];
                $data['lowestPrice']  = $quotation['low'] * $val['beishu'];
                //后计算加减
                $data['closingPrice'] += $val['dianwei'];
                $data['highestPrice'] += $val['dianwei'];
                $data['lowestPrice']  += $val['dianwei'];
                $history              = $data['closingPrice'];
                if ($data['lowestPrice'] > $data['openingPrice']) {
                    $data['lowestPrice'] = $data['openingPrice'];
                }
                if ($data['highestPrice'] < $data['openingPrice']) {
                    $data['highestPrice'] = $data['openingPrice'];
                }
                $dataArr[] = $data;
            }
            DB::table('xy_month_info')->where(['pid' => $val['pid']])->delete();
            DB::table('xy_month_info')->insert($dataArr);

            $dataArr = [];
            $history = 0;
            foreach ($adausdt_1day['data'] as $quotation) {
                //第一遍计算倍数
                $data = [];
                //基础数据
                $data['pid']       = $val['pid'];
                $data['code']      = $val['code'];
                $data['name']      = $val['pname'];
                $data['volume']    = $quotation['vol'] * $val['deal'];                 //交易量
                $data['date']      = date('Y-m-d', $quotation['id']);
                $data['time']      = date('H:i:s', $quotation['id']);
                $data['dateTime']  = date('Y-m-d H:i:s', $quotation['id']);
                $data['timestamp'] = $quotation['id'];
                //k线核心数据
                //                    先计算倍数
                $data['openingPrice'] = $history == 0 ? $quotation['open'] * $val['beishu'] + $val['dianwei']
                    : $history;                                                        //开盘价等于上一根关盘价格
                $data['closingPrice'] = $quotation['close'] * $val['beishu'];
                $data['highestPrice'] = $quotation['high'] * $val['beishu'];
                $data['lowestPrice']  = $quotation['low'] * $val['beishu'];
                //后计算加减
                $data['closingPrice'] += $val['dianwei'];
                $data['highestPrice'] += $val['dianwei'];
                $data['lowestPrice']  += $val['dianwei'];
                $history              = $data['closingPrice'];
                if ($data['lowestPrice'] > $data['openingPrice']) {
                    $data['lowestPrice'] = $data['openingPrice'];
                }
                if ($data['highestPrice'] < $data['openingPrice']) {
                    $data['highestPrice'] = $data['openingPrice'];
                }
                $dataArr[] = $data;
            }
            DB::table('xy_dayk_info')->where(['pid' => $val['pid']])->delete();
            DB::table('xy_dayk_info')->insert($dataArr);

        }
    }
}
