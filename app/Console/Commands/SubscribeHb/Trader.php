<?php

namespace App\Console\Commands\SubscribeHb;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use function Co\run;

class Trader extends Command
{
    protected $signature = 'SubscribeHb:trader';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '内部订阅交易行情重新处理（脚本）';

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

        Redis::subscribe(['inside:vb:channel:trader'], function ($msg, $chan)
        {
            $data = json_decode($msg, true);
            if (!empty($data)) {
                foreach ($data['data'] as $k => $item) {
                    $item['dt']       = time() * 1000;
                    $item['dc']       = rand(0, 1) ? 'sell' : 'buy';
                    $item['amount']   = bcmul($item['amount'], rand(10, 90) / 10, 6);
                    $data['data'][$k] = $item;
                }
                //重新调起客户端
                run(function () use ($data)
                {
                    $redis = new \Swoole\Coroutine\Redis();
                    $redis->connect('127.0.0.1', 6379);
                    $result = $redis->publish('vb:channel:trader', json_encode($data));
                });
//                    $status = Redis::publish('vb:channel:trader', json_encode($data));
            }
        });


    }


}