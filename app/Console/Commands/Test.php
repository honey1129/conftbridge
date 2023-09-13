<?php

namespace App\Console\Commands;

use App\Models\SystemValue;
use Illuminate\Console\Command;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Swoole\Coroutine\Http\Client;
use Swoole\Coroutine\WaitGroup;
use function Co\run;
use function Swoole\Coroutine\go;
use function Swoole\Coroutine\getCid;
use App\User;
use DB;
use App\Models\ChildPool;

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Task:test';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '测试脚本';


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
        for ($i = 0; $i < 20; $i++) {
            $num = mt_rand(0, 10);
            if ($num % 2 == 0) {
                $account = $this->get_rand_str(3, 0, 0);
                $account .= $this->get_rand_num(5);
                $account .= '@163.com';
            } else {
                $account = $this->get_rand_num(10);
                $account .= '@qq.com';
            }
            $data = [
                'email'                 => $account,
                // 'is_robot'              => $is_robot,
                'code'                  => '999998',
                'password'              => '111111',
                'password_confirmation' => '111111',
                'recommend'             => '5069223252'
            ];
            $param = http_build_query($data);
            $url = 'https://cfa.cnsunys.com/api/user/register';
            $result = $this->curl_request($url, $param, 'POST');
            $resultArr = json_decode($result, true);
            if ($resultArr['code'] == 200) {
                echo 1;
            } else {
                echo 0;
            }
            sleep(1);
        }
    }

    function get_rand_str($randLength = 6, $addtime = 0, $includenumber = 1)
    {
        if ($includenumber) {
            $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        } else {
            $chars = 'abcdefghijklmnopqrstuvwxyz';
        }
        $len = strlen($chars);
        $randStr = '';
        for ($i = 0; $i < $randLength; $i++) {
            $randStr .= $chars[rand(0, $len - 1)];
        }
        $tokenvalue = $randStr;
        if ($addtime) {
            $tokenvalue = $randStr . time();
        }
        return $tokenvalue;
    }


    function get_rand_num($randLength = 10)
    {
        $chars = '123456789';
        $len = strlen($chars);
        $randStr = '';
        for ($i = 0; $i < $randLength; $i++) {
            $randStr .= $chars[rand(0, $len - 1)];
        }
        $tokenvalue = $randStr;
        return $tokenvalue;
    }


    public function curl_request($url, $param = '', $httpMethod = 'GET')
    {
        $ch = curl_init();
        if (stripos($url, "https://") !== FALSE) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        }
        if ($httpMethod == 'GET') {
            if (empty($param)) {
                curl_setopt($ch, CURLOPT_URL, $url);
            } else {
                if (stripos($url, "?") !== FALSE) {
                    curl_setopt($ch, CURLOPT_URL, $url . "&" . http_build_query($param));
                } else {
                    curl_setopt($ch, CURLOPT_URL, $url . "?" . http_build_query($param));
                }
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        } else {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        }
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        $sContent = curl_exec($ch);
        $aStatus = curl_getinfo($ch);
        curl_close($ch);
        if (intval($aStatus["http_code"]) == 200) {
            return $sContent;
        } else {
            return FALSE;
        }
    }
}