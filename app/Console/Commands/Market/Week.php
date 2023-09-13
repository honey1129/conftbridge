<?php

namespace App\Console\Commands\Market;


use App\Models\XyWeekInfo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use function Co\run;

class Week extends Command
{
    protected $signature   = 'Market:week';
    protected $description = '周线数据整合';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {

        date_default_timezone_set('PRC');
        //查询自定义币行情
        $product         = DB::table('products')
                             ->select('pid', 'image', 'pname', 'code', 'mark_cn', 'beishu', 'dianwei', 'basis')
                             ->where('state', 1)
//                             ->where('type', 2)
                             ->get();
        $week_start_time = now()->startOfWeek();

        foreach ($product as $key => $item) {
            $code       = $item->code;
            $name       = $item->name;
            $open       = DB::table('xy_dayk_info')
                            ->where('date', '>', $week_start_time)
                            ->where('code', $code)
                            ->orderBy('timestamp', 'asc')
                            ->value('openingPrice');
            $close      = DB::table('xy_dayk_info')
                            ->where('date', '>', $week_start_time)
                            ->where('code', $code)
                            ->orderBy('timestamp', 'desc')
                            ->value('closingPrice');
            $high       = DB::table('xy_dayk_info')
                            ->where('date', '>', $week_start_time)
                            ->where('code', $code)
                            ->max('highestPrice');
            $low        = DB::table('xy_dayk_info')
                            ->where('date', '>', $week_start_time)
                            ->where('code', $code)
                            ->min('lowestPrice');
            $volume     = DB::table('xy_dayk_info')
                            ->where('date', '>', $week_start_time)
                            ->where('code', $code)
                            ->sum('volume');
            $pid        = $item->pid;
            $time_stamp = strtotime($week_start_time);
            $ttime      = date('H:i:00', $time_stamp);
            $datetime   = date('Y-m-d 00:00:00', $time_stamp);

            $dataArr = [
                'type'      => 'week',
                'code'      => $code,
                'datetime'  => $week_start_time,
                'timestamp' => $time_stamp,
                'open'      => $open,
                'close'     => $close,
                'high'      => $high,
                'low'       => $low,
                'volume'    => $volume,
            ];
            //存入最新周线
            Redis::set('vb:newklineweek:' . $code, json_encode($dataArr));
            $model     = XyWeekInfo::where([
                'pid'       => $pid,
                'code'      => $code,
                'name'      => $name,
                'date'      => $week_start_time,
                'time'      => $ttime,
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
                'date'         => $week_start_time,
                'time'         => $ttime,
                'dateTime'     => $datetime,
                'timestamp'    => $time_stamp,
            ];
            try {
                if (empty($model)) {
                    XyWeekInfo::insert($modelData);
                } else {
                    XyWeekInfo::where('id', $model->id)->update($modelData);
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
                $result = $redis->publish('vb:channel:newkline:week', json_encode($dataArr));
            });

        }
    }
}