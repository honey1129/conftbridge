<?php

namespace App\Http\Controllers\Api;

use App\Models\Products;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;

class IndexController extends Controller
{
    /**
     * 外调接口
     * @param Request $request
     * @return array
     */
    public function getpros(Request $request)
    {
        $code = $request->get('code');
        if (!empty($code)) {
            $find['code'] = $code;
        }
        $newList = [];
        $list    = [];
        try {
            $find['state'] = Products::DIS_TYPE;
            $prolsit       = Products::where($find)
                ->select('pname as name','code')
                ->orderBy('sort')
                ->get()->toArray();
            foreach ($prolsit as $val) {
                //  行情 depth    盘口 ticker
                $str  = 'vb:ticker:newitem:' . $val['code'];
                $data = Redis::get($str);
                if($data){
                    $list[] = json_decode($data, true);
                }
            }
            $newList['data']     = time();
            foreach ($list as $key => &$value) {
                $item['symbol'] = $value['code'];
                $item['buy']    = $value['buy'];
                $item['high']   = $value['high'];
                $item['last']   = $value['price'];
                $item['low']    = $value['low'];
                $item['sell']   = $value['sell'];
                $item['change'] = $value['change'];
                $item['vol']    = $value['volume'];
                $newList['ticker'][] = $item;
            }
            unset($value);
        } catch (\Exception $exp) {
            \Log::error($exp->getMessage().$exp->getLine());
            return ['code' => -1,'msg' => '获取异常'];
        }
        return $newList;
    }
}
