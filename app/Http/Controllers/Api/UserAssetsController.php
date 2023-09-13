<?php

namespace App\Http\Controllers\Api;

use App\Http\Traits\WriteUserMoneyLog;
use App\Models\AssetRelease;
use App\Models\FeeRebates;
use App\Models\Order;
use App\Models\OrderExt;
use App\Models\Product;
use App\Models\Recharge;
use App\Models\SystemValue;
use App\Models\UserAssets;
use App\Models\UserBank;
use App\Models\UserMoneyLog;
use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;

class UserAssetsController extends Controller
{
    use WriteUserMoneyLog;

    /**
     *未使用
     * @param Request $request
     * @return array
     */
    public function getAssets(Request $request)
    {
        $user = $request->user;
        $usdt2CnyRedis = Redis::get('vb:indexTickerAll:usd2cny');
        if ($usdt2CnyRedis) {
            $usdt2CnyRedisArr = json_decode($usdt2CnyRedis, true);
            $usdtToCnyRate = $usdt2CnyRedisArr['USDT'];
        } else {
            $usdtToCnyRate = 6.8;
        }
        //        $userAssets  = UserAssets::where(['uid' => $user->id])->whereIn('pid', [8, 9, 10])->get()->toArray();
//        $products    = Product::whereIn('pid', [8, 9, 10])->get()->toArray();
        $userBalance = [];
        $totalUsdt = 0;
        $totalCny = 0;
        return __return(
            $this->successStatus,
            '获取成功',
            [
                'total_reo' => $totalUsdt,
                'total_cny' => round($totalCny, 2),
                'usdt_cny'  => $usdtToCnyRate,
                'coins'     => $userBalance
            ]
        );
    }

    /**
     * 未使用
     * 我的佣金
     * @param Request $request
     * @return array
     */
    public function commissionDetails(Request $request)
    {
        $user = $request->user;
        $details = AssetRelease::where('uid', $user->id)->whereIn('order_type', [2, 3])
            ->paginate(10);
        return __return($this->successStatus, '获取成功' . $user->id, $details);

    }

    /**
     *  * 废弃
     * 从佣金提到余额的限制信息
     * @return array
     */
    public function feeWithdrawInfo()
    {
        $return['withdraw_min'] = config('tibi.fee_withdraw_min');
        $return['withdraw_max'] = config('tibi.fee_withdraw_max');

        return __return($this->successStatus, '获取成功', $return);
    }

    /**
     * 废弃
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
     * 未使用
     * 添加、编辑银行卡
     * @param Request $request
     * @return array
     */
    public function userBankEdit(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'type'             => 'required',
                'name'             => 'required',
                'bank'             => 'required',
                'branch'           => 'required',
                'card_num'         => 'required',
                'payment_password' => 'required'

            ],
            [
                'type.required'             => '请选择操作类型',
                'name.required'             => '请输入开户姓名',
                'bank.required'             => '请输入开户银行',
                'branch.required'           => '请输入银行支行',
                'card_num.required'         => '请输入银行卡号',
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


    // 划转
    public function transfer(Request $request)
    {
        $user = $request->user;
        $validator = validator($request->post(), [
            'from_pid'         => 'required|integer|gt:0',
            'money'            => 'required|numeric|gt:0',
            'payment_password' => 'required'
        ]);

        if ($validator->fails()) {
            return __return($this->errStatus, '参数错误');
        }

        $fromPid = $request->input('from_pid');
        $money = $request->input('money');
        $paymentPassword = $request->input('payment_password');

        if (empty($user->payment_password)) {
            return __return($this->errStatus, '您还没有设置支付密码，请先设置支付密码');
        }

        if (!Hash::check($paymentPassword, $user->payment_password)) {
            return __return($this->errStatus, '支付密码错误');
        }

        if (!in_array($fromPid, [3, 4, 6])) {
            return __return($this->errStatus, '参数错误');
        }

        if ($fromPid == 3) {
            $feeRate = 10;
            $fee = round($feeRate * 0.01 * $money, 6);

            $toPid = 8;
            if ($money < 5) {
                return __return($this->errStatus, '最小划转金额5U');
            }
        }
        if ($fromPid == 4) {
            $feeRate = 0;
            $fee = 1;
            $toPid = 6;
        }

        if ($fromPid == 6) {
            $feeRate = 0;
            $fee = 1;
            $toPid = 4;
        }

        try {
            DB::beginTransaction();
            $fromAsset = UserAssets::getBalance($user->id, $fromPid, 1, true);
            $toAsset = UserAssets::getBalance($user->id, $toPid, 1, true);
            if ($fromPid == 3) {
                // U 扣10 % 手续费
                if ($fromAsset->balance < ($money)) {
                    DB::rollBack();
                    return __return($this->errStatus, '账户余额不足');
                }
                $this->writeBalanceLog($fromAsset, 0, -$money, 17, '划转', 'transfer', $fromAsset->pid, $fromAsset->pname);
                $this->writeBalanceLog($toAsset, 0, $money - $fee, 17, '划转', 'transfer', $toAsset->pid, $toAsset->pname);
            } else if ($fromPid == 4) {
                if ($fromAsset->balance < ($money + $fee)) {
                    DB::rollBack();
                    return __return($this->errStatus, 'CFT余额不足');
                }
                $this->writeBalanceLog($fromAsset, 0, -$money, 17, '划转', 'transfer', $fromAsset->pid, $fromAsset->pname);
                $this->writeBalanceLog($fromAsset, 0, -$fee, 19, '划转手续费', 'transfer fee', $fromAsset->pid, $fromAsset->pname);
                $this->writeBalanceLog($toAsset, 0, $money, 17, '划转', 'transfer', $toAsset->pid, $toAsset->pname);
            } else if ($fromPid == 6) {
                if ($fromAsset->balance < $money) {
                    DB::rollBack();
                    return __return($this->errStatus, 'CFT(租)余额不足');
                }
                if ($toAsset->balance < $fee) {
                    DB::rollBack();
                    return __return($this->errStatus, 'CFT余额不足');
                }
                $this->writeBalanceLog($fromAsset, 0, -$money, 17, '划转', 'transfer', $fromAsset->pid, $fromAsset->pname);
                $this->writeBalanceLog($toAsset, 0, -$fee, 19, '划转手续费', 'transfer fee', $toAsset->pid, $toAsset->pname);
                $this->writeBalanceLog($toAsset, 0, $money, 17, '划转', 'transfer', $toAsset->pid, $toAsset->pname);
            }

            if ($fee > 0) {
                //$this->writeBalanceLog($fromAsset, 0, -$fee, 17, '划转', 'transfer', $fromAsset->pid, $fromAsset->pname);
                if ($fromPid == 3) {
                    $pool1 = User::where(['email' => 'pool1@qq.com'])->first();
                    $pool1UserAsset = UserAssets::where(['pid' => 3, 'uid' => $pool1->id])->first();
                    $this->writeBalanceLog($pool1UserAsset, 0, $fee * 0.3, 26, $user->email . '划转手续费30%转入', '划转手续费30%转入', $pool1UserAsset->pid, $pool1UserAsset->pname);

                    $pool2 = User::where(['email' => 'pool2@qq.com'])->first();
                    $pool2UserAsset = UserAssets::where(['pid' => 3, 'uid' => $pool2->id])->first();
                    $this->writeBalanceLog($pool2UserAsset, 0, $fee * 0.2, 26, $user->email . '划转手续费20%转入', '划转手续费20%转入', $pool2UserAsset->pid, $pool2UserAsset->pname);

                    $pool3 = User::where(['email' => 'pool3@qq.com'])->first();
                    $pool3UserAsset = UserAssets::where(['pid' => 3, 'uid' => $pool3->id])->first();
                    $this->writeBalanceLog($pool3UserAsset, 0, $fee * 0.1, 26, $user->email . '划转手续费10%转入', '划转手续费10%转入', $pool3UserAsset->pid, $pool3UserAsset->pname);


                    $pool4 = User::where(['email' => 'pool4@qq.com'])->first();
                    $pool4UserAsset = UserAssets::where(['pid' => 3, 'uid' => $pool4->id])->first();
                    $this->writeBalanceLog($pool4UserAsset, 0, $fee * 0.1, 26, $user->email . '划转手续费10%转入', '划转手续费10%转入', $pool4UserAsset->pid, $pool4UserAsset->pname);

                    $pool5 = User::where(['email' => 'pool5@qq.com'])->first();
                    $pool5UserAsset = UserAssets::where(['pid' => 3, 'uid' => $pool5->id])->first();
                    $this->writeBalanceLog($pool5UserAsset, 0, $fee * 0.1, 26, $user->email . '划转手续费10%转入', '划转手续费10%转入', $pool5UserAsset->pid, $pool5UserAsset->pname);

                    $pool6 = User::where(['email' => 'pool6@qq.com'])->first();
                    $pool6UserAsset = UserAssets::where(['pid' => 3, 'uid' => $pool6->id])->first();
                    $this->writeBalanceLog($pool6UserAsset, 0, $fee * 0.1, 26, $user->email . '划转手续费10%转入', '划转手续费10%转入', $pool6UserAsset->pid, $pool6UserAsset->pname);

                    $pool7 = User::where(['email' => 'pool7@qq.com'])->first();
                    $pool7UserAsset = UserAssets::where(['pid' => 3, 'uid' => $pool7->id])->first();
                    $this->writeBalanceLog($pool7UserAsset, 0, $fee * 0.1, 26, $user->email . '划转手续费10%转入', '划转手续费10%转入', $pool7UserAsset->pid, $pool7UserAsset->pname);

                    SystemValue::where(['name' => 'pool_master_balance_full'])->increment('value', $fee);
                }
            }

            DB::commit();
            return __return($this->successStatus, '操作成功');
        } catch (\Exception $e) {
            Log::error($e);
            DB::rollBack();
            return __return($this->errStatus, '操作失败');
        }

    }


}