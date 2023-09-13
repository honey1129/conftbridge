<?php

namespace App\Http\Controllers\Api;

use App\Http\Traits\WriteUserMoneyLog;
use App\Models\FeeRebates;
use App\Models\Product;
use App\Models\Recharge;
use App\Models\UserAssets;
use App\Models\UserBank;
use App\Models\UserMoneyLog;
use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;

class UserAssetsController extends Controller
{
    use WriteUserMoneyLog;

    protected $subscribe_redis;

    public function __construct()
    {
//        $this->subscribe_redis = Redis::connection('subscribe');
    }


    /**
     *
     * @param Request $request
     * @return array
     */
    public function getAssets(Request $request)
    {
        $user = $request->user;
        $usdtToCnyRate = 7.08;  // 1usdt = 7.08 cny
        $reoToUsdtRate = config('gongmu.three_price'); // 1 REO = 0.15 USDT
        $tcToUsdtRate = config('site.tc_price'); // 1 TC = 0.15 USDT

//        $usdtToEroRate = $usdtRate/$reoRate;
//        $tcToEro = $rate/$reoRate;

        $userAssets = UserAssets::where(['uid' => $user->id])->whereIn('pid', [8, 9, 10])->get()->toArray();
        $products   = Product::whereIn('pid', [8, 9, 10])->get()->toArray();
        $userBalance = [];
        $totalReo = 0;
        $totalCny = 0;
        foreach($userAssets as $userAsset){
            if($userAsset['pid'] == 8){
                $userBalance[] = [
                    'name' =>   $userAsset['pname'],
                    'balance' => round($userAsset['balance'], 4),
                    'frost'   => round($userAsset['frost'], 4),
                    'cny'     =>  round(($userAsset['balance'] + $userAsset['frost']) * $usdtToCnyRate, 2),
                    'img'     => $products[0]['image'],
                    'pid'     => $products[0]['pid']
                ];
                $totalReo += ($userAsset['balance'] + $userAsset['frost']) / $reoToUsdtRate;
                $totalCny += ($userAsset['balance'] + $userAsset['frost']) * $usdtToCnyRate;
            }elseif($userAsset['pid'] == 9){
                $userBalance[] = [
                    'name' =>   $userAsset['pname'],
                    'balance' => round($userAsset['balance'], 4),
                    'frost'   => round($userAsset['frost'], 4),
                    'cny'     =>  round(($userAsset['balance'] + $userAsset['frost'])*$reoToUsdtRate*$usdtToCnyRate , 2),
                    'img'     => $products[1]['image'],
                    'pid'     => $products[1]['pid']
                ];
                $totalReo += ($userAsset['balance'] + $userAsset['frost']);
                $totalCny += ($userAsset['balance'] + $userAsset['frost']) * $reoToUsdtRate * $usdtToCnyRate;
            }elseif($userAsset['pid'] == 10){
                $userBalance[] = [
                    'name' =>   $userAsset['pname'],
                    'balance' => round($userAsset['balance'], 4),
                    'frost'   => round($userAsset['frost'], 4),
                    'cny'     =>  round(($userAsset['balance'] + $userAsset['frost']) * $tcToUsdtRate * $usdtToCnyRate,2),
                    'img'     => $products[2]['image'],
                    'pid'     => $products[2]['pid']
                ];
                $totalReo += ($userAsset['balance'] + $userAsset['frost']) * $tcToUsdtRate / $reoToUsdtRate;
                $totalCny += ($userAsset['balance'] + $userAsset['frost']) * $tcToUsdtRate * $usdtToCnyRate;
            }
        }

        return __return($this->successStatus, '获取成功', ['total_reo' => $totalReo, 'total_cny' => $totalCny, 'coins' => $userBalance]);
    }

    /**
     * 资产信息
     * @param Request $request
     * @return array
     */
    public function assetInfo(Request $request)
    {
        $user = $request->user;
        $str = 'vb:indexTickerAll:usd2cny';
        $exrate = json_decode($this->subscribe_redis->get($str), true);
        $dev_price = $this->subscribe_redis->get('vb:ticker:newprice:c20t_usdt');
        if (empty($dev_price)) {
            return __return($this->errStatus, '获取最新ERC20T价格信息失败');
        }

        $btc_price = $this->subscribe_redis->get('vb:ticker:newprice:btc_usdt');
        if (empty($btc_price)) {
            return __return($this->errStatus, '获取最新BTC价格信息失败');
        }

        $eth_price = $this->subscribe_redis->get('vb:ticker:newprice:eth_usdt');
        if (empty($eth_price)) {
            return __return($this->errStatus, '获取最新ETH价格信息失败');
        }

        $data = array();
        $total_usdt = 0;
        $total_money = 0;
        $assets = UserAssets::where('uid', $user->id)->whereIn('pid', [8, 9, 10])->where('ptype', 1)->get()->toArray();
        foreach ($assets as $key => $val) {
            if ($val['pid'] == 1) {
                $total_usdt += $val['balance'] * $btc_price;
                $assets[$key]['balance_rmb'] = floor($val['balance'] * $btc_price * $exrate['USDT'] * 100) / 100;
                $total_money += $assets[$key]['balance_rmb'];

                $total_usdt += $val['frost'] * $btc_price;
                $assets[$key]['frost_rmb'] = floor($val['frost'] * $btc_price * $exrate['USDT'] * 100) / 100;
                $total_money += $assets[$key]['frost_rmb'];
                $assets[$key]['relative_usdt'] = $btc_price;
                $assets[$key]['balance'] = floor($val['balance'] * 10000) / 10000;
                $assets[$key]['frost'] = floor($val['frost'] * 10000) / 10000;
            } else if ($val['pid'] == 2) {
                $total_usdt += $val['balance'] * $eth_price;
                $assets[$key]['balance_rmb'] = floor($val['balance'] * $eth_price * $exrate['USDT'] * 100) / 100;
                $total_money += $assets[$key]['balance_rmb'];

                $total_usdt += $val['frost'] * $eth_price;
                $assets[$key]['frost_rmb'] = floor($val['frost'] * $eth_price * $exrate['USDT'] * 100) / 100;
                $total_money += $assets[$key]['frost_rmb'];
                $assets[$key]['relative_usdt'] = $eth_price;
                $assets[$key]['balance'] = floor($val['balance'] * 10000) / 10000;
                $assets[$key]['frost'] = floor($val['frost'] * 10000) / 10000;
            } else if ($val['pid'] == 8) {
                $total_usdt += $val['balance'];
                $assets[$key]['balance_rmb'] = floor($val['balance'] * $exrate['USDT'] * 100) / 100;
                $total_money += $assets[$key]['balance_rmb'];

                $total_usdt += $val['frost'];
                $assets[$key]['frost_rmb'] = floor($val['frost'] * $exrate['USDT'] * 100) / 100;
                $total_money += $assets[$key]['frost_rmb'];
                $assets[$key]['relative_usdt'] = 1;

                $assets[$key]['balance'] = floor($val['balance'] * 1000000) / 1000000;
                $assets[$key]['frost'] = floor($val['frost'] * 1000000) / 1000000;
            } else {
                $total_usdt += $val['balance'] * $dev_price;
                $assets[$key]['balance_rmb'] = floor($val['balance'] * $dev_price * $exrate['USDT'] * 100) / 100;
                $total_money += $assets[$key]['balance_rmb'];

                $total_usdt += $val['frost'] * $dev_price;
                $assets[$key]['frost_rmb'] = floor($val['frost'] * $dev_price * $exrate['USDT'] * 100) / 100;
                $total_money += $assets[$key]['frost_rmb'];
                $assets[$key]['relative_usdt'] = $dev_price;
                $assets[$key]['balance'] = floor($val['balance'] * 10000) / 10000;
                $assets[$key]['frost'] = floor($val['frost'] * 10000) / 10000;
            }

        }
        $data['ttl_usdt'] = floor($total_usdt * 1000000) / 1000000;
        $data['ttl_rmb'] = floor($total_money * 100) / 100;
        $data['list'] = $assets;
        return __return($this->successStatus, '獲取成功', $data);
    }

    /**
     * 我的优惠券
     * @param Request $request
     * @return array
     */
    public function myCoupons(Request $request)
    {
        $user = $request->user;
        $coupons = DB::table('user_coupons')
            ->where('uid', $user->id)
            ->get();

        $array = [2 => '500', 3 => '15万', 4 => '20万', 5 => '30万'];
        foreach ($coupons as $key => &$coupon) {
            if ($coupon->type == 1) {
                $coupon->text = '注册即赠送';
            }
            if ($coupon->type == 2) {
                $coupon->text = '累计充值' . $array[$coupon->type] . '可用';
            }

            if ($coupon->type > 2) {
                $coupon->text = '累计' . $array[$coupon->type] . '交易额可用';
            }

            if (now()->gt($coupon->expired_at) && $coupon->expired_at && $coupon->used == 0) {
                $coupon->used = 2;
            }

        }

        unset($coupon);

        return __return($this->successStatus, '獲取成功', $coupons);
    }


    /**
     * 我的佣金
     * @param Request $request
     * @return array
     */
    public function commissionDetails(Request $request)
    {
        $user = $request->user;
        $details = FeeRebates::where('recommend_id', $user->id)
            ->select('from_uid', 'fee', 'recommend_yongjin', 'created_at', 'type', 'memo')
            ->with('from')
            ->orderBy('id', 'desc')
            ->paginate(10);

        return __return($this->successStatus, '獲取成功', $details);

    }

    /**
     * 用户资金明细
     * @param Request $request
     * @return array
     */
    public function userMoneyLog(Request $request)
    {
        $user = $request->user;
        $wt = $request->get('wt', 1);//余额类型 1可用 2冻结

        $log = UserMoneyLog::query();

        if ($request->type) {
            //明细类型 config/system.php user_money_log_type
            $log->where('type', $request->type);
        }

        $logs = $log->where('uid', $user->id)
            ->where('wt', $wt)
            ->orderBy('id', 'desc')
            ->paginate(10);

        return __return($this->successStatus, '獲取成功', $logs);
    }

    /**
     * 从佣金提到余额的限制信息
     * @return array
     */
    public function feeWithdrawInfo()
    {
        $return['withdraw_min'] = config('tibi.fee_withdraw_min');
        $return['withdraw_max'] = config('tibi.fee_withdraw_max');

        return __return($this->successStatus, '獲取成功', $return);
    }

    /**
     * 从佣金提到余额的逻辑处理
     */
    public function feeWithdraw(Request $request)
    {
        $user = $request->user;
        if (!$request->money) {
            return __return($this->errStatus, '请输入提现金额');
        }

        $withdraw_min = config('tibi.fee_withdraw_min');

        if ($request->money < $withdraw_min) {
            return __return($this->errStatus, '最小金额为' . $withdraw_min);
        }

        $withdraw_max = config('tibi.fee_withdraw_max');

        if ($request->money > $withdraw_max) {
            return __return($this->errStatus, '最大金额为' . $withdraw_max);
        }

        $asset = UserAssets::getBalance($user->id);

        if ($asset->fee < $request->money) {
            return __return($this->errStatus, '佣金额度不足');
        }

        if (!Hash::check($request->payment_password, $user->payment_password)) {
            return __return($this->errStatus, '资金密码输入错误');
        }

        //创建充值订单
        $data['uid'] = $user->id;
        $data['usdt'] = $request->money;
        $data['mark'] = '客户佣金提现';
        $data['type'] = Recharge::FEE_RECHARGE;
        $data['status'] = Recharge::PAYED;
        $recharge = Recharge::create($data);

        $ordnum = 'RE' . date('YmdHis') . $recharge->id . mt_rand(1000, 9999);
        $recharge->ordnum = $ordnum;
        $recharge->save();


        $this->writeFeeLog($asset, $recharge->id, 'USDT', $request->money * (-1), 13, '佣金提现扣除佣金');
        $this->writeBalanceLog($asset, $recharge->id, 'USDT', $request->money, 13, '佣金提现增加余额');

        return __return($this->successStatus, '提现成功');
    }

    /**
     * 添加、编辑银行卡
     * @param Request $request
     * @return array
     */
    public function userBankEdit(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'type' => 'required',
                'name' => 'required',
                'bank' => 'required',
                'branch' => 'required',
                'card_num' => 'required',
                'payment_password' => 'required'

            ],
            [
                'type.required' => '请选择操作类型',
                'name.required' => '请输入开户姓名',
                'bank.required' => '请输入开户银行',
                'branch.required' => '请输入银行支行',
                'card_num.required' => '请输入银行卡号',
                'payment_password.required' => '请输入资金密码',
            ]
        );

        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }

        $post = $request->post();
        $user = $request->user;

        if (!Hash::check($post['payment_password'], $user->payment_password)) {
            return __return($this->errStatus, '资金密码输入错误');
        }

        $bank = UserBank::where('uid', $user->id)->first();
        if ($bank && $post['type'] == 1) {
            return __return($this->errStatus, '你已经添加过银行卡信息了');
        }
        $data['uid'] = $user->id;
        $data['name'] = $post['name'];
        $data['bank'] = $post['bank'];
        $data['branch'] = $post['branch'];
        $data['card_num'] = $post['card_num'];

        if ($post['mark']) {
            $data['mark'] = $post['mark'];
        }

        //1新增2编辑
        if ($post['type'] == 1) {
            UserBank::create($data);

            $user_config = $request->user_config;
            $user_config->bank_set = 1;
            $user_config->save();
        }

        if ($post['type'] == 2) {
            UserBank::where('uid', $user->id)->update($data);
        }

        return __return($this->successStatus, '操作成功');
    }


}
