<?php

namespace App\Console\Commands\Market;


use App\Models\XyMonthInfo;
use App\Models\XyWeekInfo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use function Co\run;

class Month extends Command
{
    protected $signature   = 'Market:month';
    protected $description = '月数据整合（定时请求）';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {

        date_default_timezone_set('PRC');
        //查询自定义币行情
        $product          = DB::table('products')
                              ->select('pid', 'image', 'pname', 'code', 'mark_cn', 'beishu', 'dianwei', 'basis')
                              ->where('state', 1)
//                              ->where('type', 2)
                              ->get();
        $month_start_time = date('Y-m-1', time());
        foreach ($product as $key => $item) {
            $code   = $item->code;
            $name   = $item->name;
            $open   = DB::table('xy_dayk_info')
                        ->where('date', '>', $month_start_time)
                        ->where('code', $code)
                        ->orderBy('timestamp', 'asc')
                        ->value('openingPrice');
            $close  = DB::table('xy_dayk_info')
                        ->where('date', '>', $month_start_time)
                        ->where('code', $code)
                        ->orderBy('timestamp', 'desc')
                        ->value('closingPrice');
            $high   = DB::table('xy_dayk_info')
                        ->where('date', '>', $month_start_time)
                        ->where('code', $code)
                        ->max('highestPrice');
            $low    = DB::table('xy_dayk_info')
                        ->where('date', '>', $month_start_time)
                        ->where('code', $code)
                        ->min('lowestPrice');
            $volume = DB::table('xy_dayk_info')
                        ->where('date', '>', $month_start_time)
                        ->where('code', $code)
                        ->sum('volume');
            $pid    = $item->pid;

            $time_stamp = strtotime($month_start_time);
            $ttime      = date('H:i:00', $time_stamp);
            $datetime   = date('Y-m-d 00:00:00', $time_stamp);
            $dataArr    = [
                'type'      => 'month',
                'code'      => $code,
                'datetime'  => $month_start_time,
                'timestamp' => $time_stamp,
                'open'      => $open,
                'close'     => $close,
                'high'      => $high,
                'low'       => $low,
                'volume'    => $volume,
            ];
            //存入最新周线
            Redis::set('vb:newklinemon:' . $code, json_encode($dataArr));
            $model     = XyMonthInfo::where([
                'pid'       => $pid,
                'dateTime'  => $datetime,
                'timestamp' => $time_stamp,
            ])->first();
            $modelData = [
                'pid'          => $pid,
                'code'         => $code,
                'name'         => $name,
                'openingPrice' => $open,
                'highestPrice' => $high,
                'closingPrice' => $close,
                'lowestPrice'  => $low,
                'volume'       => $volume,
                'date'         => $month_start_time,
                'time'         => $ttime,
                'dateTime'     => $datetime,
                'timestamp'    => $time_stamp,
            ];
            try {
                if (empty($model)) {
                    XyMonthInfo::insert($modelData);
                } else {
                    XyMonthInfo::where('id', $model->id)->update($modelData);
                }
            } catch (\Exception $e) {
                var_dump($e->getMessage());
            } catch (\Throwable $e) {
                var_dump($e->getMessage());
            }

//价差
            $change     = bcsub($close, $open, 4);
            $changeRate = bcmul(bcdiv($change, $open, 8), 100, 2) . '%';
            //初始化数据
            $dataArr = [
                'type'       => 'minute',
                'code'       => $code,
                'name'       => $name,
                'datetime'   => $datetime,
                'timestamp'  => (int)$time_stamp,
                'open'       => (float)$open,
                'close'      => (float)$close,
                'high'       => (float)$high,
                'low'        => (float)$low,
                'cnyPrice'   => 0,
                'changeRate' => $changeRate,
                'volume'     => (float)$volume
            ];
            run(function () use ($dataArr)
            {
                $redis = new \Swoole\Coroutine\Redis();
                $redis->connect('127.0.0.1', 6379);
                $result = $redis->publish('vb:channel:newkline:month', json_encode($dataArr));
                var_dump($result);
            });
        }
    }
}