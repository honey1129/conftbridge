<?php

namespace App\Http\Controllers\Api;

use App\Jobs\AutoConfirmation;
use App\Models\SmsLog;
use App\Models\UserEntrusts;
use App\Models\UserPositions;
use App\User;
use Carbon\Carbon;
use App\Models\Fbpay;
use App\Models\Fbsell;
use App\Models\Fbtrans;
use App\Models\Fbappeal;
use App\Models\Fbbuying;
use App\Models\UserAssets;
use Illuminate\Http\Request;
use App\Http\Traits\SendSms;
use App\Http\Traits\WriteUserMoneyLog;
use App\Http\Controllers\Controller;
use App\Http\Traits\GoogleAuthenticator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;

class FbTradeController extends Controller
{

    use SendSms, WriteUserMoneyLog, GoogleAuthenticator;

    protected $subscribe_redis;

    public function __construct()
    {
        $this->subscribe_redis = Redis::connection('subscribe');
    }

    /**
     * 交易大厅
     * @param Request $request
     * @return array
     */
    public function trading(Request $request)
    {
        //订单类型 1出售 2购买
        $type = $request->get('type', 1);

        if ($type == 1) {
            $Fbquery = Fbsell::query();
        } else {
            $Fbquery = Fbbuying::query();
        }
        // 求购倒序
        // 出售正序
        if ($type == 1) {
            $Fbquery->orderBy('price', 'asc');
        } else {
            $Fbquery->orderBy('price', 'desc');
        }
        $orders = $Fbquery
            ->with('user')
            ->where('status', 1)
            ->paginate(10);

        foreach ($orders->items() as &$item) {
            $item['user']['username'] = substr_cut($item['user']['username']);
            $item['amount']           = $item['trans_num'] - $item['deals_num'];                 //已交易数量
            $item['rate']             =
                number_format(($item['deals_num'] / $item['trans_num']) * 100, 2, '.', '') . '%';// 完成率
            $item['total_price']      = $item['amount'] * $item['price'];                        //已成交金额

            $t_order = Fbtrans::where('jy_order', $item['order_no'])
                              ->where('status', 3)
                              ->sum('total_num');
            if ($t_order >= $item['trans_num']) {
                if ($type == 1) {
                    Fbsell::where('order_no', $item['order_no'])->update(['status' => 2]);
                } else {
                    Fbbuying::where('order_no', $item['order_no'])->update(['status' => 2]);
                }
            }
        }

        unset($item);

        $str                = 'vb:indexTickerAll:usd2cny';
        $exrate             = json_decode($this->subscribe_redis->get($str), true);
        $return['usdt_cny'] = $exrate['USDT'];
//        $return['usdt_cny'] = 7.06;
        $return['orders'] = $orders;
        $return['type']   = $type;

        return __return($this->successStatus, '获取成功', $return);
    }


    /**
     * 下单  post
     * @param user->account
     * @param  $data
     * @return array
     */
    public function createOrder(Request $request)
    {
        $user = $request->user;

        if ($user->fbtrans) {
            return __return($this->errStatus, '您的交易功能已冻结');
        }

        $validator = Validator::make(
            $request->all(),
            [
                'type'             => 'required',//订单类型 1出售 2购买
                'total_num'        => 'required|numeric|min:1',
                'total_price'      => 'required|numeric|min:0',
                'order_id'         => 'required|numeric|min:1',
                'payment_password' => 'required',
            ],
            [
                'type.required'             => '请选择正确的订单类型',
                'total_num.required'        => '交易数量必须',
                'total_price.required'      => '交易价格必须',
                'order_id.required'         => '订单号必须',
                'payment_password.required' => '资金密码不能为空',
            ]
        );

        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }
        $hour       = date('H');
        $start_time = config('fabi.start_time');
        $end_time   = config('fabi.end_time');
        if (($hour < $start_time) && ($hour >= $end_time)) {
            return __return($this->errStatus, '当前不是交易时间，请在交易时间内进行交易');
        }
        $cancel_count = Fbtrans::where(function ($query) use ($user)
        {
            $query->where('chu_uid', $user->id)->orWhere('gou_uid', $user->id);
        })
                               ->where('status', Fbtrans::ORDER_CANCEL)
                               ->whereBetween('created_at', [Carbon::today(), Carbon::tomorrow()])
                               ->count();

        if ($cancel_count >= 3) {
            return __return($this->errStatus, '当天撤单次数大于限制，不能下单');
        }

        $post = $request->post();

        //出售单，我要购买，我是买家
        //求购单，我要出售，我是卖家
        $type = $post['type'];
        if (!isset($type)) {
            return __return($this->errStatus, '参数有误');
        }

        if ($type == 1) {
            $m_model = new Fbsell();
        } else {
            $m_model = new Fbbuying();
        }
        if (!Hash::check($post['payment_password'], $user->payment_password)) {
            return __return($this->errStatus, '资金密码输入错误');
        }

        DB::beginTransaction();
        try {
            //查询订单状态只能是未交易结算的或者撤单的
            $chu_order = $m_model::where('id', $post['order_id'])->first();
            if (empty($chu_order)) {
                return __return($this->errStatus, '该订单编号有误');
            }
            if ($chu_order['status'] == 2) {
                return __return($this->errStatus, '该订单数量不足');
            }
            if ($user->id == $chu_order->uid) {
                return __return($this->errStatus, '不能操作自己订单');
            }
            //字符串比较问题
            if ($post['total_num'] > ($chu_order->trans_num - $chu_order->deals_num)) {
                return __return($this->errStatus, '数量不足');
            }

            if ($chu_order->min_price > $post['total_num']) {
                return __return($this->errStatus, '数量不能低于最小限量');
            }

            if ($chu_order->max_price < $post['total_num']) {
                return __return($this->errStatus, '数量不能超过最大限量');
            }

            //成交数量等于交易数量
            if ($chu_order->deals_num == $chu_order->trans_num) {
                return __return($this->errStatus, '该订单已结束');
            }
            $oid = $chu_order->id;
            //获取用户余额   判断用户钱包里的钱是否足够
            $asset = UserAssets::getBalance($user->id, 9, 1, true);
            //获取手续费
            $fee   = config('fabi.fb_fee');
            $sxfee = $post['total_num'] * $fee * 0.01;
            if ($type == 2) {
                if ($asset->balance < $post['total_num'] + $sxfee) {
                    return __return($this->errStatus, '账户余额不足');
                }
            }
            $chu_user = User::find($chu_order->uid);
            if ($type == 1) {
                $status = 3;
                //下单人是买家
                $order_info['chu_uid'] = $chu_order->uid;
                $order_info['gou_uid'] = $user->id;
            } else {
                $status                = 1;
                $order_info['chu_uid'] = $user->id;
                $order_info['gou_uid'] = $chu_order->uid;
            }

            //这里加载单子方的支付方式
            $order_info['jy_order']    = $chu_order->order_no;
            $order_info['price']       = $chu_order->price;
            $order_info['total_num']   = $post['total_num'];
            $order_info['total_price'] = $post['total_num'] * $chu_order->price;
            $order_info['refer']       = mt_rand(1000, 9999);
            $order_info['type']        = $type;
            $order_info['status']      = $status;
            if ($status == 3) {
                $order_info['pay_at']     = date('YmdHis');
                $order_info['checked_at'] = date('YmdHis');
            }
            $order_info['min_price'] = $chu_order->min_price;
            $order_info['max_price'] = $chu_order->max_price;
            $order_info['sxfee']     = $sxfee;
            //订单数据
            $Fbtrans = Fbtrans::create($order_info);
            if ($Fbtrans) {
                $Fbtrans->order_no = 'FBT' . date('YmdHis') . $Fbtrans->id . rand(1000, 9999);
                $Fbtrans->save();
                //增加成交数量
                $map              = array();
                $map['deals_num'] = $order_info['total_num'] + $chu_order['deals_num'];
                if ($map['deals_num'] == $chu_order['trans_num']) {
                    $map['status'] = 2;
                }
                $inc = $m_model->where('id', $oid)->update($map);
                if (!$inc) {
                    DB::rollBack();
                    return __return($this->errStatus, '更新订单信息失败');
                }

                if (($order_info['sxfee'] > 0) && ($type == 1)) {
                    //减成交手续费
                    $dec = $m_model->where('id', $oid)->decrement('sxfee', $order_info['sxfee']);
                    if (!$dec) {
                        DB::rollBack();
                        return __return($this->errStatus, '成交手续费扣除失败');
                    }
                }
                //出售下单 扣除自己的钱包余额
                if ($type == 2) {
                    //卖家增加冻结，减少钱包
                    $bool1 = $this->writeBalanceLog($asset, $oid, 1, -$post['total_num'], 41, '出售下单-C20T', 9, 'C20T');
                    $bool2 = $this->writeFrostLog($asset, $oid, 1, $post['total_num'], 43, '法币交易冻结', 9, 'C20T');
                    $bool3 = $this->writeBalanceLog($asset, $oid, 1, -$order_info['sxfee'], 42, '出售下单-手续费', 9, 'C20T');
                    $bool4 = 4;
                } else {
                    $gusdt_asset = UserAssets::getBalance($order_info['gou_uid'], 8, 1, true);
                    //买家减少USDT，增加C20T
                    $bool1 =
                        $this->writeBalanceLog($gusdt_asset, $oid, 1, -$order_info['total_price'], 47, '购买下单-USDT', 8, 'USDT');
                    $bool2 = $this->writeBalanceLog($asset, $oid, 1, $post['total_num'], 49, '购买下单增加C20T', 9, 'C20T');
                    //卖家增加USDT，减少冻结C20T
                    $cusdt_asset = UserAssets::getBalance($order_info['chu_uid'], 8, 1, true);
                    $cc20t_asset = UserAssets::getBalance($order_info['chu_uid'], 9, 1, true);
                    $bool3       =
                        $this->writeBalanceLog($cusdt_asset, $oid, 1, $order_info['total_price'], 48, '出售交易增加USDT', 8, 'USDT');
                    $bool4       =
                        $this->writeFrostLog($cc20t_asset, $oid, 1, -$post['total_num'], 50, '出售下单-冻结', 9, 'C20T');
                }
                if ($bool1 && $bool2 && $bool3 && $bool4) {
                    DB::commit();
                    return __return($this->successStatus, '下单成功');
                } else {
                    DB::rollBack();
                    return __return($this->errStatus, '记录添加失败');
                }
            } else {
                DB::rollBack();
                return __return($this->errStatus, '记录添加失败');
            }
        } catch (\Exception $exception) {
            DB::rollBack();
            return __return($this->errStatus, $exception->getMessage());
        }

    }


    /**
     * 创建法币订单
     * @param Request $request
     * @return array
     */
    public function createTrade(Request $request)
    {
        $user = $request->user;
        if ($user->fbtrans) {
            return __return($this->errStatus, '您的交易功能已冻结');
        }
        $validator = Validator::make(
            $request->all(),
            [
                'type'             => 'required',//订单类型 1出售 2购买
                'trans_num'        => 'required|numeric|min:1',
                'price'            => 'required|numeric|min:0',
                'min_price'        => 'required|numeric|min:0',
                'max_price'        => 'required|numeric|min:0',
                'payment_password' => 'required',
            ],
            [
                'type.required'             => '请选择正确的订单类型',
                'trans_num.required'        => '交易数量必须',
                'price.required'            => '交易价格必须',
                'min_price.required'        => '最低交易金额不能为空',
                'max_price.required'        => '最高交易金额不能为空',
                'payment_password.required' => '资金密码不能为空',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }

        $hour       = date('H');
        $start_time = config('fabi.start_time');
        $end_time   = config('fabi.end_time');
        $fb_fee     = config('fabi.fb_fee');
        if (($hour < $start_time) && ($hour >= $end_time)) {
            return __return($this->errStatus, '当前不是交易时间，请在交易时间内进行交易');
        }
        if ($request->trans_num < $request->max_price) {
            return __return($this->errStatus, 'The maximum limit cannot be exceeded' . $request->trans_num);
        }
        if ($request->min_price > $request->max_price) {
            return __return($this->errStatus, '最小限量不能超过最大限量');
        }
        $repeal_num   = config('fabi.repeal_num');
        $cancel_count = Fbtrans::where(function ($query) use ($user)
        {
            $query->where('chu_uid', $user->id)->orWhere('gou_uid', $user->id);
        })
                               ->where('status', Fbtrans::ORDER_CANCEL)
                               ->whereBetween('created_at', [Carbon::today(), Carbon::tomorrow()])
                               ->count();
        if ($cancel_count >= $repeal_num) {
            return __return($this->errStatus, '当天撤单次数大于限制，不能下单');
        }
        DB::beginTransaction();
        try {
            if ($request->type == 1) {
                $m_model      = new Fbsell();
                $order_no     = 'FBS' . time() . $user->id . mt_rand(1000, 9999);
                $sxfee        = $request->trans_num * $fb_fee * 0.01;
                $user_account = UserAssets::getBalance($user->id, 9, 1);
                if ($user_account->balance < $request->trans_num + $sxfee) {
                    return __return($this->errStatus, '当前可交易的C20T不足');
                }

                $rs1 = $this->writeBalanceLog($user_account, 0, 1, -$request->trans_num, 41, '法币交易扣除本金', 9, 'C20T');
                $rs2 = $this->writeBalanceLog($user_account, 0, 1, -$sxfee, 42, '法币交易扣除手续费', 9, 'C20T');
                $rs3 = $this->writeFrostLog($user_account, 0, 1, $request->trans_num, 43, '法币交易冻结', 9, 'C20T');
            } else {
                $m_model  = new Fbbuying();
                $order_no = 'FBB' . time() . $user->id . mt_rand(1000, 9999);
                $sxfee    = 0;
                $rs1      = $rs2 = $rs3 = 1;
            }
            if (!Hash::check($request->payment_password, $user->payment_password)) {
                return __return($this->errStatus, '资金密码输入错误');
            }
            $dev_price = Redis::get('vb:ticker:newprice:c20t_usdt');
            if (empty($dev_price)) {
                return __return($this->errStatus, '获取最新C20T价格信息失败');
            }
            if ($dev_price < $request->price) {
                return __return($this->errStatus, '交易价格不能超出当前C20T的价格');
            }

            $data               = array();
            $data['uid']        = $user->id;
            $data['order_no']   = $order_no;
            $data['trans_num']  = $request->trans_num;
            $data['deals_num']  = 0;
            $data['price']      = $request->price;
            $data['totalprice'] = $request->price * $request->trans_num;
            $data['sxfee']      = $sxfee;
            $data['min_price']  = $request->min_price;
            $data['max_price']  = $request->max_price;
            $data['status']     = 1;
            $data['notes']      = $request->notes;
            $rs4                = $m_model::create($data);

            if ($rs1 && $rs2 && $rs3 && $rs4) {
                DB::commit();
                return __return($this->successStatus, '操作成功');
            } else {
                DB::rollBack();
                return __return($this->errStatus, '操作失败');
            }
        } catch (\Exception $exception) {
            DB::rollBack();
            return __return($this->errStatus, $exception->getMessage());
        }
    }


    /**
     * 取消发布需求
     * @param Request $request
     * @return array
     */
    public function cancelTrade(Request $request)
    {
        $user = $request->user;
        if ($user->fbtrans) {
            return __return($this->errStatus, '您的交易功能已冻结');
        }
        $validator = Validator::make(
            $request->all(),
            [
                'type'             => 'required',//订单类型 1出售 2购买
                'order_id'         => 'required',
                'payment_password' => 'required',
            ],
            [
                'type.required'             => '请选择正确的订单类型',
                'order_id.required'         => '订单ID不能为空',
                'payment_password.required' => '资金密码不能为空',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }

        if (!Hash::check($request->payment_password, $user->payment_password)) {
            return __return($this->errStatus, '资金密码输入错误');
        }
        $dev_price = Redis::get('vb:ticker:newprice:c20t_usdt');
        if (empty($dev_price)) {
            return __return($this->errStatus, '获取最新C20T价格信息失败');
        }
        if ($request->price < 0 || $dev_price < $request->price) {
            return __return($this->errStatus, '交易价格不能超出当前C20T的价格');
        }

        if ($request->type == 1) {
            $m_model = new Fbsell();
        } else {
            $m_model = new Fbbuying();
        }
        $order_info = $m_model::where(['id' => $request->order_id, 'uid' => $user->id])->first();
        if (empty($order_info)) {
            return __return($this->errStatus, '发布信息不存在');
        }
        if ($order_info->status != 1) {
            return __return($this->errStatus, '当前状态不能进行取消');
        }
        if ($order_info->deals_num > 0) {
            return __return($this->errStatus, 'The release information already has a transaction order' . $order_info->deals_num);
        }
        DB::beginTransaction();
        try {
            $res1 = $m_model::where(['id' => $request->order_id, 'uid' => $user->id])->update(['status' => 3]);
            if ($request->type == 1) {
                $user_account = UserAssets::getBalance($user->id, 9, 1);

                $res2 = $this->writeBalanceLog($user_account, 0, 1, $order_info->trans_num, 44, '返回法币交易本金', 9, 'C20T');
                $res3 = $this->writeBalanceLog($user_account, 0, 1, $order_info->sxfee, 45, '返回法币交易手续费', 9, 'C20T');
                $res4 = $this->writeFrostLog($user_account, 0, 1, -$order_info->trans_num, 46, '法币交易冻结', 9, 'C20T');
            } else {
                $res2 = $res3 = $res4 = 1;
            }
            if ($res1 && $res2 && $res3 && $res4) {
                DB::commit();
                return __return($this->successStatus, '操作成功');
            } else {
                DB::rollBack();
                return __return($this->errStatus, '操作失败');
            }
        } catch (\Exception $exception) {
            DB::rollBack();
            return __return($this->errStatus, $exception->getMessage());
        }
    }

    /**
     * 法币交易历史明细 get
     */
    public function myTrade(Request $request)
    {
        $user      = $request->user;
        $validator = Validator::make(
            $request->all(),
            [
                'type' => 'required',//订单类型 1出售 2购买
            ],
            [
                'type.required' => '请选择正确的订单类型',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }
        if ($request->type == 1) {
            $m_model = new Fbsell();
        } else {
            $m_model = new Fbbuying();
        }
        $lists = $m_model::where('uid', $user->id)->whereIn('status', [1, 2])->orderBy('id', 'desc')->paginate(10);
        return __return($this->successStatus, '获取成功', $lists);
    }

    /**
     * 订单详情
     * @param Request $request
     * @return array
     */
    public function orderDetail(Request $request)
    {
        $user = $request->user;

        $order_id = $request->get('order_id');

        $order_info = Fbtrans::where('id', $order_id)->first();

        if (!$order_info) {
            return __return($this->errStatus, '订单信息不存在！');
        }

        try {
            //1出售者 2购买者
            if ($order_info->chu_uid == $user->id) {
                $user             = User::find($order_info->gou_uid);
                $backData['type'] = 1;
            } elseif ($order_info->gou_uid == $user->id) {
                $user             = User::find($order_info->chu_uid);
                $backData['type'] = 2;
            } else {
                return __return($this->errStatus, '详情拉取失败');
            }
            $backData['oop_account'] = $user->account;         //对方编号
            $backData['oop_name']    = substr_cut($user->name);//对方姓名
            $backData['oop_mobile']  = $user->phone;           //对方手机号

            if ($order_info->status == 4) {
                $appeal = Fbappeal::where('order_no', $order_info->order_no)
                                  ->select('pan_reason', 'command')
                                  ->first();
                if ($appeal) {
                    $pan_reason = $appeal->pan_reason;
                    $command    = $appeal->command;
                } else {
                    $command    = '';
                    $pan_reason = '';
                }
            } else {
                $command    = '';
                $pan_reason = '';
            }
            $backData['pan_reason']  = $pan_reason;             //判决原因
            $backData['command']     = $command;                //申诉口令
            $backData['order_no']    = $order_info->order_no;   //订单编号
            $backData['total_num']   = $order_info->total_num;  //总数量
            $backData['price']       = $order_info->price;      //单价
            $backData['total_price'] = $order_info->total_price;//总计
            $backData['refer']       = $order_info->refer;      //付款参考号
            $backData['created_at']  = Carbon::parse($order_info->created_at)->toDateTimeString();
            $backData['status']      = $order_info->status; //1未确认待付款 2已付款 3已确认完成 4 申述中 5取消
            $backData['pay_at']      = $order_info->pay_at; //付款时间

            $fb_time = config('fb.fb_time');//自动取消时间

            $created_at = Carbon::parse($order_info->created_at)->timestamp;

            $qx_time = $created_at + $fb_time * 60 - time();

            if ($qx_time <= 0) {
                $qx_time = 0;
            }
            //自动取消剩余时间
            $backData['down_time'] = $qx_time;
            $qr_time               = config('fb.qr_time');
            $pay_at                = Carbon::parse($order_info->pay_at)->timestamp;

            if ($order_info['status'] == 2) {
                $qr_time = $pay_at + $qr_time * 60 - time();
                if ($qr_time <= 0) {
                    $qr_time = 0;
                }
                $backData['qr_time'] = $qr_time;//自动确认剩余时间
            }

            $pay_list = Fbpay::where('uid', $order_info->chu_uid)->get();

            if ($order_info->type == 1) {
                $notes = Fbsell::where('order_no', $order_info->jy_order)->value('notes');
            } else {
                $notes = Fbbuying::where('order_no', $order_info->jy_order)->value('notes');
            }

            $backData['notes']    = $notes;
            $backData['pay_list'] = $pay_list;

            return __return($this->successStatus, '获取成功', $backData);
        } catch (\Exception $exception) {
            return __return($this->errStatus, $exception->getMessage());
        }
    }


    /**
     * 标记已付款
     * @param Request $request
     * @return array
     */
    public function setOrderStatus(Request $request)
    {
        $user = $request->user;

        $order_id = $request->post('order_id');

        $order = Fbtrans::where('id', $order_id)
                        ->where('gou_uid', $user->id)
                        ->where('status', Fbtrans::ORDER_PENDING)
                        ->first();

        if (!$order) {
            return __return($this->errStatus, '该订单不存在');
        }

        DB::beginTransaction();
        try {
            $order->status     = Fbtrans::ORDER_OVER;
            $order->pay_at     = now();
            $order->checked_at = now();
            $res1              = $order->save();

            $gusdt_asset = UserAssets::getBalance($order->gou_uid, 8, 1, true);
            $gc20t_asset = UserAssets::getBalance($order->gou_uid, 9, 1, true);
            //买家减少USDT，增加C20T
            $res2 =
                $this->writeBalanceLog($gusdt_asset, $order->id, 1, -$order->total_price, 47, '购买下单-USDT', 8, 'USDT');
            $res3 = $this->writeBalanceLog($gc20t_asset, $order->id, 1, $order->total_num, 49, '购买下单增加C20T', 9, 'C20T');
            //卖家增加USDT，减少冻结C20T
            $cusdt_asset = UserAssets::getBalance($order->chu_uid, 8, 1, true);
            $cc20t_asset = UserAssets::getBalance($order->chu_uid, 9, 1, true);
            $res4        =
                $this->writeBalanceLog($cusdt_asset, $order->id, 1, $order->total_price, 48, '出售交易增加USDT', 8, 'USDT');
            $res5        =
                $this->writeFrostLog($cc20t_asset, $order->id, 1, -$order->total_num, 50, '出售下单-冻结', 9, 'C20T');

            if ($res1 && $res2 && $res3 && $res4 && $res5) {
                DB::commit();
                return __return($this->successStatus, '操作成功');
            } else {
                DB::rollBack();
                return __return($this->errStatus, '操作失败');
            }
        } catch (\Exception $exception) {
            DB::rollBack();
            return __return($this->errStatus, $exception->getMessage());
        }
    }

    /**
     * 确认放行
     * @param Request $request
     * @return array
     */
    public function confirm(Request $request)
    {
        $user = $request->user;

        // 资金密码
        $tpwd     = $request->post('payment_password');
        $order_id = $request->post('order_id');

        if (!Hash::check($tpwd, $user->payment_password)) {
            return __return($this->errStatus, '资金密码输入错误');
        }

        $order = Fbtrans::where('id', $order_id)
                        ->where('chu_uid', $user->id)
                        ->where('status', Fbtrans::ORDER_PAID)
                        ->first();

        if (!$order) {
            return __return($this->errStatus, '该订单不存在');
        }

        DB::beginTransaction();

        try {
            $order->status     = Fbtrans::ORDER_OVER;
            $order->checked_at = now();
            $result            = $order->save();

            if (!$result) {
                DB::rollBack();
                return __return($this->errStatus, '更新状态失败');
            }

            //给购买人加余额
            $goAsset = UserAssets::getBalance($order->gou_uid);
            $inc     = $this->writeBalanceLog($goAsset, $order->id, 'USDT', $order->total_num, 21, '交易购买-增加余额');

            if (!$inc) {
                DB::rollBack();
                return __return($this->errStatus, '交易购买失败');
            }

            //减出售人冻结金额
            $chuAsset = UserAssets::getBalance($order->chu_uid);
            $dec      = $this->writeFrostLog($chuAsset, $order->id, 'USDT', -$order->total_num, 22, '交易出售-扣除冻结');
            if (!$dec) {
                DB::rollBack();
                return __return($this->errStatus, '交易出售失败');
            }

            DB::commit();
            return __return($this->successStatus, '确认成功');
        } catch (\Exception $exception) {
            DB::rollBack();
            return __return($this->errStatus, $exception->getMessage());
        }
    }

    /**
     * 提交申诉
     * @param Request $request
     * @return array
     */
    public function appeal(Request $request)
    {
        $user = $request->user;

        $validator = Validator::make(
            $request->all(),
            [
                'payment_password' => 'required',
                'order_id'         => 'required|min:0',
                'reason'           => 'required|min:0',
            ],
            [
                'payment_password.required' => '密码不能为空',
                'order_id.required'         => '交易ID不能为空',
                'reason.required'           => '申诉理由不能为空',
            ]
        );

        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }

        $post = $request->post();

        $order = Fbtrans::where('id', $post['order_id'])
                        ->where('status', Fbtrans::ORDER_PAID)
                        ->first();

        if (!$order) {
            return __return($this->errStatus, '订单异常或不存在');
        }

        if ($order->gou_uid != $user->id && $order->chu_uid != $user->id) {
            return __return($this->errStatus, '你无权申诉该订单');
        }

        if ($order->gou_uid == $user->id) {
            $appeal_uid    = $order->gou_uid;
            $be_appeal_uid = $order->chu_uid;
        } else {
            $appeal_uid    = $order->chu_uid;
            $be_appeal_uid = $order->gou_uid;
        }

        $appeal = Fbappeal::create([
            'order_no'      => $order->order_no,
            'command'       => mt_rand(1000, 9999),
            'oid'           => $post['order_id'],
            'appeal_uid'    => $appeal_uid,
            'be_appeal_uid' => $be_appeal_uid,
            'type'          => $order->type,
            'reason'        => $post['reason'],
            'order_status'  => $order->status,
        ]);

        $order->status = Fbtrans::ORDER_APPEAL;
        $result        = $order->save();
        if (!$result) {
            return __return($this->errStatus, '更新状态失败');
        }
        $backData = [
            'command' => $appeal->command,
            'refer'   => $appeal->refer,
        ];
        return __return($this->successStatus, '提交申诉成功,等待专员介入', $backData);
    }

    /**
     * 取消申诉
     * @param Request $request
     * @return array
     */
    public function cancelAppeal(Request $request)
    {
        $user = $request->user;
        //1待付款 2已付款 3已确认完成 4 申述中 5取消 6冻结
        $validator = Validator::make(
            $request->all(),
            [
                'appeal_id'        => 'required',
                'payment_password' => 'required',
            ],
            [
                'appeal_id.required'        => '申诉ID不能为空',
                'payment_password.required' => '支付密码不能为空',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }
        if (!Hash::check($request->payment_password, $user->payment_password)) {
            return __return($this->errStatus, '资金密码输入错误');
        }

        $appeal_info = Fbappeal::where(['id' => $request->appeal_id, 'appeal_uid' => $user->id])->first();
        if (empty($appeal_info)) {
            return __return($this->errStatus, '申诉订单不存在');
        }
        if ($appeal_info->status != 1) {
            return __return($this->errStatus, '该申诉订单状态不能进行取消');
        }
        Fbappeal::where(['id' => $request->appeal_id, 'appeal_uid' => $user->id])->update(['status' => 3]);
        $map           = array();
        $map['status'] = $appeal_info->order_status;
        if ($appeal_info->order_status == 1) {
            $map['created_at'] = date('Y-m-d H:i:s');
        }
        if ($appeal_info->order_status == 2) {
            $map['pay_at'] = date('Y-m-d H:i:s');
        }
        Fbtrans::where(['id' => $appeal_info->oid, 'appeal_uid' => $user->id])->update($map);
        return __return($this->successStatus, '取消成功');
    }

    /**
     * 取消订单
     * @param Request $request
     * @return array
     */
    public function cancelOrder(Request $request)
    {
        $user = $request->user;
        //1待付款 2已付款 3已确认完成 4 申述中 5取消 6冻结
        $order_id = $request->post('order_id');

        $order = Fbtrans::where('id', $order_id)
                        ->where('status', 1)
                        ->first();

        if (!$order) {
            return __return($this->errStatus, '订单不存在');
        }

        if ($order->gou_uid != $user->id && $order->chu_uid != $user->id) {
            return __return($this->errStatus, '订单异常');
        }

        DB::beginTransaction();

        try {
            $order->status     = Fbtrans::ORDER_CANCEL;
            $order->cancel_at  = now();
            $order->cancel_uid = $user->id;
            $result            = $order->save();

            if (!$result) {
                DB::rollBack();
                return __return($this->errStatus, '更新状态失败');
            }


            if ($order->type == 2) {
                //对于卖家有影响
                $asset = UserAssets::getBalance($order->chu_uid, 9, 1, true);
                $bool1 = $this->writeBalanceLog($asset, $order->id, 1, $order->total_num, 41, '出售下单-C20T', 9, 'C20T');
                $bool2 = $this->writeFrostLog($asset, $order->id, 1, -$order->total_num, 43, '法币交易冻结', 9, 'C20T');
                $bool3 = $this->writeBalanceLog($asset, $order->id, 1, $order->sxfee, 42, '出售下单-手续费', 9, 'C20T');
                if (!$bool1 || !$bool2 || !$bool3) {
                    DB::rollBack();
                    return __return($this->errStatus, '交易出售下单取消失败');
                }
            }

            if ($order->type == 1) {
                $Fbquery = new Fbsell();
                if ($order->sxfee > 0) {
                    // 返还手续费
                    $inc_fee = $Fbquery->where('order_no', $order->jy_order)->increment('sxfee', $order->sxfee + 0);
                    if (!$inc_fee) {
                        DB::rollBack();
                        return __return($this->errStatus, '返还手续费失败');
                    }
                }
            } else {
                $Fbquery = new Fbbuying();
            }

            // 减成交数量
            $dec = $Fbquery->where('order_no', $order->jy_order)
                           ->decrement('deals_num', $order->total_num);

            if (!$dec) {
                DB::rollBack();
                return __return($this->errStatus, '减成交数量失败');
            }


            DB::commit();
            return __return($this->successStatus, '取消成功');
        } catch (\Exception $exception) {
            DB::rollBack();
            return __return($this->errStatus, $exception->getMessage());
        }
    }

    /**
     * 法币交易历史明细 get
     */
    public function myOrderList(Request $request)
    {
        $user = $request->user;

        $Fbquery = Fbtrans::query();

        $type = $request->get('type', 1);
        if ($type == 1) {
            $Fbquery->where('gou_uid', $user->id);
        } else if ($type == 2) {
            $Fbquery->where('chu_uid', $user->id);
        } else {
            $Fbquery->where(function ($query) use ($user)
            {
                $query->where('chu_uid', $user->id)->orWhere('gou_uid', $user->id);
            });
        }

        $lists = $Fbquery
            ->select('id', 'chu_uid', 'gou_uid', 'order_no', 'type', 'status', 'price', 'total_num', 'total_price', 'created_at')
            ->orderBy('id', 'desc')
            ->paginate(10);


        return __return($this->successStatus, '获取成功', $lists);
    }


}
