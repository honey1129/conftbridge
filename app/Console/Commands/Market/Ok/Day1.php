<?php

namespace App\Console\Commands\Market\Ok;

use App\Service\Hangqing;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Lin\Okex\OkexV5;

class Day1 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Market:OkDay1';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '接口获取日线OK接口';


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
        date_default_timezone_set('PRC');

        try {
            //查询自定义币行情
            $product = DB::table('products')
                         ->where('pid', 36)
                         ->select('pid', 'image', 'pname', 'code', 'mark_cn', 'beishu', 'dianwei', 'basis')
                         ->where('state', 1)
//                         ->where('type', 2)
                         ->get()->toArray();
            //进程处理
            $time = time();
            //定时器防重复器
            Redis::set('APIMarketOkDay1', $time);
            \Swoole\Timer::tick(1000, function (int $timer_id) use ($product, $time)
            {
                $task_time_id = Redis::get('APIMarketOkDay1');
                if ($task_time_id != $time) {
                    //新任务进来删除本任务
                    dump('原TimeId:' . $time . '，新TimeId:' . $task_time_id);
                    \Swoole\Timer::clear($timer_id);
                }
                $okex = new OkexV5();
                foreach ($product as $key => $item) {
                    $item   = (array)$item;
                    $instId = strtoupper(str_replace('_', '-', $item['code']));
                    $result = $okex->market()->getTicker([
                        'instId' => $instId,
                        'bar'    => '1D',
                        'limit'  => 1
                    ]);
                    if ($result['code'] != 0) {
                        echo 'OK接口请求失败';
                        continue;
                    }
                    $ticker = $result['data'][0];//弹出数据
                    go(function () use ($item, $ticker)
                    {
                        $this->analysis_pub($item, $ticker);
                    });
                }


            });

        } catch (\Exception $exception) {
            var_dump($exception->getMessage());
            \Log::info('RedisSubscribe bnb_usdt Faild' . $exception->getMessage());
        }


    }

    public function analysis_pub($item, $data)
    {

        if (isset($data)) {
            //获取依附币种名字
            //查询依附本币列表
            $name             = strtoupper($item['pname']);
            $code             = strtolower($item['code']);
            $timestamp        = $data['ts'];
            $dt               = substr($data['ts'], 0, 10);
            $date             = date('Y-m-d', $dt);
            $times            = date('H:i:s', $dt);
            $tickers_datetime = date('Y-m-d H:i:s', strtotime($date));
            $time_stamp       = strtotime($tickers_datetime);
            //单独获取开盘价格
            $open = $this->getDayInfo($item['pid'], strtotime($date)) ?? 0;
            //上涨倍数
            $beishu = Redis::get('ProductBeishu' . $item['pid']);
            if (!$beishu) {
                Redis::set('ProductBeishu' . $item['pid'], $item['beishu']);
                $beishu = $item['beishu'];
            }
            //上涨点位
            $dianwei = Redis::get('ProductDianwei' . $item['pid']);
            if (!$dianwei) {
                Redis::set('ProductDianwei' . $item['pid'], $item['dianwei']);
                $dianwei = $item['dianwei'];
            }
            $price_rand = bcdiv(rand(0, 5), 10000, 6);
            //先乘除后加减
            $price = bcadd(bcmul($data['last'], $beishu, 6), $dianwei, 6);
            //干扰万分之一的价格保持线动
            $price = bcmul($price, bcadd($price_rand, 1, 6), 6);
            $close = $price;
            //最低价格
            $low = bcadd(bcmul($data['low24h'], $beishu, 6), $dianwei, 6);
            if ($low > $close) {
                $low = $close;
            }

            //查看当前缓存里本币最小值
            $cache_low = Redis::get('ProductLow' . $item['pid']);
            if (!$cache_low) {
                $cache_low = json_encode(['date' => $date, 'value' => $low]);
                Redis::set('ProductLow' . $item['pid'], $cache_low);
            } else {
                $cache_low = json_decode($cache_low, true);
                if ($cache_low['date'] == $date) {//判断缓存内时间是否为当前时间
                    if ($cache_low['value'] > $low) {//缓存内最小值大于当前值设置当前值到缓存
                        $cache_low = json_encode(['date' => $date, 'value' => $low]);
                        Redis::set('ProductLow' . $item['pid'], $cache_low);
                    } else {
                        $low = $cache_low['value'];
                    }
                } else {
                    $cache_low = json_encode(['date' => $date, 'value' => $low]);
                    Redis::set('ProductLow' . $item['pid'], $cache_low);
                }
            }
            //最大值
            $high = bcadd(bcmul($data['high24h'], $beishu, 6), $dianwei, 6);
            if ($high < $close) {
                $high = $close;
            }
            //查看当前缓存里本币最大值
            $cache_high = Redis::get('ProductHigh' . $item['pid']);
            if (!$cache_high) {
                $cache_high = json_encode(['date' => $date, 'value' => $high]);
                Redis::set('ProductHigh' . $item['pid'], $cache_high);
            } else {
                $cache_high = json_decode($cache_high, true);
                if ($cache_high['date'] == $date) {//判断缓存内时间是否为当前时间
                    if ($cache_high['value'] < $high) {//缓存内最大值小于当前值设置当前值到缓存
                        $cache_high = json_encode(['date' => $date, 'value' => $high]);
                        Redis::set('ProductHigh' . $item['pid'], $cache_high);
                    } else {
                        $high = $cache_high['value'];
                    }
                } else {
                    $cache_high = json_encode(['date' => $date, 'value' => $high]);
                    Redis::set('ProductHigh' . $item['pid'], $cache_high);
                }
            }
            //交易量
            $deal = Redis::get('ProductDeal' . $item['pid']);
            if (!$deal) {
                Redis::set('ProductDeal' . $item['pid'], $item['deal']);
                $deal = $item['deal'] ?? 1;
            }
            //交易量单独计算
            $volume = bcmul($data['vol24h'], $deal, 6);
            //获取人民币对U价格
            $exRate = floor(Redis::get('exchangeRate') ?? 7);
            // 人民币价格
            $cnyP = bcmul($close, $exRate, 6);
            //价差
            $change = bcsub($close, $open, 4);
            //价格浮动比例
            $changeRate = bcmul(bcdiv($change, $open, 8), 100, 2) . '%';
            //vb:channel:ticker数据
            $dataArr = [
                "code"       => $code,
                "name"       => $name,
                "date"       => $date,
                "time"       => $times,
                "timestamp"  => $timestamp,
                "price"      => $close,//价格等于关盘价
                "cnyPrice"   => $cnyP,
                "open"       => $open,
                "high"       => $high,
                "low"        => $low,
                "close"      => $close,
                "volume"     => $volume,
                "change"     => $change,
                "changeRate" => $changeRate,
            ];
            var_dump($dataArr);
            //当前行情数据
            Redis::publish('vb:channel:ticker', json_encode($dataArr));
            $dayklineitem = [
                'type'       => 'day',
                'code'       => $code,
                'datetime'   => $tickers_datetime,
                'timestamp'  => $time_stamp,
                'open'       => $open,
                'close'      => $close,
                'high'       => $high,
                'low'        => $low,
                'cnyPrice'   => $cnyP,
                "changeRate" => $changeRate,
                'volume'     => $volume,
            ];
            //日线数据
            Redis::publish('vb:channel:newkline:day', json_encode($dayklineitem));
            //维护币种价格
            Redis::set('vb:ticker:newprice:' . $code, $price);
            //维护币种当前info
            Redis::set('vb:ticker:newitem:' . $code, json_encode($dataArr));

        }

    }

    //返回日线开盘价格
    //历史k线脚本在维护开盘价格
    public function getDayInfo($pid, $id)
    {

        $cache_key  = 'api:xy_dayk_info_open:' . $pid;
        $model_data = Redis::get($cache_key);
        if ($model_data) {
            $model_data = json_decode($model_data);
        } else {
            $model_data = DB::table('xy_dayk_info')->where(['pid' => $pid])->orderby('timestamp', 'desc')
                            ->first();
            Redis::setex($cache_key, 180, json_encode($model_data));
        }
        if ($model_data->timestamp === $id) {
            //相等返回开盘价格
            return $model_data->openingPrice;
        } else {
            //不相等返回关盘价格
            return $model_data->closingPrice;
        }

    }

}