<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\RealTime;
use App\Models\SecondInfo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Service\Hangqing;

class Newada extends Command
{
    protected $signature   = 'Newada';
    protected $description = '自定义币维护历史k线(计划任务)';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {

        $product = DB::table('products')
                     ->select('pid', 'image', 'pname', 'code', 'mark_cn', 'beishu', 'dianwei', 'basis','type')
                     ->where('state', 1)
//                     ->where('type', 2)
                     ->get()->toarray();
        foreach ($product as $key => $val) {
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
            $adausdt_1min  =
                $req->get_history_kline($basis, '1min', '1');                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     //修改了service内返回值json_encode第二个参数直接转化为数组
            $adausdt_5min  = $req->get_history_kline($basis, '5min', '1');
            $adausdt_15min = $req->get_history_kline($basis, '15min', '1');
            $adausdt_30min = $req->get_history_kline($basis, '30min', '1');
            $adausdt_60min = $req->get_history_kline($basis, '60min', '1');
            $adausdt_4hour = $req->get_history_kline($basis, '4hour', '1');
            $adausdt_week  = $req->get_history_kline($basis, '1week', '1');
            $adausdt_month = $req->get_history_kline($basis, '1mon', '1');
            $adausdt_1day  = $req->get_history_kline($basis, '1day', '1');
            var_dump($adausdt_1min['status']);
            if ($adausdt_1min['status'] !== 'ok' || $adausdt_5min['status'] !== 'ok' || $adausdt_15min['status'] !== 'ok' || $adausdt_30min['status'] !== 'ok' || $adausdt_60min['status'] !== 'ok' || $adausdt_1day['status'] !== 'ok') {
                continue;
                //            die('火币接口请求失败');
            }
            date_default_timezone_set('PRC');
            $volume = Redis::get('vb:ticker:newitem:' . $val['code']);
            $volume = json_decode($volume, true)['volume'] ?? 0;
            //1min
            $quotation  =
                reset($adausdt_1min['data']);                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       //弹出数据
            $model_data = DB::table('xy_1min_info')
                            ->where(['pid' => $val['pid']])->orderby('timestamp', 'desc')
                            ->first();
            if ($model_data) {
                $model_data = (array)$model_data;
                if ($model_data['timestamp'] === $quotation['id']) {
                    //第一遍计算倍数
                    $data                 = [];
                    $data['closingPrice'] = $quotation['close'] * $val['beishu'];
                    $data['highestPrice'] = $quotation['high'] * $val['beishu'];
                    $data['lowestPrice']  = $quotation['low'] * $val['beishu'];
//                    $data['volume']       = $quotation['vol'] *$val['deal'];
                    $data['volume']       = (float)$volume / 1440;
                    $data['closingPrice'] += $val['dianwei'];
                    $data['highestPrice'] += $val['dianwei'];
                    $data['lowestPrice']  += $val['dianwei'];
                    if ($model_data['openingPrice'] < $data['lowestPrice']) {
                        $data['lowestPrice'] = $model_data['openingPrice'];
                    }
                    if ($model_data['openingPrice'] > $data['highestPrice']) {
                        $data['highestPrice'] = $model_data['openingPrice'];
                    }
                    DB::table('xy_1min_info')->where('id', $model_data['id'])->update($data);
                } else if ($model_data['timestamp'] < $quotation['id']) {
                    //第一遍计算倍数
                    $data = [];
                    //基础数据
                    $data['pid']  = $val['pid'];
                    $data['code'] = $val['code'];
                    $data['name'] = $val['pname'];
//                    $data['volume']    = $quotation['vol'] *$val['deal'];//交易量
                    $data['volume']    = (float)$volume / 1440;
                    $data['date']      = date('Y-m-d', $quotation['id']);
                    $data['time']      = date('H:i:s', $quotation['id']);
                    $data['dateTime']  = date('Y-m-d H:i:s', $quotation['id']);
                    $data['timestamp'] = $quotation['id'];
                    //k线核心数据
                    //                    先计算倍数
                    $data['openingPrice'] = $model_data['closingPrice'];//开盘价等于上一根关盘价格
                    $data['closingPrice'] = $quotation['close'] * $val['beishu'];
                    $data['highestPrice'] = $quotation['high'] * $val['beishu'];
                    $data['lowestPrice']  = $quotation['low'] * $val['beishu'];
                    //后计算加减
                    $data['closingPrice'] += $val['dianwei'];
                    $data['highestPrice'] += $val['dianwei'];
                    $data['lowestPrice']  += $val['dianwei'];
                    if ($data['lowestPrice'] > $data['openingPrice']) {
                        $data['lowestPrice'] = $data['openingPrice'];
                    }
                    if ($data['highestPrice'] < $data['openingPrice']) {
                        $data['highestPrice'] = $data['openingPrice'];
                    }
                    DB::table('xy_1min_info')->insert($data);
                } else {
                    echo '出bug了'.__LINE__;
                }
            } else {
                $data                 = [];
                $data['pid']          = $val['pid'];
                $data['code']         = $val['code'];
                $data['name']         = $val['pname'];
                $data['openingPrice'] = $quotation['open'] * $val['beishu'] + $val['dianwei'];
                $data['closingPrice'] = $quotation['close'] * $val['beishu'] + $val['dianwei'];
                $data['highestPrice'] = $quotation['high'] * $val['beishu'] + $val['dianwei'];
                $data['lowestPrice']  = $quotation['low'] * $val['beishu'] + $val['dianwei'];
//                $data['volume']       = $quotation['vol'] *$val['deal'];
                $data['volume'] = (float)$volume / 1440;;
                $data['date']      = date('Y-m-d', $quotation['id']);
                $data['time']      = date('H:i:s', $quotation['id']);
                $data['dateTime']  = date('Y-m-d H:i:s', $quotation['id']);
                $data['timestamp'] = $quotation['id'];
                DB::table('xy_1min_info')->insert($data);
            }
            //5min
            $quotation  =
                reset($adausdt_5min['data']);                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       //弹出数据
            $model_data = DB::table('xy_5min_info')->where(['pid' => $val['pid']])->orderby('timestamp', 'desc')
                            ->first();
            if ($model_data) {
                $model_data = (array)$model_data;
                if ($model_data['timestamp'] === $quotation['id']) {
                    //第一遍计算倍数
                    $data                 = [];
                    $data['closingPrice'] = $quotation['close'] * $val['beishu'];
                    $data['highestPrice'] = $quotation['high'] * $val['beishu'];
                    $data['lowestPrice']  = $quotation['low'] * $val['beishu'];
//                    $data['volume']       = $quotation['vol'] *$val['deal'];
                    $data['volume']       = (float)$volume / 288;
                    $data['closingPrice'] += $val['dianwei'];
                    $data['highestPrice'] += $val['dianwei'];
                    $data['lowestPrice']  += $val['dianwei'];
                    if ($model_data['openingPrice'] < $data['lowestPrice']) {
                        $data['lowestPrice'] = $model_data['openingPrice'];
                    }
                    if ($model_data['openingPrice'] > $data['highestPrice']) {
                        $data['highestPrice'] = $model_data['openingPrice'];
                    }
                    DB::table('xy_5min_info')->where('id', $model_data['id'])->update($data);
                } else if ($model_data['timestamp'] < $quotation['id']) {
                    //第一遍计算倍数
                    $data = [];
                    //基础数据
                    $data['pid']  = $val['pid'];
                    $data['code'] = $val['code'];
                    $data['name'] = $val['pname'];
//                    $data['volume']    = $quotation['vol'] *$val['deal'];
                    $data['volume'] = (float)$volume / 288;;
                    $data['date']      = date('Y-m-d', $quotation['id']);
                    $data['time']      = date('H:i:s', $quotation['id']);
                    $data['dateTime']  = date('Y-m-d H:i:s', $quotation['id']);
                    $data['timestamp'] = $quotation['id'];
                    //k线核心数据
                    //                    先计算倍数
                    $data['openingPrice'] = $model_data['closingPrice'];//开盘价等于上一根关盘价格
                    $data['closingPrice'] = $quotation['close'] * $val['beishu'];
                    $data['highestPrice'] = $quotation['high'] * $val['beishu'];
                    $data['lowestPrice']  = $quotation['low'] * $val['beishu'];
                    //后计算加减
                    $data['closingPrice'] += $val['dianwei'];
                    $data['highestPrice'] += $val['dianwei'];
                    $data['lowestPrice']  += $val['dianwei'];
                    if ($data['lowestPrice'] > $data['openingPrice']) {
                        $data['lowestPrice'] = $data['openingPrice'];
                    }
                    if ($data['highestPrice'] < $data['openingPrice']) {
                        $data['highestPrice'] = $data['openingPrice'];
                    }
                    DB::table('xy_5min_info')->insert($data);
                } else {
                    echo '出bug了'.__LINE__;
                }
            } else {
                $data                 = [];
                $data['pid']          = $val['pid'];
                $data['code']         = $val['code'];
                $data['name']         = $val['pname'];
                $data['openingPrice'] = $quotation['open'] * $val['beishu'] + $val['dianwei'];
                $data['closingPrice'] = $quotation['close'] * $val['beishu'] + $val['dianwei'];
                $data['highestPrice'] = $quotation['high'] * $val['beishu'] + $val['dianwei'];
                $data['lowestPrice']  = $quotation['low'] * $val['beishu'] + $val['dianwei'];
//                $data['volume']       = $quotation['vol'] *$val['deal'];
                $data['volume']    = (float)$volume / 288;
                $data['date']      = date('Y-m-d', $quotation['id']);
                $data['time']      = date('H:i:s', $quotation['id']);
                $data['dateTime']  = date('Y-m-d H:i:s', $quotation['id']);
                $data['timestamp'] = $quotation['id'];
                DB::table('xy_5min_info')->insert($data);
            }
            //15min
            $quotation  =
                reset($adausdt_15min['data']);                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      //弹出数据
            $model_data = DB::table('xy_15min_info')->where(['pid' => $val['pid']])->orderby('timestamp', 'desc')
                            ->first();
            if ($model_data) {
                $model_data = (array)$model_data;
                if ($model_data['timestamp'] === $quotation['id']) {
                    //第一遍计算倍数
                    $data                 = [];
                    $data['closingPrice'] = $quotation['close'] * $val['beishu'];
                    $data['highestPrice'] = $quotation['high'] * $val['beishu'];
                    $data['lowestPrice']  = $quotation['low'] * $val['beishu'];
//                    $data['volume']       = $quotation['vol'] *$val['deal'];
                    $data['volume']       = (float)$volume / 96;
                    $data['closingPrice'] += $val['dianwei'];
                    $data['highestPrice'] += $val['dianwei'];
                    $data['lowestPrice']  += $val['dianwei'];
                    if ($model_data['openingPrice'] < $data['lowestPrice']) {
                        $data['lowestPrice'] = $model_data['openingPrice'];
                    }
                    if ($model_data['openingPrice'] > $data['highestPrice']) {
                        $data['highestPrice'] = $model_data['openingPrice'];
                    }
                    DB::table('xy_15min_info')->where('id', $model_data['id'])->update($data);
                } else if ($model_data['timestamp'] < $quotation['id']) {
                    //第一遍计算倍数
                    $data = [];
                    //基础数据
                    $data['pid']  = $val['pid'];
                    $data['code'] = $val['code'];
                    $data['name'] = $val['pname'];
//                    $data['volume']    = $quotation['vol'] *$val['deal'];
                    $data['volume']    = (float)$volume / 96;
                    $data['date']      = date('Y-m-d', $quotation['id']);
                    $data['time']      = date('H:i:s', $quotation['id']);
                    $data['dateTime']  = date('Y-m-d H:i:s', $quotation['id']);
                    $data['timestamp'] = $quotation['id'];
                    //k线核心数据
                    //                    先计算倍数
                    $data['openingPrice'] = $model_data['closingPrice'];//开盘价等于上一根关盘价格
                    $data['closingPrice'] = $quotation['close'] * $val['beishu'];
                    $data['highestPrice'] = $quotation['high'] * $val['beishu'];
                    $data['lowestPrice']  = $quotation['low'] * $val['beishu'];
                    //后计算加减
                    $data['closingPrice'] += $val['dianwei'];
                    $data['highestPrice'] += $val['dianwei'];
                    $data['lowestPrice']  += $val['dianwei'];
                    if ($data['lowestPrice'] > $data['openingPrice']) {
                        $data['lowestPrice'] = $data['openingPrice'];
                    }
                    if ($data['highestPrice'] < $data['openingPrice']) {
                        $data['highestPrice'] = $data['openingPrice'];
                    }
                    DB::table('xy_15min_info')->insert($data);
                } else {
                    echo '出bug了'.__LINE__;
                }
            } else {
                $data                 = [];
                $data['pid']          = $val['pid'];
                $data['code']         = $val['code'];
                $data['name']         = $val['pname'];
                $data['openingPrice'] = $quotation['open'] * $val['beishu'] + $val['dianwei'];
                $data['closingPrice'] = $quotation['close'] * $val['beishu'] + $val['dianwei'];
                $data['highestPrice'] = $quotation['high'] * $val['beishu'] + $val['dianwei'];
                $data['lowestPrice']  = $quotation['low'] * $val['beishu'] + $val['dianwei'];
//                $data['volume']       = $quotation['vol'] *$val['deal'];
                $data['volume']    = (float)$volume / 96;
                $data['date']      = date('Y-m-d', $quotation['id']);
                $data['time']      = date('H:i:s', $quotation['id']);
                $data['dateTime']  = date('Y-m-d H:i:s', $quotation['id']);
                $data['timestamp'] = $quotation['id'];
                DB::table('xy_15min_info')->insert($data);
            }
            //30min
            $quotation  =
                reset($adausdt_30min['data']);                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      //弹出数据
            $model_data = DB::table('xy_30min_info')->where(['pid' => $val['pid']])->orderby('timestamp', 'desc')
                            ->first();
            if ($model_data) {
                $model_data = (array)$model_data;
                if ($model_data['timestamp'] === $quotation['id']) {
                    //第一遍计算倍数
                    $data                 = [];
                    $data['closingPrice'] = $quotation['close'] * $val['beishu'];
                    $data['highestPrice'] = $quotation['high'] * $val['beishu'];
                    $data['lowestPrice']  = $quotation['low'] * $val['beishu'];
//                    $data['volume']       = $quotation['vol'] *$val['deal'];
                    $data['volume']       = (float)$volume / 48;
                    $data['closingPrice'] += $val['dianwei'];
                    $data['highestPrice'] += $val['dianwei'];
                    $data['lowestPrice']  += $val['dianwei'];
                    if ($model_data['openingPrice'] < $data['lowestPrice']) {
                        $data['lowestPrice'] = $model_data['openingPrice'];
                    }
                    if ($model_data['openingPrice'] > $data['highestPrice']) {
                        $data['highestPrice'] = $model_data['openingPrice'];
                    }
                    DB::table('xy_30min_info')->where('id', $model_data['id'])->update($data);
                } else if ($model_data['timestamp'] < $quotation['id']) {
                    //第一遍计算倍数
                    $data = [];
                    //基础数据
                    $data['pid']  = $val['pid'];
                    $data['code'] = $val['code'];
                    $data['name'] = $val['pname'];
//                    $data['volume']    = $quotation['vol'] * $val['deal'];
                    $data['volume']    = (float)$volume / 48;
                    $data['date']      = date('Y-m-d', $quotation['id']);
                    $data['time']      = date('H:i:s', $quotation['id']);
                    $data['dateTime']  = date('Y-m-d H:i:s', $quotation['id']);
                    $data['timestamp'] = $quotation['id'];
                    //k线核心数据
                    //                    先计算倍数
                    $data['openingPrice'] = $model_data['closingPrice'];//开盘价等于上一根关盘价格
                    $data['closingPrice'] = $quotation['close'] * $val['beishu'];
                    $data['highestPrice'] = $quotation['high'] * $val['beishu'];
                    $data['lowestPrice']  = $quotation['low'] * $val['beishu'];
                    //后计算加减
                    $data['closingPrice'] += $val['dianwei'];
                    $data['highestPrice'] += $val['dianwei'];
                    $data['lowestPrice']  += $val['dianwei'];
                    if ($data['lowestPrice'] > $data['openingPrice']) {
                        $data['lowestPrice'] = $data['openingPrice'];
                    }
                    if ($data['highestPrice'] < $data['openingPrice']) {
                        $data['highestPrice'] = $data['openingPrice'];
                    }
                    DB::table('xy_30min_info')->insert($data);
                } else {
                    echo '出bug了'.__LINE__;
                }
            } else {
                $data                 = [];
                $data['pid']          = $val['pid'];
                $data['code']         = $val['code'];
                $data['name']         = $val['pname'];
                $data['openingPrice'] = $quotation['open'] * $val['beishu'] + $val['dianwei'];
                $data['closingPrice'] = $quotation['close'] * $val['beishu'] + $val['dianwei'];
                $data['highestPrice'] = $quotation['high'] * $val['beishu'] + $val['dianwei'];
                $data['lowestPrice']  = $quotation['low'] * $val['beishu'] + $val['dianwei'];
//                $data['volume']       = $quotation['vol'] * $val['deal'];
                $data['volume']    = (float)$volume / 48;
                $data['date']      = date('Y-m-d', $quotation['id']);
                $data['time']      = date('H:i:s', $quotation['id']);
                $data['dateTime']  = date('Y-m-d H:i:s', $quotation['id']);
                $data['timestamp'] = $quotation['id'];
                DB::table('xy_30min_info')->insert($data);
            }
            //60min
            $quotation  =
                reset($adausdt_60min['data']);                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      //弹出数据
            $model_data = DB::table('xy_60min_info')->where(['pid' => $val['pid']])->orderby('timestamp', 'desc')
                            ->first();
            if ($model_data) {
                $model_data = (array)$model_data;
                if ($model_data['timestamp'] === $quotation['id']) {
                    //第一遍计算倍数
                    $data                 = [];
                    $data['closingPrice'] = $quotation['close'] * $val['beishu'];
                    $data['highestPrice'] = $quotation['high'] * $val['beishu'];
                    $data['lowestPrice']  = $quotation['low'] * $val['beishu'];
//                    $data['volume']       = $quotation['vol'] * $val['deal'];
                    $data['volume']       = (float)$volume / 24;
                    $data['closingPrice'] += $val['dianwei'];
                    $data['highestPrice'] += $val['dianwei'];
                    $data['lowestPrice']  += $val['dianwei'];
                    if ($model_data['openingPrice'] < $data['lowestPrice']) {
                        $data['lowestPrice'] = $model_data['openingPrice'];
                    }
                    if ($model_data['openingPrice'] > $data['highestPrice']) {
                        $data['highestPrice'] = $model_data['openingPrice'];
                    }
                    DB::table('xy_60min_info')->where('id', $model_data['id'])->update($data);
                } else if ($model_data['timestamp'] < $quotation['id']) {
                    //第一遍计算倍数
                    $data = [];
                    //基础数据
                    $data['pid']  = $val['pid'];
                    $data['code'] = $val['code'];
                    $data['name'] = $val['pname'];
//                    $data['volume']    = $quotation['vol'] * $val['deal'];
                    $data['volume']    = (float)$volume / 24;
                    $data['date']      = date('Y-m-d', $quotation['id']);
                    $data['time']      = date('H:i:s', $quotation['id']);
                    $data['dateTime']  = date('Y-m-d H:i:s', $quotation['id']);
                    $data['timestamp'] = $quotation['id'];
                    //k线核心数据
                    //                    先计算倍数
                    $data['openingPrice'] = $model_data['closingPrice'];//开盘价等于上一根关盘价格
                    $data['closingPrice'] = $quotation['close'] * $val['beishu'];
                    $data['highestPrice'] = $quotation['high'] * $val['beishu'];
                    $data['lowestPrice']  = $quotation['low'] * $val['beishu'];
                    //后计算加减
                    $data['closingPrice'] += $val['dianwei'];
                    $data['highestPrice'] += $val['dianwei'];
                    $data['lowestPrice']  += $val['dianwei'];
                    if ($data['lowestPrice'] > $data['openingPrice']) {
                        $data['lowestPrice'] = $data['openingPrice'];
                    }
                    if ($data['highestPrice'] < $data['openingPrice']) {
                        $data['highestPrice'] = $data['openingPrice'];
                    }
                    DB::table('xy_60min_info')->insert($data);
                } else {
                    echo '出bug了'.__LINE__;
                }
            } else {
                $data                 = [];
                $data['pid']          = $val['pid'];
                $data['code']         = $val['code'];
                $data['name']         = $val['pname'];
                $data['openingPrice'] = $quotation['open'] * $val['beishu'] + $val['dianwei'];
                $data['closingPrice'] = $quotation['close'] * $val['beishu'] + $val['dianwei'];
                $data['highestPrice'] = $quotation['high'] * $val['beishu'] + $val['dianwei'];
                $data['lowestPrice']  = $quotation['low'] * $val['beishu'] + $val['dianwei'];
//                $data['volume']       = $quotation['vol'] * $val['deal'];
                $data['volume']    = (float)$volume / 24;
                $data['date']      = date('Y-m-d', $quotation['id']);
                $data['time']      = date('H:i:s', $quotation['id']);
                $data['dateTime']  = date('Y-m-d H:i:s', $quotation['id']);
                $data['timestamp'] = $quotation['id'];
                DB::table('xy_60min_info')->insert($data);
            }
            //4hour
            $quotation  =
                reset($adausdt_4hour['data']);                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      //弹出数据
            $model_data = DB::table('xy_4hour_info')->where(['pid' => $val['pid']])->orderby('timestamp', 'desc')
                            ->first();
            if ($model_data) {
                $model_data = (array)$model_data;
                if ($model_data['timestamp'] === $quotation['id']) {
                    //第一遍计算倍数
                    $data                 = [];
                    $data['closingPrice'] = $quotation['close'] * $val['beishu'];
                    $data['highestPrice'] = $quotation['high'] * $val['beishu'];
                    $data['lowestPrice']  = $quotation['low'] * $val['beishu'];
//                    $data['volume']       = $quotation['vol'] * $val['deal'];
                    $data['volume']       = (float)$volume / 24;
                    $data['closingPrice'] += $val['dianwei'];
                    $data['highestPrice'] += $val['dianwei'];
                    $data['lowestPrice']  += $val['dianwei'];
                    if ($model_data['openingPrice'] < $data['lowestPrice']) {
                        $data['lowestPrice'] = $model_data['openingPrice'];
                    }
                    if ($model_data['openingPrice'] > $data['highestPrice']) {
                        $data['highestPrice'] = $model_data['openingPrice'];
                    }
                    DB::table('xy_4hour_info')->where('id', $model_data['id'])->update($data);
                } else if ($model_data['timestamp'] < $quotation['id']) {
                    //第一遍计算倍数
                    $data = [];
                    //基础数据
                    $data['pid']  = $val['pid'];
                    $data['code'] = $val['code'];
                    $data['name'] = $val['pname'];
//                    $data['volume']    = $quotation['vol'] * $val['deal'];
                    $data['volume']    = (float)$volume / 24;
                    $data['date']      = date('Y-m-d', $quotation['id']);
                    $data['time']      = date('H:i:s', $quotation['id']);
                    $data['dateTime']  = date('Y-m-d H:i:s', $quotation['id']);
                    $data['timestamp'] = $quotation['id'];
                    //k线核心数据
                    //                    先计算倍数
                    $data['openingPrice'] = $model_data['closingPrice'];//开盘价等于上一根关盘价格
                    $data['closingPrice'] = $quotation['close'] * $val['beishu'];
                    $data['highestPrice'] = $quotation['high'] * $val['beishu'];
                    $data['lowestPrice']  = $quotation['low'] * $val['beishu'];
                    //后计算加减
                    $data['closingPrice'] += $val['dianwei'];
                    $data['highestPrice'] += $val['dianwei'];
                    $data['lowestPrice']  += $val['dianwei'];
                    if ($data['lowestPrice'] > $data['openingPrice']) {
                        $data['lowestPrice'] = $data['openingPrice'];
                    }
                    if ($data['highestPrice'] < $data['openingPrice']) {
                        $data['highestPrice'] = $data['openingPrice'];
                    }
                    DB::table('xy_4hour_info')->insert($data);
                } else {
                    echo '出bug了'.__LINE__;
                }
            } else {
                $data                 = [];
                $data['pid']          = $val['pid'];
                $data['code']         = $val['code'];
                $data['name']         = $val['pname'];
                $data['openingPrice'] = $quotation['open'] * $val['beishu'] + $val['dianwei'];
                $data['closingPrice'] = $quotation['close'] * $val['beishu'] + $val['dianwei'];
                $data['highestPrice'] = $quotation['high'] * $val['beishu'] + $val['dianwei'];
                $data['lowestPrice']  = $quotation['low'] * $val['beishu'] + $val['dianwei'];
//                $data['volume']       = $quotation['vol'] * $val['deal'];
                $data['volume']    = (float)$volume / 24;
                $data['date']      = date('Y-m-d', $quotation['id']);
                $data['time']      = date('H:i:s', $quotation['id']);
                $data['dateTime']  = date('Y-m-d H:i:s', $quotation['id']);
                $data['timestamp'] = $quotation['id'];
                DB::table('xy_4hour_info')->insert($data);
            }
            //1day
            $quotation  =
                reset($adausdt_1day['data']);                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       //弹出数据
            $model_data = DB::table('xy_dayk_info')->where(['pid' => $val['pid']])->orderby('timestamp', 'desc')
                            ->first();
            if ($model_data) {
                $model_data = (array)$model_data;
                if ($model_data['timestamp'] === $quotation['id']) {
                    //第一遍计算倍数
                    $data                 = [];
                    $data['closingPrice'] = $quotation['close'] * $val['beishu'];
                    $data['highestPrice'] = $quotation['high'] * $val['beishu'];
                    $data['lowestPrice']  = $quotation['low'] * $val['beishu'];
//                    $data['volume']       = $quotation['vol'] * $val['deal'];
                    $data['volume']       = (float)$volume / 24;
                    $data['closingPrice'] += $val['dianwei'];
                    $data['highestPrice'] += $val['dianwei'];
                    $data['lowestPrice']  += $val['dianwei'];
                    if ($model_data['openingPrice'] < $data['lowestPrice']) {
                        $data['lowestPrice'] = $model_data['openingPrice'];
                    }
                    if ($model_data['openingPrice'] > $data['highestPrice']) {
                        $data['highestPrice'] = $model_data['openingPrice'];
                    }
                    $date = date('Y-m-d', $model_data['timestamp']);

                    //查看当前缓存里本币最小值
                    $cache_low = Redis::get('ProductLow' . $val['pid']);
                    if (!$cache_low) {
                        $cache_low = json_encode(['date' => $val['pid'], 'value' => $data['lowestPrice']]);
                        Redis::set('ProductLow' . $val['pid'], $cache_low);
                    } else {
                        $cache_low = json_decode($cache_low, true);
                        if ($cache_low['date'] == $date) {//判断缓存内时间是否为当前时间
                            if ($cache_low['value'] > $data['lowestPrice']) {//缓存内最小值大于当前值设置当前值到缓存
                                $cache_low = json_encode(['date' => $date, 'value' => $data['lowestPrice']]);
                                Redis::set('ProductLow' . $val['pid'], $cache_low);
                            } else {
                                $data['lowestPrice'] = $cache_low['value'];
                            }
                        } else {
                            $cache_low = json_encode(['date' => $date, 'value' => $data['lowestPrice']]);
                            Redis::set('ProductLow' . $val['pid'], $cache_low);
                        }
                    }

                    //查看当前缓存里本币最大值
                    $cache_high = Redis::get('ProductHigh' . $val['pid']);
                    if (!$cache_high) {//不存在向缓存放入最大值
                        $cache_high = json_encode(['date' => $date, 'value' => $data['highestPrice']]);
                        Redis::set('ProductHigh' . $val['pid'], $cache_high);
                    } else {
                        $cache_high = json_decode($cache_high, true);
                        if ($cache_high['date'] == $date) {//判断缓存内时间是否为当前时间
                            if ($cache_high['value'] < $data['highestPrice']) {//缓存内最大值小于当前值设置当前值到缓存
                                $cache_high = json_encode(['date' => $date, 'value' => $data['highestPrice']]);
                                Redis::set('ProductHigh' . $val['pid'], $cache_high);
                            } else {
                                $data['highestPrice'] = $cache_high['value'];
                            }
                        } else {
                            $cache_high = json_encode(['date' => $date, 'value' => $data['highestPrice']]);
                            Redis::set('ProductHigh' . $val['pid'], $cache_high);
                        }
                    }


                    DB::table('xy_dayk_info')->where('id', $model_data['id'])->update($data);
                } else if ($model_data['timestamp'] < $quotation['id']) {
                    //第一遍计算倍数
                    $data = [];
                    //基础数据
                    $data['pid']  = $val['pid'];
                    $data['code'] = $val['code'];
                    $data['name'] = $val['pname'];
//                    $data['volume']    = $quotation['vol'] * $val['deal'];
                    $data['volume']    = (float)$volume / 24;
                    $data['date']      = date('Y-m-d', $quotation['id']);
                    $data['time']      = date('H:i:s', $quotation['id']);
                    $data['dateTime']  = date('Y-m-d H:i:s', $quotation['id']);
                    $data['timestamp'] = $quotation['id'];
                    //k线核心数据
                    //                    先计算倍数
                    $data['openingPrice'] = $model_data['closingPrice'];//开盘价等于上一根关盘价格
                    $data['closingPrice'] = $quotation['close'] * $val['beishu'];
                    $data['highestPrice'] = $quotation['high'] * $val['beishu'];
                    $data['lowestPrice']  = $quotation['low'] * $val['beishu'];
                    //后计算加减
                    $data['closingPrice'] += $val['dianwei'];
                    $data['highestPrice'] += $val['dianwei'];
                    $data['lowestPrice']  += $val['dianwei'];
                    if ($data['lowestPrice'] > $data['openingPrice']) {
                        $data['lowestPrice'] = $data['openingPrice'];
                    }
                    if ($data['highestPrice'] < $data['openingPrice']) {
                        $data['highestPrice'] = $data['openingPrice'];
                    }
                    DB::table('xy_dayk_info')->insert($data);
                } else {
                    echo '出bug了'.__LINE__;
                }
            } else {
                $data                 = [];
                $data['pid']          = $val['pid'];
                $data['code']         = $val['code'];
                $data['name']         = $val['pname'];
                $data['openingPrice'] = $quotation['open'] * $val['beishu'] + $val['dianwei'];
                $data['closingPrice'] = $quotation['close'] * $val['beishu'] + $val['dianwei'];
                $data['highestPrice'] = $quotation['high'] * $val['beishu'] + $val['dianwei'];
                $data['lowestPrice']  = $quotation['low'] * $val['beishu'] + $val['dianwei'];
//                $data['volume']       = $quotation['vol'] * $val['deal'];
                $data['volume']    = (float)$volume / 24;
                $data['date']      = date('Y-m-d', $quotation['id']);
                $data['time']      = date('H:i:s', $quotation['id']);
                $data['dateTime']  = date('Y-m-d H:i:s', $quotation['id']);
                $data['timestamp'] = $quotation['id'];
                DB::table('xy_dayk_info')->insert($data);
            }
            //week
            $quotation  =
                reset($adausdt_week['data']);                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      //弹出数据
            $model_data = DB::table('xy_week_info')->where(['pid' => $val['pid']])->orderby('timestamp', 'desc')
                            ->first();
            if ($model_data) {
                $model_data = (array)$model_data;
                if ($model_data['timestamp'] === $quotation['id']) {
                    //第一遍计算倍数
                    $data                 = [];
                    $data['closingPrice'] = $quotation['close'] * $val['beishu'];
                    $data['highestPrice'] = $quotation['high'] * $val['beishu'];
                    $data['lowestPrice']  = $quotation['low'] * $val['beishu'];
//                    $data['volume']       = $quotation['vol'] * $val['deal'];
                    $data['volume']       = (float)$volume / 24;
                    $data['closingPrice'] += $val['dianwei'];
                    $data['highestPrice'] += $val['dianwei'];
                    $data['lowestPrice']  += $val['dianwei'];
                    if ($model_data['openingPrice'] < $data['lowestPrice']) {
                        $data['lowestPrice'] = $model_data['openingPrice'];
                    }
                    if ($model_data['openingPrice'] > $data['highestPrice']) {
                        $data['highestPrice'] = $model_data['openingPrice'];
                    }
                    DB::table('xy_week_info')->where('id', $model_data['id'])->update($data);
                } else if ($model_data['timestamp'] < $quotation['id']) {
                    //第一遍计算倍数
                    $data = [];
                    //基础数据
                    $data['pid']  = $val['pid'];
                    $data['code'] = $val['code'];
                    $data['name'] = $val['pname'];
//                    $data['volume']    = $quotation['vol'] * $val['deal'];
                    $data['volume']    = (float)$volume / 24;
                    $data['date']      = date('Y-m-d', $quotation['id']);
                    $data['time']      = date('H:i:s', $quotation['id']);
                    $data['dateTime']  = date('Y-m-d H:i:s', $quotation['id']);
                    $data['timestamp'] = $quotation['id'];
                    //k线核心数据
                    //                    先计算倍数
                    $data['openingPrice'] = $model_data['closingPrice'];//开盘价等于上一根关盘价格
                    $data['closingPrice'] = $quotation['close'] * $val['beishu'];
                    $data['highestPrice'] = $quotation['high'] * $val['beishu'];
                    $data['lowestPrice']  = $quotation['low'] * $val['beishu'];
                    //后计算加减
                    $data['closingPrice'] += $val['dianwei'];
                    $data['highestPrice'] += $val['dianwei'];
                    $data['lowestPrice']  += $val['dianwei'];
                    if ($data['lowestPrice'] > $data['openingPrice']) {
                        $data['lowestPrice'] = $data['openingPrice'];
                    }
                    if ($data['highestPrice'] < $data['openingPrice']) {
                        $data['highestPrice'] = $data['openingPrice'];
                    }
                    DB::table('xy_week_info')->insert($data);
                } else {
                    echo '出bug了'.__LINE__;
                }
            } else {
                $data                 = [];
                $data['pid']          = $val['pid'];
                $data['code']         = $val['code'];
                $data['name']         = $val['pname'];
                $data['openingPrice'] = $quotation['open'] * $val['beishu'] + $val['dianwei'];
                $data['closingPrice'] = $quotation['close'] * $val['beishu'] + $val['dianwei'];
                $data['highestPrice'] = $quotation['high'] * $val['beishu'] + $val['dianwei'];
                $data['lowestPrice']  = $quotation['low'] * $val['beishu'] + $val['dianwei'];
//                $data['volume']       = $quotation['vol'] * $val['deal'];
                $data['volume']    = (float)$volume / 24;
                $data['date']      = date('Y-m-d', $quotation['id']);
                $data['time']      = date('H:i:s', $quotation['id']);
                $data['dateTime']  = date('Y-m-d H:i:s', $quotation['id']);
                $data['timestamp'] = $quotation['id'];
                DB::table('xy_week_info')->insert($data);
            }
            //月
            $quotation  =
                reset($adausdt_month['data']);                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      //弹出数据
            $model_data = DB::table('xy_month_info')->where(['pid' => $val['pid']])->orderby('timestamp', 'desc')
                            ->first();
            if ($model_data) {
                $model_data = (array)$model_data;
                if ($model_data['timestamp'] === $quotation['id']) {
                    //第一遍计算倍数
                    $data                 = [];
                    $data['closingPrice'] = $quotation['close'] * $val['beishu'];
                    $data['highestPrice'] = $quotation['high'] * $val['beishu'];
                    $data['lowestPrice']  = $quotation['low'] * $val['beishu'];
//                    $data['volume']       = $quotation['vol'] * $val['deal'];
                    $data['volume']       = (float)$volume / 24;
                    $data['closingPrice'] += $val['dianwei'];
                    $data['highestPrice'] += $val['dianwei'];
                    $data['lowestPrice']  += $val['dianwei'];
                    if ($model_data['openingPrice'] < $data['lowestPrice']) {
                        $data['lowestPrice'] = $model_data['openingPrice'];
                    }
                    if ($model_data['openingPrice'] > $data['highestPrice']) {
                        $data['highestPrice'] = $model_data['openingPrice'];
                    }
                    DB::table('xy_month_info')->where('id', $model_data['id'])->update($data);
                } else if ($model_data['timestamp'] < $quotation['id']) {
                    //第一遍计算倍数
                    $data = [];
                    //基础数据
                    $data['pid']  = $val['pid'];
                    $data['code'] = $val['code'];
                    $data['name'] = $val['pname'];
//                    $data['volume']    = $quotation['vol'] * $val['deal'];
                    $data['volume']    = (float)$volume / 24;
                    $data['date']      = date('Y-m-d', $quotation['id']);
                    $data['time']      = date('H:i:s', $quotation['id']);
                    $data['dateTime']  = date('Y-m-d H:i:s', $quotation['id']);
                    $data['timestamp'] = $quotation['id'];
                    //k线核心数据
                    //                    先计算倍数
                    $data['openingPrice'] = $model_data['closingPrice'];//开盘价等于上一根关盘价格
                    $data['closingPrice'] = $quotation['close'] * $val['beishu'];
                    $data['highestPrice'] = $quotation['high'] * $val['beishu'];
                    $data['lowestPrice']  = $quotation['low'] * $val['beishu'];
                    //后计算加减
                    $data['closingPrice'] += $val['dianwei'];
                    $data['highestPrice'] += $val['dianwei'];
                    $data['lowestPrice']  += $val['dianwei'];
                    if ($data['lowestPrice'] > $data['openingPrice']) {
                        $data['lowestPrice'] = $data['openingPrice'];
                    }
                    if ($data['highestPrice'] < $data['openingPrice']) {
                        $data['highestPrice'] = $data['openingPrice'];
                    }
                    try {
                        DB::table('xy_month_info')->insert($data);
                    } catch (\Exception $e) {
                    }
                } else {
                    echo '出bug了'.__LINE__;
                }
            } else {
                $data                 = [];
                $data['pid']          = $val['pid'];
                $data['code']         = $val['code'];
                $data['name']         = $val['pname'];
                $data['openingPrice'] = $quotation['open'] * $val['beishu'] + $val['dianwei'];
                $data['closingPrice'] = $quotation['close'] * $val['beishu'] + $val['dianwei'];
                $data['highestPrice'] = $quotation['high'] * $val['beishu'] + $val['dianwei'];
                $data['lowestPrice']  = $quotation['low'] * $val['beishu'] + $val['dianwei'];
//                $data['volume']       = $quotation['vol'] * $val['deal'];
                $data['volume']    = (float)$volume / 24;
                $data['date']      = date('Y-m-d', $quotation['id']);
                $data['time']      = date('H:i:s', $quotation['id']);
                $data['dateTime']  = date('Y-m-d H:i:s', $quotation['id']);
                $data['timestamp'] = $quotation['id'];
                DB::table('xy_month_info')->insert($data);
            }


        }

    }
}
