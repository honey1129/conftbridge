<?php

namespace App\Http\Controllers\Api;

use App\Models\AssetRelease;
use App\Service\ImageService;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Http\Traits\WriteUserMoneyLog;
use App\Models\Products;
use App\Models\UserAssets;
use App\Models\UserEntrusts;
use App\Models\UserPositions;
use App\Models\UserTrans;
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


class ContractController extends Controller
{
    use WriteUserMoneyLog;

    protected $title = '合约交易控制器';

    /**
     * 下单
     * @param Request $request
     * @return array
     */
    function createOrder(Request $request)
    {
        // 合约交易状态 1为开放 2为关闭
        $status = config('trans.status');
        if ($status == 2) {
            return __return($this->errStatus, '交易系统维护中');
        }
        $userInfo  = $request->user;
        $validator = Validator::make(
            $request->all(),
            [
                'newprice' => ['required', 'regex:/^[+]{0,1}(\d+)$|^[+]{0,1}(\d+\.\d+)$/'], //最新价格  正数（包括小数）
                'buynum'   => ['required', 'regex:/^[+]{0,1}(\d+)$|^[+]{0,1}(\d+\.\d+)$/'], //买入张数  正数(包括小数)
                // 'zy'       => ['required', 'sometimes', 'regex:/^[+]{0,1}(\d+)$|^[+]{0,1}(\d+\.\d+)$/'], //正数（包括小数）
                //'zs'       => ['required', 'sometimes', 'regex:/^[+]{0,1}(\d+)$|^[+]{0,1}(\d+\.\d+)$/'], //正数（包括小数）
                'type'     => ['required', Rule::in([1, 2])], // 1市价 2 限价
                'otype'    => ['required', Rule::in([1, 2])], // 1涨 2跌
                'code'     => 'required', // 产品名称
                'leverage' => 'required', // 产品杠杆
            ],
            [
                'newprice.regex'    => '最新价格格式错误',
                'buynum.regex'      => '买入数量格式错误',
                //                'buynum.integer'      => '买入数量格式错误',
                //                'zy.regex'          => '止盈价格格式错误',
                //                'zs.regex'          => '止损价格格式错误',
                'newprice.required' => '最新价格必须',
                'buynum.required'   => '买入数量必须',
                'type.required'     => '买入类型必须',
                'otype.required'    => '买入方向必须',
                'code.required'     => '产品名称必须',
                'leverage.required' => '杠杆必须',
            ]

        );

        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }
        $input = $request->input();

        $newprice = $input['newprice'];                     //最新价格
        $type     = $input['type'];                         //1市价 2 限价
        $otype    = $input['otype'];                        //1涨 2跌
        $sheets   = $input['buynum'];                       //买入张数
        $code     = $input['code'];                         //产品名称
        $zy_price = isset($input['zy']) ? $input['zy'] : 0; //止盈
        $zs_price = isset($input['zs']) ? $input['zs'] : 0; //止损
        $leverage = $input['leverage'];                     // 产品杠杆

        $pinfo = Products::where(['state' => 1, 'code' => $code])
                         ->select('pid', 'pname', 'code', 'sheets_rate', 'leverage', 'min_order',
                             'max_order', 'max_chicang', 'spread', 'var_price')
                         ->first();

        if (empty($pinfo)) {
            return __return($this->errStatus, '该币种暂未发布');
        }
        $buynum = $sheets;
        if ($buynum <= 0) {
            return __return($this->errStatus, '下单失败');
        }
        $price_json = Redis::get('vb:ticker:newprice:' . $pinfo->code);
        if (!$price_json) {
            return __return($this->errStatus, '网络价格异常');
        }
        $actprice = number_format($price_json, 4, '.', '');

        if ($actprice <= 0) {
            return __return($this->errStatus, '网络价格异常');
        }
        //限价点差
//        $spread = $pinfo->var_price * $pinfo->spread;
        $spread = $pinfo->spread;
        if ($type == 1) {  //市价
            if ($otype == 1) {
                $newprice = bcadd($actprice, $spread, 6);
            } else {
                $newprice = bcsub($actprice, $spread, 6);
            }
        }
        if ($actprice <= 0) {
            return __return($this->errStatus, '网络价格异常');
        }
        if ($zy_price != 0 && $zs_price != 0) {
            if ($zy_price == $zs_price) {
                return __return($this->errStatus, '止盈价格不能等于止损价格');
            }
        }

        // 1 做多  2做空   做多时：止损不能高于现价，止盈不能低于现价。做空时：止损不能低于现价，止盈不能高于现价
        if ($otype == 1) {
            if ($zy_price != 0 && $zy_price < $actprice) {
                return __return($this->errStatus, '止盈价格必须大于现价');
            }
            if ($zs_price != 0 && $zs_price > $actprice) {
                return __return($this->errStatus, '止损价格必须小于现价');
            }
            if ($actprice < $newprice && $type != 1) {
                $newprice = $actprice;
//                return __return($this->errStatus, '下单价格不能大于当前价格');
            }
        } else {
            if ($zy_price != 0 && $zy_price > $actprice) {
                return __return($this->errStatus, '止盈价格必须小于现价');
            }
            if ($zs_price != 0 && $zs_price < $actprice) {
                return __return($this->errStatus, '止损价格必须大于现价');
            }
            if ($actprice > $newprice && $type != 1) {
                $newprice = $actprice;
//                return __return($this->errStatus, '下单价格不能小于当前价格');
            }
        }
        $newprice  = format_price($newprice, $code);
        $trans_fee = config('site.hey_fee', 0);//手续费比例
        DB::beginTransaction();
        try {
            $flag = true;
            // asset lock  锁
            $userBalance = UserAssets::getBalance($userInfo->id, 8, 3, true);
            $zj          = format_price(($newprice * $buynum) / $leverage);      //总金
//            $zj          = format_price($newprice * $buynum);      //总金
            $sxfee = format_price($newprice * $buynum * $trans_fee * 0.01);      //手续费
//            $sxfee = format_price($zj * $trans_fee * 0.01);                 //手续费
            $total = bcadd($zj, $sxfee, 6);
            if ($userBalance->balance < $total) {
                // DB::rollBack();
                return __return($this->errStatus, '合约账号资金不足'); // 资金不足
            }
            $info = [
                'uid'          => $userInfo->id,
                'name'         => $pinfo->pname,
                'code'         => $pinfo->code,
                'buyprice'     => $newprice,
                'sheets'       => $sheets,
                'buynum'       => $buynum,
                'totalprice'   => $zj,
                'leverage'     => $leverage,
                'from'         => $type,
                'otype'        => $otype,
                'stopwin'      => $zy_price,
                'stoploss'     => $zs_price,
                'created_at'   => now(),
                'fee'          => $sxfee,
                'spread'       => $spread,
                'market_price' => $actprice,
            ];

            //如果下限价单  价格和最新价格相等就转为市价单  1市价 2 限价
            /*if ($type == 2 && $newprice == $actprice) {
                $type = 1;
                //加上点差
                if ($otype == 1) {
                    $newprice_to_new = $newprice + $spread;
                } else {
                    $newprice_to_new = $newprice - $spread;
                }
                $info['buyprice'] = $newprice_to_new;
            }*/
            if ($type == 2) {
                if ($otype == 1 && $newprice >= $actprice) {
                    $newprice_to_new  = $newprice;
                    $info['buyprice'] = $newprice_to_new;
                }
                if ($otype == 2 && $newprice <= $actprice) {
                    $newprice_to_new  = $newprice;
                    $info['buyprice'] = $newprice_to_new;
                }
            }
//            //市价单
            if ($type == 1) {
                //查询是否存在同用户ID同产品name同杠杠倍数产品
                $u_p_first = UserPositions::where('uid', $info['uid'])
                                          ->where('code', $info['code'])
                                          ->where('otype', $info['otype'])
                                          ->where('leverage', $info['leverage'])
                                          ->first();
                if (!empty($u_p_first)) {
                    //更新持仓mysql
                    //买入数量
                    $buynum = $u_p_first->buynum;
                    //持仓数量
                    $sheets = $u_p_first->sheets;
                    //持仓价格
                    $buyprice = $u_p_first->buyprice;
                    //持仓总计金额
                    $totalprice = $u_p_first->totalprice;
                    //本次买入数量
                    $x_buynum = $info['buynum'];
                    //本次数量
                    $x_sheets = $info['sheets'];
                    //本次价格
                    $x_buyprice = $info['buyprice'];
                    //本次订单总金额
                    $x_totalprice = $info['totalprice'];
                    //更新后总买入数量
                    $model_buynum = bcadd($buynum, $x_buynum, 6);
                    //更新后总数量
                    $model_sheets = bcadd($sheets, $x_sheets, 6);
                    //更新后持仓价格
                    $model_buyprice =
                        bcdiv(bcadd(bcmul($sheets, $buyprice, 6), bcmul($x_sheets, $x_buyprice, 6), 6), $model_sheets, 6);
                    //更新后订单总金额
                    $model_totalprice = bcadd($totalprice, $x_totalprice, 6);
                    //手续费
                    $model_fee = bcadd($u_p_first->fee, $info['fee'], 6);
                    $save      = UserPositions::where('id', $u_p_first->id)
                                              ->update(
                                                  [
                                                      'buynum'     => $model_buynum,
                                                      'sheets'     => $model_sheets,
                                                      'buyprice'   => $model_buyprice,
                                                      'totalprice' => $model_totalprice,
                                                      'fee'        => $model_fee
                                                  ]
                                              );
                    $order_id  = $u_p_first->id;
                } else {
                    //入MySQL
                    $position           = UserPositions::create($info);
                    $order_id           = $position->id;
                    $position->hold_num = $position->createSN();
                    $save               = $position->save();
                }
            }
            //统一修改为限价单
            //限价单
            if ($type == 2) {
                //入MySQL
                $entrusts         = UserEntrusts::create($info);
                $order_id         = $entrusts->id;
                $entrusts->en_num = $entrusts->createSN();
                $save             = $entrusts->save();

            }
            // 市价买入   最新价
            // 限价进持仓 最新价
            // 限价进委托 下单价
            if (!$save) {
                $flag = false;
            }
            if ($flag && $sxfee > 0) {
                $dec_fee =
                    $this->writeBalanceLog($userBalance, $order_id, -$sxfee, 35, '合约交易手续费', 'Contract transaction fee', 8, 'USDT', 3);
                if (!$dec_fee) {
                    $flag = false;
                }
            }
            // 本金财务流水
            if ($flag && $zj > 0) {
                $dec_wallone =
                    $this->writeBalanceLog($userBalance, $order_id, -$zj, 36, '合约交易扣款', 'Contract transaction deduction', 8, 'USDT', 3);
                if (!$dec_wallone) {
                    $flag = false;
                }
            }
            if (($sxfee > 0) && ($type == 1)) {
                $data = $this->updateTeamAward($userInfo->id, $order_id, $sxfee, 3);
                if ($data['code'] != 200) {
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
            return __return($this->errStatus, '下单失败', [$exception->getMessage()]); // 下单失败
        }

    }

    /**
     * 持仓单设置止盈止损
     * @param Request $request
     * @return array
     */
    public function setPoit(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'hold_id' => 'required',
                //                'zy'      => ['sometimes', 'regex:/^[+]{0,1}(\d+)$|^[+]{0,1}(\d+\.\d+)$/'], //正数（包括小数）
                //                'zs'      => ['sometimes', 'regex:/^[+]{0,1}(\d+)$|^[+]{0,1}(\d+\.\d+)$/'], //正数（包括小数）
            ],
            [
                'hold_id.required' => '缺少持仓订单',
                //                'zy.required'      => '止盈价格必须',
                //                'zs.required'      => '止损价格必须',
            ]
        );

        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }
        $input    = $request->input();
        $zy_price = 0;
        if (isset($input['zy'])) {
            $zy_price = $input['zy']; //止盈
        }
        $zs_price = 0;
        if (isset($input['zs'])) {
            $zs_price = $input['zs']; //止损
        }
        if ($zy_price == 0 && $zs_price == 0) {
            return __return($this->errStatus, '请设置止盈止损');
        }
        $hold_id   = $input['hold_id']; //持仓订单
        $userInfo  = $request->user;
        $hold_data = UserPositions::where('uid', $userInfo->id)
                                  ->where('id', $hold_id)
                                  ->first();

        if (!$hold_data) {
            return __return($this->errStatus, '订单不存在，请刷新列表');
        }

        $newprice = Redis::get('vb:ticker:newprice:' . $hold_data['code']);
        if (!$newprice) {
            return __return($this->errStatus, '网络异常');
        }
//        做多时：止损不能高于现价，止盈不能低于现价。做空时：止损不能低于现价，止盈不能高于现价
        // 1 做多  2做空
        if ($hold_data['otype'] == 1) {
            if ($zy_price != 0 && $zy_price < $newprice) {
                return __return($this->errStatus, '止盈价格必须大于实时价');
            }
            if ($zs_price != 0 && $zs_price > $newprice) {
                return __return($this->errStatus, '止损价格必须小于实时价');
            }
        } else {
            if ($zy_price != 0 && $zy_price > $newprice) {
                return __return($this->errStatus, '止盈价格必须小于实时价');
            }
            if ($zs_price != 0 && $zs_price < $newprice) {
                return __return($this->errStatus, '止损价格必须大于实时价');
            }
        }
        $model_arr = [];
        if ($zy_price != 0) {
            $model_arr['stopwin'] = $zy_price;
        }
        if ($zs_price != 0) {
            $model_arr['stoploss'] = $zs_price;
        }
        $res = UserPositions::where('uid', $userInfo->id)
                            ->where('id', $hold_id)
                            ->update($model_arr);
        if ($res !== false) {
            return __return($this->successStatus, '设置成功');
        } else {
            return __return($this->errStatus, '设置失败');
        }
    }

    /**
     * 撤单接口
     * @param Request $request
     * @return array
     */
    public function cancellations(Request $request)
    {

        if (!$request->order_id) {
            return __return($this->errStatus, '订单不存在，请刷新列表');
        }
        $userInfo = $request->user;
        $where    = [
            'uid'    => $userInfo->id,
            'id'     => $request->order_id,
            'status' => 1
        ];
        $order    = UserEntrusts::where($where)->first();
        if (!$order) {
            return __return($this->errStatus, '订单不存在，请刷新列表');
        }
        DB::beginTransaction();
        try {
            //  查询余额 并上锁
            $assets = UserAssets::getBalance($userInfo->id, 8, 3, true);
            $flag   = true;
            $update = UserEntrusts::where($where)->update(['status' => 3]);
            if (!$update) {
                $flag = false;
            }
            if ($flag) {
                $money = $order['totalprice'] + $order['fee'];
                if ($money > 0) {
                    // 财务流水
                    $inc =
                        $this->writeBalanceLog($assets, $order->id, $money, 3, '合约交易撤单', 'Contract transaction cancellation', 8, 'USDT', 3);
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
            Log::info($e->getMessage() . $e->getLine());
            return __return($this->errStatus, '撤单失败');
        }

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
                'order_id' => 'required',
                'sheets'   => 'required',
            ],
            [
                'order_id.required' => '订单不存在',
                'sheets.required'   => '请输入平仓数',
                //                'sheets.integer'    => '平仓数为整数',
                //                'sheets.min'        => '最小平仓数为1',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }
        $userInfo = $request->user;
        $position = UserPositions::where('uid', $userInfo->id)
                                 ->where('id', $request->order_id)
                                 ->first();
        if (!$position) {
            return __return($this->errStatus, '订单不存在，请刷新列表');
        }
        $product = Products::where('code', $position->code)
                           ->where('state', 1)
                           ->first();

        if (empty($product)) {
            return __return($this->errStatus, '该币种暂未发布，不能平仓');
        }
        if (Carbon::now()->modify('-' . $product->delay_time . ' minutes')->lt($position->created_at)) {
            return __return($this->errStatus, $product->delay_time . 'The position can be closed in minutes');
        }
        $newprice = Redis::get('vb:ticker:newprice:' . $position->code);
        if (!$newprice) {
            return __return($this->errStatus, '网络价格异常');
        }
        //加减点差
        $spread = $product->spread;
        if ($position->otype == 1) {
            $newprice -= $spread;
        } else {
            $newprice += $spread;
        }
        $pc_sheet = $request->sheets;
        $process  = 'positions_process_sheets';
        if ($pc_sheet >= $position->sheets || $pc_sheet >= $position->buynum) {
            $pc_sheet = 0;
            $process  = 'positions_process';
        }
        $queue_data             = [];
        $queue_data['pc_type']  = 1;
        $queue_data['pc_sheet'] = $pc_sheet;
        $queue_data['price']    = $newprice;
        $queue_data['position'] = $position;
        $queue_data['memo']     = '手动平仓';
        $queue_data['en_memo']  = 'Manually close a position';
        //  进平仓队列处理
        $redis  = Redis::connection('server');
        $taskId = $redis->lpush($process, json_encode($queue_data));
        if ($taskId === false) {
            return __return($this->errStatus, sprintf("订单%s处理失败", $position->hold_num));
        }

        if ($taskId !== false) {
            return __return($this->successStatus, '平仓成功', [$queue_data]);
        } else {
            return __return($this->errStatus, '平仓失败，请重试');
        }
    }

    /**
     * 一键全平仓
     * @param Request $request
     * @return array
     */
    public function allClosePosition(Request $request)
    {
        $userInfo  = $request->user;
        $positions = UserPositions::where(['uid' => $userInfo->id])->get();
        if (!$positions->count()) {
            return __return($this->errStatus, '订单不存在，请刷新列表');
        }

        //  进平仓队列处理
        foreach ($positions as $key => $position) {
            $product = Products::where('code', $position->code)
                               ->where('state', 1)
                               ->first();

            if (empty($product)) {
                continue;
            }
            if (Carbon::now()->modify('-' . $product->delay_time . ' minutes')->lt($position->created_at)) {
                continue;
            }
            $newprice = Redis::get('vb:ticker:newprice:' . $position->code);
            if (!$newprice) {
                return __return($this->errStatus, '网络价格异常');
            }
            //加减点差
            $spread = $product->spread;
            if ($position->otype == 1) {
                $newprice -= $spread;
            } else {
                $newprice += $spread;
            }
            $queue_data['pc_type']  = 1;
            $queue_data['pc_sheet'] = 0;
            $queue_data['price']    = $newprice;
            $queue_data['position'] = $position;
            $queue_data['memo']     = '手动平仓';
            $queue_data['en_memo']  = 'Manually close a position';
            $redis                  = Redis::connection('server');
            $tid                    = $redis->lpush('positions_process', json_encode($queue_data));
            if ($tid === false) {
                return __return($this->errStatus, sprintf("订单%s处理失败", $position->hold_num));
            }
        }

        return __return($this->successStatus, '平仓成功');
    }

    /**
     * 持仓/委托 数据接口
     * @param Request $request
     * @return array
     */
    public function transData(Request $request)
    {
        $data_type  = $request->get('data_type', 1);  // 1 持仓  2 委托
        $code       = $request->get('code', '');
        $start_time = $request->get('start_time', '');
        $end_time   = $request->get('end_time', '');
        if ($data_type == 1) {
            $model = new UserPositions;
        } else {
            $model = UserEntrusts::where('status', UserEntrusts::STATE_ING);
        }
        if ($start_time != '' && $end_time != '') {
            $model = $model->whereBetween('created_at', [$start_time, $end_time]);
        }
        $userInfo = $request->user;
//        if ($code != '') {
//            $model = $model->where('code', $code);
//        }
        $hold_data = $model->where('uid', $userInfo->id)
                           ->orderBy('id', 'desc')
                           ->get();
        if (empty($hold_data)) {
            return __return($this->successStatus, '数据为空');
        }
        foreach ($hold_data as &$item) {
            $newprice = Redis::get('vb:ticker:newprice:' . $item->code);
            if ($item->otype == 1) {
                $profit = number_format(($newprice - $item->buyprice) * $item->buynum, 4, '.', '');
            } else {
                $profit = number_format(($item->buyprice - $newprice) * $item->buynum, 4, '.', '');
            }
            $item->floating = $profit;
            $item->newprice = $newprice;
            $item->deposit  = $item->totalprice;
        }
        unset($item);

        return __return($this->successStatus, '获取成功', ['data' => $hold_data]);
    }

    /**
     * 获取用户订单列表
     * @param Request $request
     * @return array
     */
    public function orderList(Request $request)
    {
        $code       = $request->get('code', '');
        $start_time = $request->get('start_time', '');
        $end_time   = $request->get('end_time', '');
        $user       = $request->user;
        $query      = UserTrans::query();
        if ($code != '') {
            $query->where('code', $code);
        }
        if ($start_time != '' && $end_time != '') {
            $query->whereBetween('created_at', [$start_time, $end_time]);
        }

        $trans = $query->where('uid', $user->id)
                       ->orderBy('id', 'desc')
                       ->paginate(10);

        return __return($this->successStatus, '获取成功', $trans);
    }

    /**
     * 持仓信息统计
     * @param Request $request
     * @return array
     */
    protected function statistics(Request $request)
    {
        $userInfo = $request->user;
        $assets   = UserAssets::getBalance($userInfo->id, 8, 3);
        //可用资金
        $keyong_price = $assets->balance;
        //冻结保证金
        $totaldeposit = $this->getDeposit($userInfo->id);
        //浮动盈亏
        $totalmoneys = $this->getProfit($userInfo->id);
        //平仓盈亏     平仓表的盈亏字段总和
        $trans_profit = $this->getTransProfit($userInfo->id);
        //动态权益
        $totalusdt = $totalmoneys + $keyong_price + $totaldeposit;
        //风险率 只统计持仓单的冻结保证金
        if ($totaldeposit <= 0) {
            $risk = 0 . '%';
        } else {
            $risk = number_format(($totalusdt / $totaldeposit) * 100, 2, '.', '') . '%';
        }
        $data                 = [];
        $data['keyong_price'] = $keyong_price;              //可用资金
        $data['totaldeposit'] = $totaldeposit;              //冻结保证金
        $data['yingkui']      = $totalmoneys;               //浮动盈亏
        $data['totalusdt']    = $totalusdt;                 //动态权益
        $data['pingcang']     = $trans_profit;              //平仓盈亏
        $data['risk']         = $totaldeposit ? $risk : 0;  //爆仓率

        return __return($this->successStatus, '获取成功', $data);
    }

    /**
     * 个人冻结保证金
     * @param $userid
     * @return mixed
     */
    protected function getDeposit($userid)
    {
        // 持仓单保证金
        return UserPositions::where('uid', $userid)
                            ->sum('totalprice') + 0;
    }

    /**
     * 浮动盈亏
     * @param $userid
     * @return float
     */
    protected function getProfit($userid)
    {
        $data = UserPositions::where('uid', $userid)
                             ->select('code', 'buynum', 'buyprice', 'otype')
                             ->get();

        $totalmoneys = 0;
        foreach ($data as $k => $v) {
            $newprice = Redis::get('vb:ticker:newprice:' . $v['code']);
            if ($v['otype'] == 1) {
                $yingkui = ($newprice - $v['buyprice']) * $v['buynum'];
            } else {
                $yingkui = ($v['buyprice'] - $newprice) * $v['buynum'];
            }
            $totalmoneys += $yingkui;
        }
        return round($totalmoneys, 4);
    }

    /**
     * 平仓盈亏
     * @param $userid
     * @return mixed
     */
    protected function getTransProfit($userid)
    {
        return UserTrans::where('uid', $userid)
                        ->sum('profit') + 0;
    }


    //获取首页信息
    public function getProduct(Request $request)
    {
        $user = $request->user;
        if (empty($request->code)) {
            return __return($this->errStatus, '缺少产品名称');
        }

        $product                  = Products::where('code', $request->code)
                                            ->select('leverage', 'position_select', 'var_price', 'spread', 'min_order', 'max_order', 'sheets_rate')
                                            ->first();
        $product->leverage        = explode(',', $product->leverage);
        $product->position_select = explode(',', $product->position_select);
        //冻结保证金
        $totaldeposit = $this->getDeposit($user->id);
        //浮动盈亏
        $totalmoneys = $this->getProfit($user->id);
        $assets      = UserAssets::getBalance($user->id, 8, 3);
        //可用资金
        $keyong_price = $assets->balance;
        // 动态权益
        $totalusdt = $totalmoneys + $keyong_price + $totaldeposit;
        //风险率
        if ($totalusdt <= 0) {
            $risk = 0 . '%';
        } else {
            $risk = number_format(($totaldeposit / $totalusdt) * 100, 2, '.', '') . '%';
        }
        $return['product']      = $product;
        $return['handling_fee'] = config('site.hey_fee') . '%';
        $return['balance']      = $keyong_price;
        $return['risk']         = $risk;
        $return['bc_rate']      = config('site.bc_rate') . '%';
        return __return($this->successStatus, '获取成功', $return);
    }


    /**
     * 后台平仓
     * @param Request $request
     * @return array
     */
    public function sysClosePosition(Request $request)
    {
        //安全验证 安全码

        if ($request->auth != sha1('ahsd98y12hqhda%*')) {
            return __return($this->errStatus, '非法请求');
        }
        if (!$request->order_id) {
            return __return($this->errStatus, '订单不存在');
        }
        $position = UserPositions::where(['uid' => $request->uid, 'id' => $request->order_id])
                                 ->first();
        if (!$position) {
            return __return($this->errStatus, '订单不存在，请刷新列表');
        }
        $product = Products::where('code', $position->code)
                           ->where('state', 1)
                           ->first();
        if (empty($product)) {
            return __return($this->errStatus, '该币种暂未发布，不能平仓');
        }
        $newprice = Redis::get('vb:ticker:newprice:' . $position->code);
        if (!$newprice) {
            return __return($this->errStatus, '网络价格异常');
        }
        //加减点差
        $spread = $product->spread;
        if ($position->otype == 1) {
            $newprice -= $spread;
        } else {
            $newprice += $spread;
        }
        $queue_data['pc_type']  = 1;
        $queue_data['pc_sheet'] = 0;
        $queue_data['price']    = $newprice;
        $queue_data['position'] = $position;
        $queue_data['memo']     = '手动平仓';
        //  进平仓队列处理
        $server = Redis::connection('server');
        $taskId = $server->lpush('positions_process', json_encode($queue_data));
        if ($taskId === false) {
            return __return($this->errStatus, sprintf("订单%s处理失败", $position->hold_num));
        }
        if ($taskId >= 0) {
            return __return($this->successStatus, '平仓成功');
        } else {
            return __return($this->errStatus, '平仓失败，请重试');
        }
    }

    public function shareOrder(Request $request)
    {
        $code     = $request->type;
        $user     = $request->user;
        $position = DB::table('user_trans')
                      ->where('uid', $user->id)
                      ->where('id', $request->order_id)
                      ->first();

        if (empty($position)) {
            return __return($this->errStatus, '订单不存在');
        }

        $url    = config('app.url') . '/web/reg/index.html';
        $querys = '';
        $querys .= '?recommend=' . $user->account;
        $disk   = 'oss';
        //判断图片是否存在 存在直接获取
        if ($code == 1) {
            $fileName = 'images/share/' . $user->account . $position->id . 'zh.jpg';
        } elseif ($code == 2) {
            $fileName = 'images/share/' . $user->account . $position->id . 'en.jpg';
        } else {
            $fileName = 'images/share/' . $user->account . $position->id . 'jt_en.jpg';
        }
        $qrcode = ImageService::fullUrl($fileName);
//        try{
//            if(file_get_contents($qrcode)){
//                return __return($this->successStatus, '获取成功',$qrcode);
//            }
//        } catch(\Exception $e){
//
//        }
        if (!Storage::exists('images/share')) {
            Storage::disk()->makeDirectory('images/share');
        }
        $png = QrCode::format('png')->size(120)->margin(0)
                     ->generate($url . $querys);
        Storage::disk($disk)->put($fileName, $png);
        if ($code == 1) {
            // 修改指定图片的大小share_en.jpg
            $img           = Image::make(ImageService::fullUrl('/share/share_zh.jpg') )->resize(750, 1634);
            $amount_profit = '盈利金额';
            if ($position->otype == 1) {
                $direction = '做多';
            } else {
                $direction = '做空';
            }
            $contract      = 'USDT合约';
            $quantity      = '数量';
            $closing_price = '平仓价格';

        } else {
            // 修改指定图片的大小
            $img           = Image::make(ImageService::fullUrl('/share/share_en.png') )->resize(750, 1634);
            $amount_profit = 'Profit';
            if ($position->otype == 1) {
                $direction = 'Do more';
            } else {
                $direction = 'Sell short';
            }
            $contract      = 'USDT Contract';
            $quantity      = 'Quantity';
            $closing_price = 'Closing price';

        }
        $img->text($amount_profit, 320, 580, function ($font) use ($request)
        {
            $font->file(base_path() . '/public/fonts/msyhl.ttc');
            $font->size(34);
            $font->color('#747474');
            $font->align('center');
            // $font->valign('top');
            // $font->angle(45);
        });
        if ($position->otype == 1) {
            $img->text($direction, 440, 580, function ($font) use ($request)
            {
                $font->file(base_path() . '/public/fonts/msyhl.ttc');
                $font->size(30);
                $font->color('#F56C6C');
                $font->align('center');
            });
        } else {
            $img->text($direction, 440, 580, function ($font) use ($request)
            {
                $font->file(base_path() . '/public/fonts/msyhl.ttc');
                $font->size(30);
                $font->color('#03C086');
                $font->align('center');
            });
        }

        $img->text(floatval($position->profit) . ' USDT', 370, 650, function ($font) use ($request)
        {
            $font->file(base_path() . '/public/fonts/arial.ttf');
            $font->size(50);
            $font->color('#03C086');
            $font->align('center');
            // $font->valign('top');
            // $font->angle(45);
        });

        $img->text($contract, 150, 750, function ($font) use ($request)
        {
            $font->file(base_path() . '/public/fonts/msyhl.ttc');
            $font->size(26);
            $font->color('#98A1B3');
            $font->align('center');
            // $font->valign('top');
            // $font->angle(45);
        });

        $img->text(str_replace('_', '/', $position->name), 150, 800, function ($font) use ($request)
        {
            $font->file(base_path() . '/public/fonts/arial.ttf');
            $font->size(34);
            $font->color('#363636');
            $font->align('center');
            // $font->valign('top');
            // $font->angle(45);
        });

        $img->text($quantity, 370, 750, function ($font) use ($request)
        {
            $font->file(base_path() . '/public/fonts/msyhl.ttc');
            $font->size(26);
            $font->color('#98A1B3');
            $font->align('center');
            // $font->valign('top');
            // $font->angle(45);
        });

        $img->text(floatval($position->buynum), 370, 800, function ($font) use ($request)
        {
            $font->file(base_path() . '/public/fonts/arial.ttf');
            $font->size(34);
            $font->color('#363636');
            $font->align('center');
            // $font->valign('top');
            // $font->angle(45);
        });

        $img->text($closing_price, 590, 750, function ($font) use ($request)
        {
            $font->file(base_path() . '/public/fonts/msyhl.ttc');
            $font->size(26);
            $font->color('#98A1B3');
            $font->align('center');
            // $font->valign('top');
            // $font->angle(45);
        });

        $img->text(floatval($position->sellprice), 590, 800, function ($font) use ($request)
        {
            $font->file(base_path() . '/public/fonts/arial.ttf');
            $font->size(34);
            $font->color('#363636');
            $font->align('center');
            // $font->valign('top');
            // $font->angle(45);
        });
        // 插入水印, 水印位置在原图片的左下角, 距离下边距 10 像素, 距离右边距 15 像素
        $img->insert($qrcode, 'bottom-right', 5, 5);
        // 将处理后的图片重新保存到其他路径
        $img->save($fileName);
        Storage::disk($disk)->put($fileName, file_get_contents($fileName));
        if (file_exists($fileName)) {
            unlink($fileName);
        }
        return __return($this->successStatus, '获取成功', $qrcode);

    }


}
