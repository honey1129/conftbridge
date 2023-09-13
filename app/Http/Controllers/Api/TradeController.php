<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assets;
use App\Models\Order;
use App\Models\OrderExt;
use App\Models\Products;
use App\Models\SecondInfo;
use App\Models\UserAssets;
use App\Models\Product;
use App\Models\WalletCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Http\Traits\WriteUserMoneyLog;
use Validator;

class TradeController extends Controller
{
    use WriteUserMoneyLog;

    const DEBUG = 1;
    protected $class;
    protected $method;

    public function getActName()
    {
        $action = \Route::current()->getActionName();
        list($class, $method) = explode('@', $action);
        $this->class  = $class;
        $this->method = $method;
        $action_array = [
            'cancel',         //撤单
            'trans',          //发单
        ];
        if (in_array($method, $action_array)) {
            Redis::select(2);//币币
        }
    }


    /**
     * 币币交易机器人自动下单
     * @param $userInfo
     * @param $data
     * @return array
     */
    public function bbtran_robot($userInfo, $data)
    {
        $check     = DB::table('users')->where('account', $userInfo->account)->first();
        $trans_fee = config('bb_trans.trans_fee');
        $currency  = $this->getCurrencyByCurrencyId($data['code']);
        $code      = $this->coin_cut($data['code']);
        $sell_code = $code[0];
        $buy_code  = $code[1];

        if ($data['type'] == 1) {
            //限价
            $buyprice  = number_format($data['buyprice'], 6, '.', '');
            $buyprice1 = $buyprice;
            $buynum    = number_format($data['buynum'], 6, '.', '');
            $toalprice = number_format(($buyprice * $buynum), 6, '.', '');
            $fee       = number_format(($toalprice * $trans_fee * 0.01), 6, '.', '');
        } else {
            //卖出
            $buyprice  = number_format($data['buyprice'], 6, '.', '');
            $buyprice1 = $buyprice;
            $buynum    = number_format($data['buynum'], 6, '.', '');
            $toalprice = 0;
            $fee       = 0;
        }

        //添加订单信息
        $insert = Order::create([
            'member_id'   => $check->id,
            'account'     => $check->account,
            'currency_id' => $currency['pid'],
            'pname'       => $currency['pname'],
            'wtprice'     => $buyprice,
            'wtprice1'    => $buyprice1,
            'wtnum'       => $buynum,
            'totalprice'  => $toalprice,
            'fee'         => $fee,
            'type'        => $data['type'],
            'otype'       => $data['otype'],
            'add_time'    => time(),
            'l_code'      => $sell_code,
            'r_code'      => $buy_code,
        ]);
        if ($insert) {
            DB::table('xy_orid')->where('id', 1)->update(['oid' => $insert->orders_id]);
        }
    }


    /**
     * 币币交易下单 post
     */
    public function bbtran(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'otype' => 'required',
                'type'  => 'required',
                'code'  => 'required',
            ],
            [
                'otype.required' => '类型不能为空',
                'type.required'  => '方向不能为空',
                'code.required'  => '产品名称不能为空',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }
        $data = $request->all();
        $user = $request->user;
        $this->getActName();
        if (Redis::exists($user->account)) {
            return __return($this->errStatus, '操作太频繁了');
        } else {
            $key = $user->account;
            $val = $this->class . '/' . $this->method;
            Redis::setex($key, 5, $val);
        }

        try {
            $currency = $this->getCurrencyByCurrencyId($data['code']);
            if (!$currency) {
                throw new \Exception('未找到该币种');
            }
            if (!in_array($data['type'], [1, 2])) {
                throw new \Exception('类型错误/买入卖出');
            }
            if (!in_array($data['otype'], [1, 2])) {
                throw new \Exception('类型错误/市价限价');
            }
            $newprice = Redis::get('vb:ticker:newprice:' . $data['code']);
            if (!$newprice) {
                return __return($this->errStatus, '网络价格异常');
            }
            $code      = $this->coin_cut($data['code']);
            $sell_code = $code[0];
            $buy_code  = $code[1];
            $sell_pid  = $this->get_asset_pid($sell_code);
            $buy_pid   = $this->get_asset_pid($buy_code);
            DB::beginTransaction();
            $trans_fee = config('site.trans_fee');
            $order     = new Order();
            // 买入
            if ($data['type'] == 1) {
                //限价
                if ($data['otype'] == 1) {
                    if ($data['buyprice'] < 0.01) {
                        throw new \Exception('委托价格不能低于0.01');
                    }
                    if (!is_numeric($data['buyprice']) || $data['buyprice'] <= 0) {
                        throw new \Exception('委托价格必须大于0');
                    }
                    if (!is_numeric($data['buynum']) || $data['buynum'] <= 0) {
                        throw new \Exception('委托数量必须大于0');
                    }
                    $buyprice = number_format($data['buyprice'], 6, '.', '');
                    if ($buyprice > $newprice) {
                        $buyprice = $newprice;
                    }
                    $buyprice1 = $buyprice;
                    $buynum    = number_format($data['buynum'], 6, '.', '');
//                    $toalprice  = number_format(($buyprice * $buynum), 6, '.', '');
                    $toalprice = number_format(($buyprice * $buynum), 6, '.', '');
//                    $toalprice  = 0;
                    $fee        = number_format(($toalprice * $trans_fee * 0.01), 6, '.', '');
                    $totalmoney = $toalprice - $fee;
                    $status     = 1;
                    $trade_time = 0;
                    $cjprice    = 0;
                    $cjnum      = 0;
                } else {
                    //市价
//                    if (!is_numeric($data['toalprice']) || $data['toalprice'] < 0) {
//                        throw new \Exception('委托总金额必须大于10');
//                    }
                    $data['buynum'] = $data['toalprice'];
                    $buyprice       = $newprice;
                    $buyprice1      = 0;
                    $toalprice      = number_format($data['buynum'] * $newprice, 6, '.', '');
                    $fee            = number_format(($toalprice * $trans_fee * 0.01), 6, '.', '');
                    $totalmoney     = $toalprice - $fee;
                    $status         = 2;
                    $trade_time     = time();
                    $buy_num        = number_format($totalmoney / $newprice, 6, '.', '');
                    $buynum         = $buy_num;
                    $cjprice        = $newprice;
                    $cjnum          = $buy_num;
                    $dec            =
                        $order->_assetAct($user, $sell_pid, $sell_code, $buy_num, 0, '币币交易', 'Currency transaction', 27, 2);
                    if (!$dec) {
                        throw new \Exception('币币交易失败');
                    }
                }
                if ($toalprice < 0.1) {
                    throw new \Exception('交易额过小');
                }
                //检测账户余额
                $asset = UserAssets::getBalance($user->id, $buy_pid, 2);
                if (!$asset) {
                    throw new \Exception('获取账户余额失败');
                }
                if ($toalprice > $asset->balance) {
                    throw new \Exception('The account balance is insufficient. You can only buy at most' . $asset->balance);
                }
                $dec1 =
                    $order->_assetAct($user, $buy_pid, $buy_code, -$fee, 0, '币币交易手续费', 'Currency transaction fee', 25, 2);
                $dec2 =
                    $order->_assetAct($user, $buy_pid, $buy_code, -$totalmoney, 0, '币币交易委托下单', 'Currency transaction', 26, 2);
                if (!$dec1 || !$dec2) {
                    throw new \Exception('币币交易委托下单,扣款失败');
                }
            } else {
                $buynum = number_format($data['buynum'], 6, '.', '');
                //卖出
                $asset = UserAssets::getBalance($user->id, $sell_pid, 2);
                if (!$asset) {
                    throw new \Exception('获取账户余额失败');
                }
                if ($buynum > $asset->balance) {
                    throw new \Exception('The account balance is insufficient. You can only sell at most' . $asset->balance);
                }
                if ($data['otype'] == 1) {
                    if ($data['buyprice'] < 0.01) {
                        throw new \Exception('委托价格不能低于0.01');
                    }
                    //限价
                    if (!is_numeric($data['buyprice']) || $data['buyprice'] <= 0) {
                        throw new \Exception('委托价格必须大于0');
                    }
                    if (!is_numeric($data['buynum']) || $data['buynum'] <= 0) {
                        throw new \Exception('委托数量必须大于0');
                    }
                    $buyprice   = number_format($data['buyprice'], 6, '.', '');
                    $buyprice1  = $buyprice - $buyprice * $trans_fee * 0.01;
                    $toalprice  = 0;
                    $fee        = number_format(($newprice * $data['buynum'] * $trans_fee * 0.01), 6, '.', '');
                    $status     = 1;
                    $trade_time = 0;
                    $cjprice    = 0;
                    $cjnum      = 0;
                } else {
                    $buynum = number_format($data['toalnum'], 6, '.', '');
                    //市价
                    if (!is_numeric($data['toalnum']) || $data['toalnum'] <= 0) {
                        throw new \Exception('委托数量必须大于0');
                    }
//                    $buyprice   = '市价';
                    $buyprice   = $newprice;
                    $buyprice1  = 0;
                    $status     = 2;
                    $trade_time = time();
                    $toalprice  = number_format($buynum * $newprice, 6, '.', '');
                    $fee        = number_format(($toalprice * $trans_fee * 0.01), 6, '.', '');
                    $toalprice  = $toalprice - $fee;
                    $cjprice    = $newprice;
                    $cjnum      = $buynum;
                    $dec1       =
                        $order->_assetAct($user, $buy_pid, $buy_code, $toalprice, 0, '币币交易', 'Currency transaction', 27, 2);
                    if (!$dec1) {
                        throw new \Exception('币币交易失败');
                    }
                }

                $dec =
                    $order->_assetAct($user, $sell_pid, $sell_code, -$buynum, 0, '币币交易委托下单', 'Currency transaction', 26, 2);
                if (!$dec) {
                    throw new \Exception('委托下单,扣款失败');
                }
            }
            $insert = Order::create([
                'member_id'   => $user->id,
                'account'     => $user->account,
                'currency_id' => $currency['pid'],
                'pname'       => $currency['pname'],
                'wtprice'     => $buyprice,
                'wtprice1'    => $buyprice1,
                'wtnum'       => $buynum,
                'cjprice'     => $cjprice,
                'cjnum'       => $cjnum,
                'totalprice'  => $toalprice,
                'fee'         => $fee,
                'type'        => $data['type'],
                'otype'       => $data['otype'],
                'add_time'    => time(),
                'l_code'      => $sell_code,
                'r_code'      => $buy_code,
                'status'      => $status,
                'trade_time'  => $trade_time,
                'is_first'    => 1,
            ]);

            if ($insert) {
                DB::commit();
                return __return($this->successStatus, '下单成功');
            } else {
                DB::rollBack();
                return __return($this->errStatus, '挂单失败');
            }
        } catch (\Exception $exception) {
            DB::rollBack();
            return __return($this->errStatus, $exception->getMessage());
        }
    }


    /**
     * 币币交易撤单 委托撤单 post
     */
    public function cancel(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'order_id' => 'required',
            ],
            [
                'order_id.required' => '订单号不能为空',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }
        $this->getActName();
        $check = $request->user;
        if (Redis::exists($check->account)) {
            return __return($this->errStatus, '操作太频繁了');
        } else {
            $key = $check->account;
            $val = $this->class . '/' . $this->method;
            Redis::setex($key, 5, $val);
        }
        $trans_fee  = config('site.trans_fee');
        $order_id   = $request->order_id;
        $orderModel = new Order();
        unset($find);
        $find  = [
            'orders_id' => $order_id,
            'member_id' => $check->id,
        ];
        $order = Order::where($find)->whereIn('status', [0, 1])->first();
        if (!$order) {
            throw new \Exception('订单不存在');
        }

        try {
            DB::beginTransaction();

            $order = $order->toArray();
            unset($where);
            $setField = ['status' => -1, 'trade_time' => time()];
            $upState  =
                Order::where([['status', '<>', -1], ['orders_id', '=', $order_id], ['member_id', '=', $check->id]])
                     ->update($setField);
            if (!$upState) {
                throw new \Exception('订单异常,更改订单状态失败');
            }
            $sell_pid = $this->get_asset_pid($order['l_code']);
            $buy_pid  = $this->get_asset_pid($order['r_code']);
            if ($order['type'] == 1) {
                // 买  币种取 r_code
                if ($order['status'] == 0) {
                    //没有交易的，直接取消退钱
//                    $inc =$orderModel->_assetAct($check, $buy_pid, $order['r_code'], $order['totalprice'] - $order['fee'], 0, '币币交易委托取消', 'Cancellation of token trading commission', 5, 2);
                    $inc =
                        $orderModel->_assetAct($check, $buy_pid, $order['r_code'], $order['totalprice'], 0, '币币交易委托取消', 'Cancellation of token trading commission', 5, 2);
                    if (!$inc) {
                        throw new \Exception('订单异常,委托取消失败');
                    }
                } else {
                    //部分交易的，退部分金额
                    $total_price = $order['wtprice'] * $order['wtnum'];
                    $total_fee   = $total_price * $trans_fee * 0.01;
                    $allcjprice  = $order['cjprice'] * $order['cjnum'];
                    $cjfee       = $allcjprice * $trans_fee * 0.01;
//                    $fhprice_tmp = ($total_price - $allcjprice) - ($total_fee - $cjfee);
                    $fhprice_tmp = ($total_price - $allcjprice) - $cjfee;
                    $fhprice     = number_format($fhprice_tmp, 6, '.', '');
                    $inc         =
                        $orderModel->_assetAct($check, $buy_pid, $order['r_code'], $fhprice, 0, '币币交易委托取消', 'Cancellation of token trading commission', 5, 2);
                    if (!$inc) {
                        throw new \Exception('订单异常,委托取消失败');
                    }
                }
            } else {
                // 卖
                if ($order['status'] == 0) {
                    //没有成交，完全撤单
                    $inc =
                        $orderModel->_assetAct($check, $sell_pid, $order['l_code'], $order['wtnum'], 0, '币币交易撤单', 'Cancellation of currency transactions', 5, 2);
                    if (!$inc) {
                        throw new \Exception('交易撤单失败');

                    }
                } else {
                    $fhnum = $order['wtnum'] - $order['cjnum'];
                    $inc   =
                        $orderModel->_assetAct($check, $sell_pid, $order['l_code'], $fhnum, 0, '币币交易撤单', 'Cancellation of currency transactions', 5, 2);
                    if (!$inc) {
                        throw new \Exception('交易撤单失败');

                    }
                }
            }
            DB::commit();
            return __return($this->successStatus, '委托取消成功');
        } catch (\Exception $exception) {
            DB::rollBack();
            return __return($this->errStatus, $exception->getMessage());
        }
    }

    /**
     * 币币交易记录 get
     */
    public function tranlist(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
//                'code'   => 'required',
'status' => 'required',
            ],
            [
//                'code.required'   => '币对不能为空',
'status.required' => '类型不能为空',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }
        $status    = $request->status;
        $check     = $request->user;
        $code      = $this->coin_cut($request->code);
        $sell_code = $code[0];
        $buy_code  = $code[1];
        $sell_pid  = $this->get_asset_pid($sell_code);
        $buy_pid   = $this->get_asset_pid($buy_code);
        if ($status) {
            if ($status == 3) {
                $whereIn = [Order::WAIT_TRANS, Order::TRANS_ING];
            } elseif ($status == 4) {
                $whereIn = [Order::TRANS_OVER, Order::TRANS_REV];
            } else {
                $whereIn = [$status];
            }
        }
        $map['member_id'] = $check->id;
        $list             = Order::where($map)
                                 ->where('status', '<>', '-1')
                                 ->whereIn('status', $whereIn)
//                                 ->where('currency_id', $sell_pid)
                                 ->orderBy('orders_id', 'desc')->paginate(10);
        if ($list) {
            foreach ($list as $k => $v) {
                if ($v['status'] == Order::TRANS_REV) {
                    $list[$k]['totalprice'] = round($v['cjnum'] * $v['cjprice'], 6);
                }
            }
        }
        return __return($this->successStatus, '获取成功', $list);
    }

    /**
     * 交易币种信息
     */
    public function get_pro()
    {
        $list = Product::select('pid', 'image', 'pname', 'code', 'type')->where('state', 1)->get();
        if ($list) {
            return __return($this->successStatus, '获取成功', $list);
        } else {
            return __return($this->errStatus, '获取信息失败');
        }
    }

    /**
     * 各币种详情
     */
    public function getCodeBalance(Request $request)
    {
        $check = $request->user;
        $data  = $request->all();

        $currency = $this->getCurrencyByCurrencyId($data['code']);
        if (!$currency) {
            return __return($this->errStatus, '未找到该币种');
        }

        $code       = $this->coin_cut($data['code']);
        $sell_code  = $code[0];
        $buy_code   = $code[1];
        $sell_pid   = $this->get_asset_pid($sell_code);
        $buy_pid    = $this->get_asset_pid($buy_code);
        $left_wall  = UserAssets::getBalance($check->id, $sell_pid, 2);
        $right_wall = UserAssets::getBalance($check->id, $buy_pid, 2);
        if ($right_wall) {
            $trans_fee_bb = config('site.trans_fee');
            unset($result);
            $result['left_code']  = $left_wall ? $left_wall['balance'] : 0;
            $result['right_code'] = $right_wall['balance'];
            $result['trans_fee']  = $trans_fee_bb * 0.01;
            $rate_obj             = json_decode(Redis::get('vb:indexTickerAll:usd2cny'));
            $result['rate_usdt']  = $rate_obj->USDT ? $rate_obj->USDT : 0;
            $result['buy_state']  = $currency['buy_state'];
            $result['sell_state'] = $currency['sell_state'];

            return __return($this->successStatus, '获取成功', $result);
        } else {
            return __return($this->errStatus, '获取失败');
        }
    }

    /**
     * 交易明细
     */
    public function tranLists(Request $request)
    {
        $check = $request->user;

        if (empty($check)) {
            return __return($this->errStatus, '用户信息为空');
        }
        $code  = $request->get('code', 0);
        $type  = $request->get('type', '');
        $otype = $request->get('otype', '');
        $page  = $request->get('page');
        $page  = $page > 1 ? (int)$page : 1;
        unset($map);
        $map = [];
        if ($code != '') {
            $currency = $this->getCurrencyByCurrencyId($code);
            if (!$currency) {
                return __return($this->errStatus, '未找到该币种');
            }
            $map['xy_orders.currency_id'] = $currency['pid'];
        }

        if ($type != '') {
            $map['xy_orders.type'] = $type;
        }
        if ($otype != '') {
            $map['xy_orders.otype'] = $otype;
        }
        $uid                   = $check->id;
        $count                 = Order::where($map)
                                      ->where([
                                          'xy_orders.member_id' => $uid,
                                      ])
                                      ->leftjoin('xy_order_ext as a', 'a.o_oid', '=', 'xy_orders.orders_id')
                                      ->join('wallet_code', 'wallet_code.code', '=', 'xy_orders.pname')
                                      ->count();
        $list                  = Order::where($map)
                                      ->where([
                                          'xy_orders.member_id' => $uid, 'xy_orders.status' => $uid
                                      ])
                                      ->leftjoin('xy_order_ext as a', 'a.o_oid', '=', 'xy_orders.orders_id')
                                      ->join('wallet_code', 'wallet_code.code', '=', 'xy_orders.pname')
                                      ->orderBy('xy_orders.orders_id', 'desc')
                                      ->offset(($page - 1) * 10)
                                      ->limit(10)
                                      ->get();
        $myset['trans_fee_bb'] = config('bb_trans.trans_fee');
        foreach ($list as $k => $v) {
            $cj_totalprice             = number_format($v['cjprice'] * $v['cjnum'], 8, '.', '');
            $list[$k]['cj_totalprice'] = $cj_totalprice;
            $list[$k]['cjnum']         = $list[$k]['b_cjnum'];
            $trans_fee_rate            = Config('trans_fee_bb') * 0.01;
            $list[$k]['fee']           = number_format($cj_totalprice * $trans_fee_rate, 8, '.', '');
        }
        $data['count'] = $count;
        $data['res']   = $list;
        return __return($this->successStatus, '获取成功', $data);

    }

    /**
     * 获取币种信息 交易对
     * @param $currency_id
     * @return mixed
     */
    protected function getCurrencyByCurrencyId($currency_id)
    {
        //查询币种
        $info = Product::where('code', $currency_id)
                       ->first();
        if (!$info) {
            return false;
        }
        $info->toArray();
        return $info ?? false;
    }

    /**
     * 添加订单信息
     * @param $user
     * @param $wtprice
     * @param $wtprice1
     * @param $wtnum
     * @param $totalprice
     * @param $fee
     * @param $type
     * @param $otype
     * @param $currency
     * @param $l_code
     * @param $r_code
     * @return bool
     */
    private function insertOrder($user, $wtprice, $wtprice1, $wtnum, $totalprice, $fee, $type, $otype, $currency,
        $l_code, $r_code, $is_first)
    {
        $info = Order::create([
            'member_id'   => $user->id,
            'account'     => $user->account,
            'currency_id' => $currency['pid'],
            'pname'       => $currency['pname'],
            'wtprice'     => $wtprice,
            'wtprice1'    => $wtprice1,
            'wtnum'       => $wtnum,
            'totalprice'  => $totalprice,
            'fee'         => $fee,
            'type'        => $type,
            'otype'       => $otype,
            'add_time'    => time(),
            'l_code'      => $l_code,
            'r_code'      => $r_code,
            'is_first'    => $is_first,
        ]);
        if ($info) {
            $update = DB::table('xy_orid')->where('id', 1)->update(['oid' => $info->orders_id]);
            return $update ?? false;
        } else {
            return false;
        }
    }


    /**
     * 获取资产的pid
     * @param $code
     * @return mixed
     */
    function get_asset_pid($code)
    {
        return WalletCode::codeGetPid($code);
    }

    /**
     * 币种截取
     * @param $str
     * @return array|bool|string
     */
    function coin_cut($str)
    {
        $arr = explode('_', $str);
        return $arr;
    }

}
