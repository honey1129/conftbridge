<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Products;
use App\Models\RealTime;
use App\Service\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class TicketController extends Controller
{
    /**
     * K线历史数据
     * @param Request $request
     * @return array
     */
    public function kline(Request $request)
    {

        $pageSize  =$request->get('pageSize', 500);
        $pageSize  > 500?$pageSize=500:'';
        //$request->get('pageSize', 300);
        $goodsType = $request->get('goodsType', 'minute');
        $code      = $request->get('code', 'btc_usdt');
        switch ($goodsType) {
            case 'minute':
                $table = 'xy_1min_info';
                break;
            case 'minute5':
                $table = 'xy_5min_info';
                break;
            case 'minute15':
                $table = 'xy_15min_info';
                break;
            case 'minute30':
                $table = 'xy_30min_info';
                break;
            case 'minute60':
                $table = 'xy_60min_info';
                break;
            case 'hour4':
                $table = 'xy_4hour_info';
                break;
            case 'day':
                $table = 'xy_dayk_info';
                break;
            case 'month':
                $table = 'xy_month_info';
                break;
            case 'week':
                $table = 'xy_week_info';
                break;
            default:
                $table = "xy_1min_info";
                break;
        }
        $fix = $pageSize . '_' . $goodsType . '_' . $code;
        //Cache 的第二個參數是分秒
        $result = Cache::remember($fix, 30, function () use ($table, $code, $pageSize)
        {
            return DB::table($table)
                     ->where('code', $code)
                     ->limit($pageSize)
                     ->orderBy('timestamp', 'desc')
                     ->get();
        });
        return __return($this->successStatus, 'ok', $result);
    }

    /**
     * 行情信息
     * @param Request $request
     * @return array
     */
    public function getPro(Request $request)
    {
        //code
        $code = $request->get('code', '');

        if ($code != '') {
            $find['code'] = $code;
        }

        $find['state'] = Products::DIS_TYPE;
//        $find['type'] = 1;
        $prolsit = Products::where($find)
                           ->select('image', 'pname as name', 'code', 'mark_cn')
                           ->orderBy('sort')
                           ->get()->toArray();
        $keys    = 'vb:ticker:newitem:';
        $list    = [];
        foreach ((array)$prolsit as $val) {
            $str      = $keys . $val['code'];
            $jsonData = json_decode(Redis::get($str), true) ?? [];
            if (empty($jsonData)) {
                $jsonData = [
                    'code'       => $val['code'],
                    'name'       => $val['name'],
                    'date'       => date('Y-m-d'),
                    'time'       => date('H:i:s'),
                    'price'      => 0.1,
                    'cnyPrice'   => 0.7,
                    'open'       => 0.1,
                    'close'      => 0.1,
                    'high'       => 0.1,
                    'low'        => 0.1,
                    'volume'     => 0.1,
                    'change'     => 0.1,
                    'changeRate' => '0.1',
                    'type'       => 'ticker',
                ];
            }
            $jsonData['mark_cn']  = $val['mark_cn'];
            $jsonData['image']    = ImageService::fullUrl($val['image']);
            $jsonData['cnyPrice'] = round($jsonData['cnyPrice'], 4);
            $code                 = $val['code'];
            $fix                  = 'getPro_chartData_' . $code;
            // 获取15分钟线，数据相对少也够100条,访问压力小，1分钟数据多，5分钟为默认线
            $chartData             = Cache::remember($fix, rand(30, 100), function () use ($code)
            {
                return DB::table('xy_15min_info')
                         ->where('code', $code)
                         ->limit(100)
                         ->orderBy('timestamp', 'desc')
                         ->pluck('closingPrice');
            });
            $jsonData['chartData'] = $chartData;
            $list[]                = $jsonData;
        }

        return __return($this->successStatus, __('Success'), $list);
    }

    /**
     * 实时成交数据
     * @param Request $request
     * @return array
     */
    public function RealTimeDeal(Request $request)
    {
        $code = $request->get('code', '');
        $keys = 'vb:trader:newitem:';
        if (!$code) {
            return __return($this->errStatus, '币种参数错误');
        }
        $jsonData = json_decode(Redis::get($keys . $code), true) ?? [];
        /*if($code=='nexc_usdt')
        {
             $list = array();
             $result =  RealTime::select('id','pid','code','price','volume','addtime','type')->where('code',$code)->orderBy('id','desc')->limit(50)->get()->toArray();
             if($result)
             {
                  foreach ($result as $key =>$val)
                  {
                       $type = 'sell';
                       if($val['type']==1)
                       {
                           $type = 'buy';
                       }
                        $list[] = [
                            'dt'=>$val['addtime'],
                            'dc'=>$type,
                            'amount'=>$val['volume'],
                            'price'=>$val['price'],
                        ];
                  }
             }
             list($msec, $sec) = explode(' ', microtime());
             $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
             $jsonData['code'] = 'nexc_usdt';
             $jsonData['name'] ='NEXC_USDT';
             $jsonData['timestamp'] = $msectime;
             $jsonData['data'] = $list;
        }
        else
        {
            $jsonData = json_decode(Redis::get($keys.$code), true) ?? [];
        }*/
        return __return($this->successStatus, __('Success'), $jsonData);
    }


    /**
     * 盘口/深度 get
     * @param Request $request
     * @return array
     */
    public function getDepth(Request $request)
    {
        // code type(depth pct)
        $data = $request->all();
        if (!isset($data['code'])) {
            return __return($this->errStatus, 'code参数错误');
        }
        if (!isset($data['type'])) {
            return __return($this->errStatus, 'type参数错误');
        }
        switch ($data['type']) {
            case 'depth':
                $keys = 'vb:depth:newitem:';
//                $keys = in_array($data['code'], $shuzu) ? 'vb:depth:newitem1:' : 'vb:depth:newitem:';
                break;
            case 'pct':
                $keys = 'vb:depth:pct:newitem:';
//                $keys = in_array($data['code'], $shuzu) ? 'vb:depth:pct:newitem1:' : 'vb:depth:pct:newitem:';
                break;
        }
        $list = [];
        try {
            $str    = $keys . $data['code'];
            $list[] = json_decode(Redis::get($str), true) ?? [];
//            //修改交易数量
//            $models = json_decode(Redis::get($str), true) ?? [];
//            $asks   = $models['asks'];
//            $bids   = $models['bids'];
//            foreach ($asks as $k => $v) {
//                $asks[$k]['totalSize'] = $v['totalSize'] / 100;
//            }
//            foreach ($bids as $key => $value) {
//                $bids[$key]['totalSize'] = $v['totalSize'] / 100;
//            }
//            $models['asks'] = $asks;
//            $models['bids'] = $bids;
//            $list[]         = $models;
            return __return($this->successStatus, __('Success'), $list);
        } catch (\Exception $exp) {
            return __return($this->errStatus, $exp->getMessage());
        }
    }

    /**
     * 获取汇率
     */
    public function getRate()
    {
        $str    = 'vb:indexTickerAll:usd2cny';
        $exrate = json_decode(Redis::get($str), true);
        return __return($this->successStatus, __('Success'), $exrate);
    }

    /**
     * 币种信息
     * @param Request $request
     * @return array
     */
    public function getProInfo(Request $request)
    {
        $data = $request->all();
        if (!isset($data['code'])) {
            return __return($this->errStatus, 'code参数错误');
        }
        $find['code']      = $data['code'];
        $product           = Products::where($find)
                                     ->select('leverage', 'pname', 'code', 'mark_cn', 'fxtime', 'fxnum', 'fxprice', 'fxweb', 'fxbook', 'memo', 'image')
                                     ->first();
        $product->leverage = explode(',', $product->leverage);
        return __return($this->successStatus, __('Success'), $product);
    }

    /**
     * 获取固定汇率 废弃
     */
    public function getFixrate()
    {
        $item['USDT'] = config('gold.rate');
        return __return($this->successStatus, __('Success'), $item);
    }

    //交易币种信息
    public function starList(Request $request)
    {
        if ($request->leixing == 2) {
            $arr = ['state' => 1];
        } elseif ($request->leixing == 3) {
            $arr = ['state' => 1, 'is_new' => 1];
        } else {
            $arr = ['state' => 1, 'is_hot' => 1];
        }
        $product = Products::starList($arr);
        return __return($this->successStatus, '获取成功', $product);
    }

}
