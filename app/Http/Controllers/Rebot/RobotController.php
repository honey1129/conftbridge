<?php

namespace App\Http\Controllers\Rebot;

use App\Http\Controllers\Api\TradeController;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * 自动发单
 * Class RobotController
 * @package App\Http\Controllers\Rebot
 */
class RobotController extends Controller
{
    public function post_buy($account,$code,$down){
        $trade_obj = new TradeController();
        $userInfo = DB::table('users')
            ->where('account', $account)
            ->first();
        $type   = 1;
        if ($down == 1)
        {// 0随机盘口价格  1跌  2涨
            if (mt_rand(1,100) < 80)
            {
                $fix = $this->randFloat(0.0001, 0.0005, 6) * -1;
            }
            else{
                $fix = $this->randFloat(0.00001, 0.00005, 6);
            }
        }
        else if ($down == 2)
        {
            if (mt_rand(1,100) < 80)
            {
                $fix = $this->randFloat(0.0001, 0.0005, 6);
            }else{
                $fix = $this->randFloat(0.00001, 0.00005, 6) * -1;
            }
        }else{
            $fix = 0;
        }

        //获取最新的价格
        $buy_price = DB::table('products')->where('code',$code)->value('actprice');
        $str  = 'vb:ticker:newprice:' . $code;
        Redis::set($str,$buy_price);

        $str = 'vb:indexTickerAll:usd2cny';
        $exrate =json_decode(Redis::get($str), true);
        //- ticker  实时行情
        $open_price = DB::table('xy_second_info_token')->where('code',$code)->orderBy('id','asc')->value('price')+0;
        $high_price = DB::table('xy_second_info_token')->where('code',$code)->max('price')+0;
        $low_price = DB::table('xy_second_info_token')->where('code',$code)->min('price')+0;
        $vol_price = DB::table('xy_second_info_token')->where('code',$code)->orderBy('id','desc')->value('volume')+0;

        if($open_price==0)
        {
            $open_price = $buy_price;
        }
        $change_price = $buy_price-$open_price;
        $change_rate = round($change_price*100/$open_price,2).'%';
        list($msec, $sec) = explode(' ', microtime());
        $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        $ticker = array();
        $ticker['code'] = $code;
        $ticker['name'] =strtoupper($code);
        $ticker['date'] = date('Y-m-d');
        $ticker['time'] = date('H:i:s');
        $ticker['timestamp'] = $msectime;
        $ticker['price'] = $buy_price;
        $ticker['cnyPrice'] = $buy_price*$exrate['USDT'];
        $ticker['open'] = $open_price;
        $ticker['high'] = $high_price;
        $ticker['low'] = $low_price ;
        $ticker['close'] = $open_price;
        $ticker['volume'] = $vol_price;
        $ticker['change'] =$change_price;
        $ticker['changeRate'] = $change_rate;
        $ticker['type'] ='ticker';

        $ticker_json = json_encode($ticker);
        $ticker_str = 'vb:ticker:newitem:' . $code;
        Redis::set($ticker_str,$ticker_json);


        $buy_price += $fix;
        $buynum = $this->randFloat(1,20,6);
        $data = array(
            'code'     => $code,
            'type'     => $type,
            'otype'    => 1, //限价
            'buyprice' => $buy_price,
            'buynum'   => $buynum,
        );
        if ($data['buyprice']>0)
        {
            $result = $trade_obj->bbtran_robot($userInfo, $data);
           // return $result;
        }

    }
    public function post_sell($account,$code,$down)
    {
        $trade_obj = new TradeController();
        $userInfo = DB::table('users')
            ->where('account', $account)
            ->first();

        $type   = 2;
        if ($down == 1) {
            if (mt_rand(1,100) < 80){
                $fix = $this->randFloat(0.0001, 0.0005, 6) * -1;
            }else{
                $fix = $this->randFloat(0.00001, 0.00005, 6);
            }
        } else if ($down == 2) {
            if (mt_rand(1,100) < 80){
                $fix = $this->randFloat(0.0001, 0.0005, 6);
            }else{
                $fix = $this->randFloat(0.00001, 0.00005, 6) * -1;
            }
        }else{
            $fix = 0;
        }
        $buy_price = DB::table('products')->where('code',$code)->value('actprice');
        $str  = 'vb:ticker:newprice:' . $code;
        Redis::set($str,$buy_price);
        $str = 'vb:indexTickerAll:usd2cny';
        $exrate =json_decode(Redis::get($str), true);
        //- ticker  实时行情
        $open_price = DB::table('xy_second_info_token')->where('code',$code)->orderBy('id','asc')->value('price')+0;
        $high_price = DB::table('xy_second_info_token')->where('code',$code)->max('price')+0;
        $low_price = DB::table('xy_second_info_token')->where('code',$code)->min('price')+0;
        $vol_price = DB::table('xy_second_info_token')->where('code',$code)->orderBy('id','desc')->value('volume')+0;
        if($open_price==0)
        {
            $open_price = $buy_price;
        }
        $change_price = $buy_price-$open_price;
        $change_rate = round($change_price*100/$open_price,2).'%';
        list($msec, $sec) = explode(' ', microtime());
        $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        $ticker = array();
        $ticker['code'] = $code;
        $ticker['name'] =strtoupper($code);
        $ticker['date'] = date('Y-m-d');
        $ticker['time'] = date('H:i:s');
        $ticker['timestamp'] = $msectime;
        $ticker['price'] = $buy_price;
        $ticker['cnyPrice'] = $buy_price*$exrate['USDT'];
        $ticker['open'] = $open_price;
        $ticker['high'] = $high_price;
        $ticker['low'] = $low_price ;
        $ticker['close'] = $open_price;
        $ticker['volume'] = $vol_price;
        $ticker['change'] =$change_price;
        $ticker['changeRate'] = $change_rate;
        $ticker['type'] ='ticker';

        $ticker_json = json_encode($ticker);
        $ticker_str = 'vb:ticker:newitem:' . $code;
        Redis::set($ticker_str,$ticker_json);




        $buy_price += $fix;
        $buynum = $this->randFloat(1,20,6);
        $data = array(
            'code'     => $code,
            'type'     => $type,
            'otype'    => 1, //限价
            'buyprice' => $buy_price,
            'buynum'   => $buynum,
        );

        if ($data['buyprice']>0){
            $result = $trade_obj->bbtran_robot($userInfo, $data);
           // return $result;
        }
    }


    /**
     * 随机数
     * @param int $min
     * @param int $max
     * @param int $sep
     * @return float
     */
    protected function randFloat($min = 0, $max = 1, $sep = 2)
    {
        $rand = $min + mt_rand() / mt_getrandmax() * ($max - $min);
        return floatval(number_format($rand, $sep));
    }

}
