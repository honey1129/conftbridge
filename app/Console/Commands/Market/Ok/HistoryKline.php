<?php

namespace App\Console\Commands\Market\Ok;

use App\Service\HttpService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class HistoryKline extends Command
{

    protected $signature   = 'Market:OkHistoryKline';
    protected $description = '维护OK历史线';

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
        //从ok获取历史行情
        $product = DB::table('products')
                     ->where('pid', 36)
                     ->select('pid', 'image', 'pname', 'code', 'mark_cn', 'deal', 'type', 'beishu', 'dianwei', 'basis')
                     ->where('state', 1)
                     ->get()->toarray();
        foreach ($product as $key => $val) {
            date_default_timezone_set('PRC');
            $val    = (array)$val;
            $instId = strtoupper(str_replace('_', '-', $val['code']));
            //一分钟
            $this->initData($instId, '1m', $val, 'xy_1min_info');
            //五分钟
            var_dump('五分钟');
            $this->initData($instId, '5m', $val, 'xy_5min_info');
            var_dump('15分钟');
            $this->initData($instId, '15m', $val, 'xy_15min_info');
            var_dump('30分钟');
            $this->initData($instId, '30m', $val, 'xy_30min_info');
            var_dump('60分钟');
            $this->initData($instId, '1H', $val, 'xy_60min_info');
            var_dump('4小时');
            $this->initData($instId, '4H', $val, 'xy_4hour_info');
            var_dump('周线');
            $this->initData($instId, '1W', $val, 'xy_week_info');
            var_dump('月线');
            $this->initData($instId, '1M', $val, 'xy_month_info');
            var_dump('日线');
            $this->initData($instId, '1D', $val, 'xy_dayk_info');
        }
    }

    /**
     * @param $instId
     * @param $bar
     * @param $val
     */
    public function initData($instId, $bar, $val, $table)
    {
        sleep(1);
        $query  = http_build_query([
            'instId' => $instId,
            'bar'    => $bar,
            'limit'  => 1
        ]);
        $url    = 'https://www.okx.com/api/v5/market/candles?' . $query;
        $result = HttpService::send_get($url);
        $result = json_decode($result, true);
        if ($result['code'] != 0) {
            echo 'OK接口请求失败';
            return false;
        }
        if (empty($result['data'][0])) {
            echo 'OK接口请求失败';
            return false;
        }
        $quotation = $result['data'][0];
        $data      = [];
        //基础数据
        $data['pid']       = $val['pid'];
        $data['code']      = $val['code'];
        $data['name']      = $val['pname'];
        $data['volume']    =
            $quotation[5] * $val['deal'];                                                           //交易量
        $dt                = substr($quotation[0], 0, 10);
        $data['date']      = date('Y-m-d', $dt);
        $data['time']      = date('H:i:s', $dt);
        $data['dateTime']  = date('Y-m-d H:i:s', $dt);
        $data['timestamp'] = $dt;
        //k线核心数据
        //                    先计算倍数
        $data['openingPrice'] =
            $quotation[1] * $val['beishu'] + $val['dianwei'];                                                        //开盘价等于上一根关盘价格
        $data['closingPrice'] = $quotation[4] * $val['beishu'];
        $data['highestPrice'] = $quotation[2] * $val['beishu'];
        $data['lowestPrice']  = $quotation[3] * $val['beishu'];
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
        $model_data = DB::table($table)
                        ->where(['pid' => $val['pid']])
                        ->orderby('timestamp', 'desc')
                        ->first();
        if ($model_data->timestamp >= $data['timestamp']) {
            $data['openingPrice'] = $model_data->openingPrice;
            return DB::table($table)->where('id', $model_data->id)->update($data);
        } else {
            $data['openingPrice'] = $model_data->closingPrice;
            return DB::table($table)->insert($data);
        }
    }

}