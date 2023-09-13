<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Http\Traits\WriteUserMoneyLog;
use App\Models\Products;
use App\Models\UserAssets;
use App\Models\UserEntrusts;
use App\Models\UserPositions;
use App\Models\QiqOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use QrCode;


class OptionsController extends Controller
{
    use WriteUserMoneyLog;

    protected $title = '期权交易控制器';

    /**
     * 获取期权周期
     * @param Request $request
     * @return array
     */
    function setCycle(Request $request)
    {
        $list              = array();
        $list['qiq_cycle'] = config('site.qiq_cycle');
        return __return($this->successStatus, '获取成功', $list);
    }

    /**
     * 下单
     * @param Request $request
     * @return array
     */
    function createOrder(Request $request)
    {
        $userInfo  = $request->user;
        $validator = Validator::make(
            $request->all(),
            [
                'code'     => 'required', // 产品名称
                'newprice' => ['required', 'regex:/^[+]{0,1}(\d+)$|^[+]{0,1}(\d+\.\d+)$/'], //最新价格  正数（包括小数）
                'buynum'   => ['required', 'regex:/^[+]{0,1}(\d+)$|^[+]{0,1}(\d+\.\d+)$/'], //买入张数  正数(包括小数)
                'type'     => ['required', Rule::in([1, 2])], // 1市价 2 限价
                'cycle'    => 'required', // 产品杠杆
                //                'payment_password' => 'required', // 支付密码必须
            ],
            [
                'newprice.regex'    => '最新价格格式错误',
                'buynum.regex'      => '买入数量格式错误',
                'newprice.required' => '最新价格必须',
                'buynum.required'   => '买入数量必须',
                'type.required'     => '买入类型必须',
                'cycle.required'    => '周期必须',
                //                'payment_password.required'     => '支付密码必须',
                'code.required'     => '币种必须',
            ]

        );
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }
        $input    = $request->input();
        $newprice = $input['newprice'];  //最新价格
        $type     = $input['type'];      //1市价 2 限价
        $buynum   = $input['buynum'];    //1涨 2跌
        if ($buynum < 0) {
            return __return($this->errStatus, '买入数量格式错误');
        }
        $code  = $input['code'];               //产品名称
        $cycle = $input['cycle'];              //产品名称
//        $price_json = Redis::get('vb:ticker:newprice:' .$code);
//        if (!$price_json) {
//            return __return($this->errStatus, '网络价格异常');
//        }
//        $actprice = number_format($price_json, 4, '.', '');
//
//        if($actprice <= 0){
//            return __return($this->errStatus, '网络价格异常');
//        }
        $trans_fee = config('site.qiq_fee', 0);//手续费比例
        DB::beginTransaction();

        try {
            $flag = true;
            // asset lock  锁
            $userBalance = UserAssets::getBalance($userInfo->id, 8, 4, true);
            $zj          = $buynum;//金额
            $total       = $zj;
            if ($userBalance->balance < $total) {
                //DB::rollBack();
                return __return($this->errStatus, '期权账户资金不足'); // 资金不足
            }
            $pid      = Product::where('code', $code)->value('pid');
            $info     = [
                'uid'        => $userInfo->id,
                'account'    => $userInfo->account,
                'pid'        => $pid,
                'pname'      => $code,
                'wtprice'    => $newprice,
                'buynum'     => $buynum,
                'totalprice' => $buynum,
                'type'       => $type,
                'status'     => 1,
                'cycle'      => $cycle,
                // 'market_price' => $actprice,
                'endtime'    => date('Y-m-d H:i:s', time() + $cycle),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $position = QiqOrder::create($info);
            if (!$position) {
                $flag = false;
            }
            // 本金财务流水
            if ($flag && $zj > 0) {
                $dec_wallone =
                    $this->writeBalanceLog($userBalance, $position->id, -$zj, 45, '期权交易扣款', 'Option transaction deductions', 8, 'USDT', 4);
                if (!$dec_wallone) {
                    $flag = false;
                }
            }
            if ($flag) {
                DB::commit();
                return __return($this->successStatus, '下单成功'); // 下单成功
            } else {
                DB::rollBack();
                return __return($this->errStatus, '下单失败'); // 下单失败
            }
        } catch (\Exception $exception) {
            DB::rollBack();
            \Log::info('下单' . $exception->getMessage() . $exception->getLine());
            return __return($this->errStatus, '下单失败'); // 下单失败
        }

    }

    /**
     * 撤单接口
     * @param Request $request
     * @return array
     */
    public function cancelOrder(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'id' => 'required',
            ],
            [
                'id.required' => '订单ID必须',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }
        $user  = $request->user;
        $where = [
            'uid'    => $user->id,
            'id'     => $request->id,
            'status' => 1
        ];
        $order = QiqOrder::where($where)->where('endtime', '>', now())->first();
        if (!$order) {
            return __return($this->errStatus, '订单不存在，请刷新列表');
        }
        DB::beginTransaction();
        try {
            $flag   = true;
            $update = QiqOrder::where($where)->update(['status' => 3]);
            if (!$update) {
                $flag = false;
            }
            if ($flag) {
                $money = $order['totalprice'] + $order['fee'];
                if ($money > 0) {
                    $assets = UserAssets::getBalance($user->id, 8, 4, true);
                    // 财务流水
                    $inc =
                        $this->writeBalanceLog($assets, $order->id, $money, 4, '期权交易撤单', 'Option transaction cancellation', 8, 'USDT', 4);
                    if (!$inc) {
                        $flag = false;
                    }
                }
            }
            if ($flag) {
                DB::commit();
                return __return($this->successStatus, '撤单成功');
            } else {
                DB::rollBack();
                return __return($this->errStatus, '撤单失败');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return __return($this->errStatus, '撤单失败');
        }

    }


    /**
     * 获取用户订单列表
     * @param Request $request
     * @return array
     */
    public function orderList(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'status' => 'required',
            ],
            [
                'status.required' => '类型必须',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }
        $status = $request->status;
        $user   = $request->user;
        $trans  = QiqOrder::where('uid', $user->id)
                          ->where('status', $status)
                          ->orderBy('id', 'desc')
                          ->paginate(10);
        if ($trans) {
            $qiq_rate = config('site.qiq_rate');
            foreach ($trans as $key => $val) {
                if ($trans[$key]['status'] == 1) {
                    /*$trans[$key]['earnprice'] = 0 ;
                    $newPrice = Redis::get('vb:ticker:newprice:' .$trans[$key]['pname']);
                    if(($trans[$key]['type']==1) &&($newPrice>$trans[$key]['wtprice']))
                    {
                        $trans[$key]['earnprice'] = number_format($trans[$key]['buynum']*$qiq_rate*0.01,'6','.','');
                    }
                    if(($trans[$key]['type']==2) &&($newPrice<$trans[$key]['wtprice']))
                    {
                        $trans[$key]['earnprice'] = number_format($trans[$key]['buynum']*$qiq_rate*0.01,'6','.','');
                    }*/
                    $trans[$key]['earnprice'] =
                        number_format($trans[$key]['buynum'] * (1 + $qiq_rate * 0.01), '6', '.', '');
                }
            }
        }
        return __return($this->successStatus, '获取成功', $trans);
    }

    /**
     * 期权实时数据列表
     * @param Request $request
     * @return array
     */
    public function realList(Request $request)
    {
        $trans = QiqOrder::select('id', 'account', 'wtprice', 'buynum', 'cjprice', 'totalprice', 'type')
                         ->where(['status' => 2])
                         ->orderBy('id', 'desc')
                         ->paginate(10);
        if ($trans) {
            foreach ($trans as $key => $val) {
                $trans[$key]['account'] = substr_replace($val['account'], '****', 3, 4);
            }
        }
        return __return($this->successStatus, '获取成功', $trans);
    }

    /**
     * 平仓
     * @param Request $request
     * @return array
     */
    public function closePosition(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'id' => 'required',
            ],
            [
                'id.required' => '订单ID必须',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }
        $user       = $request->user;
        $order_info = QiqOrder::where(['status' => 1, 'uid' => $user->id, 'id' => $request->id])->first();
        if (empty($order_info)) {
            return __return($this->errStatus, '该订单信息不存在');
        }
        $qiq_rate  = config('site.qiq_rate');
        $newPrice  = Redis::get('vb:ticker:newprice:' . $order_info->pname);
        $earnprice = 0;
        if (($order_info->type == 1) && ($newPrice > $order_info->wtprice)) {
            $earnprice = number_format($order_info->buynum * $qiq_rate * 0.01, '6', '.', '');
        }
        if (($order_info->type == 2) && ($newPrice < $order_info->wtprice)) {
            $earnprice = number_format($order_info->buynum * $qiq_rate * 0.01, '6', '.', '');
        }
        QiqOrder::where('id', $request->id)->update(['status' => 2, 'cjprice' => $newPrice, 'earnprice' => $earnprice]);
        if ($earnprice > 0) {
            $asset     = UserAssets::getBalance($user->id, 8, 4);
            $earnprice += $order_info->buynum;
            $this->writeBalanceLog($asset, $request->id, $earnprice, 46, '期权交易收益', 'Option trading income', 8, 'USDT', 4);
        }
        $this->updateTeamAward($user->id, $request->id, $order_info->sxfee, 4);
        return __return($this->successStatus, '操作成功');
    }

    /**
     * 一键平仓
     * @param Request $request
     * @return array
     */
    public function allClosePosition(Request $request)
    {
        $user       = $request->user;
        $order_list = QiqOrder::where(['status' => 1, 'uid' => $user->id])
                              ->orderBy('id', 'desc')
                              ->get()->toArray();
        if ($order_list) {
            $qiq_rate = config('site.qiq_rate');
            foreach ($order_list as $key => $val) {
                $earnprice = 0;
                $newPrice  = Redis::get('vb:ticker:newprice:' . $val['pname']);
                if (($val['type'] == 1) && ($newPrice > $val['wtprice'])) {
                    $earnprice = number_format($val['buynum'] * $qiq_rate * 0.01, '6', '.', '');
                }
                if (($val['type'] == 2) && ($newPrice < $val['wtprice'])) {
                    $earnprice = number_format($val['buynum'] * $qiq_rate * 0.01, '6', '.', '');
                }
                QiqOrder::where('id', $val['id'])->update(['status'    => 2, 'cjprice' => $newPrice,
                                                           'earnprice' => $earnprice
                ]);
                if ($earnprice > 0) {
                    $asset     = UserAssets::getBalance($user->id, 8, 4);
                    $earnprice += $val['buynum'];
                    $this->writeBalanceLog($asset, $val['id'], $earnprice, 46, '期权交易收益', 'Option trading income', 8, 'USDT', 4);
                }
                $this->updateTeamAward($user->id, $val['id'], $val['sxfee'], 4);
            }
        }
        return __return($this->successStatus, '操作成功');
    }
}
