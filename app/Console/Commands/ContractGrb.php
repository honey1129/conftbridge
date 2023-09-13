<?php

namespace App\Console\Commands;

use App\Http\Traits\ClosePositions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use function Swoole\Coroutine\Http\get;
use function Swoole\Coroutine\run;
use function Swoole\Coroutine\go;

class ContractGrb extends Command
{
    use ClosePositions;

    //脚本命令
    protected $signature = 'contract:grb';
    //脚本名称
    protected $description = '所有币爆仓检查(脚本)';

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
        try {
            $product = DB::table('products')->where('state', 1)
                         ->select(['pid', 'pname', 'code', 'state', 'type'])
                         ->get();
            $time    = time();
            //定时器防重复器
            Redis::set('grbTimeId', $time);

            \Swoole\Timer::tick(1000, function (int $timer_id) use ($product, $time)
            {
                $grb_time_id = Redis::get('grbTimeId');
                dump($grb_time_id);
                if ($grb_time_id != $time) {
                    //新任务进来删除本任务
                    dump('原grbTimeId:' . $time . '，新grbTimeId:' . $grb_time_id);
                    \Swoole\Timer::clear($timer_id);
                }
                foreach ($product as $k => $v) {
                    go(function () use ($v)
                    {
                        //协程处理爆仓
                        $subscribe         = array();
                        $subscribe['code'] = $v->code;
                        $this->doClosePositions($subscribe);
                    });
                }
            });
            //进程处理

        } catch (\Exception $exception) {
            var_dump($exception->getMessage());
            \Log::info('RedisSubscribe bnb_usdt Faild' . $exception->getMessage());
        }
    }

//file_put_contents('test_pool.txt', '123');

    public function test(int $timer_id, ...$params)
    {
        dump($timer_id);
    }
}