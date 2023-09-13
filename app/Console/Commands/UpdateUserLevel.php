<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Swoole\Coroutine;
use Swoole\Coroutine\Http\Client;
use function Co\run;
use function Swoole\Coroutine\go;
use App\User;

class UpdateUserLevel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update_user_level';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update user level';


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

    }

    function task()
    {
        run(function ()
        {
            //协程承载每个币的数据
            go(function ()
            {
                $client = new Client('api.huobi.pro', 443, true);
                $ret = $client->upgrade('/ws');
                //订阅数据
                $str = '{"sub": "market.okbusdt.kline.1day"}';
                //推送数据
                $client->push($str);
                if ($ret) {
                    while (true) {
                        try {
                            //接收数据
                            $result = $client->recv();
                            //发生错误或对端关闭连接，本端也需要关闭
                            if ($result === '' || $result === false) {
                                echo "errCode: {$client->errCode}\n";
                                $client->close();
                                break;
                            }
                            $result = gzdecode($result->data);
                            if (substr($result, 0, 7) == '{"ping"') {
                                $ts = substr($result, 8, 21);
                                $pong = '{"pong":' . $ts . '}';
                                //查看是否给远程响应
                                $client->push($pong);
                            } else {
                                //处理数据
                                var_dump($result);
                            }
                        } catch (\Exception $e) {
                            var_dump('err:' . $e->getMessage());
                        }
                        Coroutine::sleep(0.05);
                    }
                } else {
                    echo 'maticusdt socket连接失败';
                }
            });
        });
    }
}