<?php

namespace App\Http\Controllers\Api;

use App\Models\Authentication;
use App\Models\ChainCoin;
use App\Models\ChainNetwork;
use App\Models\Product;
use App\Models\UserBank;
use App\Models\UserPositions;
use App\Models\WalletCode;
use App\User;
use Extend\Wallet\EthInterface;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\UserAssets;
use App\Models\UserWithdraw;
use App\Http\Traits\GoogleAuthenticator;
use App\Http\Traits\WriteUserMoneyLog;
use App\Http\Traits\SendSms;
use App\Http\Traits\SendEmail;
use Carbon\Carbon;
use Validator;
use Hash;
use Illuminate\Support\Facades\DB;

class ApplyWithdrawController extends Controller
{
    use WriteUserMoneyLog, SendSms, SendEmail, GoogleAuthenticator;

    /**
     * 获取账户余额
     * @param Request $request
     * @return array
     */
    public function checkBalance(Request $request)
    {
        $user = $request->user;
        $assets1 = UserAssets::getBalance($user->id, 8, 2, false);
        $return['balance1'] = $assets1->balance;
        $assets2 = UserAssets::getBalance($user->id, 8, 1, false);
        $return['balance2'] = $assets2->balance;
        return __return($this->successStatus, '获取成功', $return);

    }

    /**
     * Display a listing of the resource.
     *
     * @return =
     */
    public function withdrawLog(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'pid' => 'required',
            ],
            [
                'pid.required' => '币种必须输入',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }
        $user = $request->user;
        $logs = UserWithdraw::with(['chain_networks'])
            ->where('uid', $user->id)
            ->where('pid', $request->pid)
            ->orderBy('id', 'desc')
            ->paginate(10);
        foreach ($logs as $key => $val) {
            $pname = WalletCode::getCode($val['pid']);
            $arr = explode('_', $pname);
            $list[$key]['pname'] = $arr[0];
            unset($logs[$key]['en_reason']);
            unset($logs[$key]['refuse_reason']);
            if (strlen($logs[$key]['tx_id']) < 30) {
                $logs[$key]['tx_id'] = 'err';
            }
            $logs[$key]['chain_name'] = $val['chain_networks']['en_name'];
            unset($logs[$key]['chain_networks']);
        }
        return __return($this->successStatus, '获取成功', $logs);
    }


    /**
     * Display a listing of the resource.
     *
     * @return array|\Illuminate\Http\Response
     */
    public function withdrawList(Request $request)
    {
        $user = $request->user;
        $logs = UserWithdraw::with(['chain_networks'])->where('uid', $user->id)
            ->orderBy('id', 'desc')
            ->paginate(10);
        foreach ($logs as $key => $val) {
            $pname = WalletCode::getCode($val['pid']);
            $logs[$key]['pname'] = $pname;
            unset($logs[$key]['en_reason']);
            unset($logs[$key]['refuse_reason']);
            if (strlen($logs[$key]['tx_id']) < 30) {
                $logs[$key]['tx_id'] = 'err';
            }
            $logs[$key]['chain_name'] = $val['chain_networks']['en_name'];
            unset($logs[$key]['chain_networks']);
        }

        return __return($this->successStatus, '获取成功', $logs);
    }

    /**
     * 用户创建提现地址
     *
     *
     */
    public function createWithdrawAddress(Request $request)
    {


        $validator = Validator::make(
            $request->all(),
            [
                'address' => 'required',
                'notes'   => 'required',
                'type'    => 'required',
            ],
            [
                'address.required' => '地址必须',
                'notes.required'   => '备注必须',
                'type.required'    => '类型必须',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }

        $user = $request->user;
        $post = $request->post();

        $addr = DB::table('user_qianbao_address')
            ->where('uid', $user->id)
            ->where('address', $post['address'])
            ->where('type', $post['type'])
            ->first();
        if ($addr) {
            return __return($this->errStatus, '已存在该地址');
        }
        if (strlen($post['address']) > 64) {
            return __return($this->errStatus, '不是一个真实有效的钱包地址');
        }
        $data = array();
        $data['uid'] = $user->id;
        $data['code'] = '';
        $data['address'] = $post['address'];
        $data['notes'] = $post['notes'];
        $data['type'] = $post['type'];
        $data['created_at'] = Carbon::now();
        $data['updated_at'] = Carbon::now();
        DB::table('user_qianbao_address')->insert($data);
        return __return($this->successStatus, '添加成功');
    }

    /**
     * 用户提现地址列表
     */
    public function getWithdrawAddress(Request $request)
    {
        $user = $request->user;
        $data = DB::table('user_qianbao_address')
            ->join('chain_network', 'user_qianbao_address.type', '=', 'chain_network.id')
            ->where('uid', $user->id)
            ->orderBy('user_qianbao_address.id', 'DESC')
            ->select(['user_qianbao_address.*', 'chain_network.en_name'])
            ->paginate(10);

        return __return($this->successStatus, '获取成功', $data);
    }

    /**
     * 用户提币处理
     * @param Request $request [description]
     * @return [type]           [description]
     */
    public function applyWithdraw(Request $request)
    {
        $user = $request->user;
        if ($user->stoped == 1) {
            return __return($this->errStatus, '该账号已经被冻结');
        }
        if ($user->is_deposit == 0) {
            return __return($this->errStatus, '该账号已经被冻结');
        }
        /*$chongzhi = DB::table('recharges')->where(['uid' => $user->id, 'status' => 2])
                      ->first();
        if (empty($chongzhi)) {
            return __return($this->errStatus, 'err');
        }*/
        // $rz = DB::table('authentications')->where(['uid' => $user->id, 'status' => 3])->first();
        // if (empty($rz)) {
        //     return __return($this->errStatus, '未通过实名认证');
        // }
        $validator = Validator::make(
            $request->all(),
            [
                'money'            => 'required|numeric|min:1',
                'payment_password' => 'required',
                'pid'              => 'required',
                'address'          => 'required',
                'type'             => 'required',
                // 'google_code'      => 'required',
            ],
            [
                'money.required'            => '金额必须',
                'payment_password.required' => '资金密码不能为空',
                'pid.required'              => '币种必须输入',
                'address.required'          => '地址必须',
                'type.required'             => '类型必须',
                // 'google_code.required'      => '请输入谷歌验证码',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }
        ////        //判断账户类型
//        if (!in_array($user->type, array(1, 2))) {
//            return __return($this->errStatus, '地址类型错误');
//        }
        $post = $request->post();
        if (!$user->payment_password) {
            return __return($this->errStatus, '请先设置交易密码');
        }
        
        $address = $request->address;
        if($address[0] == 'T' || $address[0] == 't'){
            $post['type'] = 2;
        }else{
            $post['type'] = 5;
        }
        //支付密码验证
        if (!Hash::check($request->payment_password, $user->payment_password)) {
            return __return($this->errStatus, '交易密码输入错误');
        }
        $asset = UserAssets::getBalance($user->id, $request->pid, 1);
        if (empty($asset)) {
            return __return($this->errStatus, '币种类型错误');
        }
        //验证谷歌验证器
        // $config = $request->user_config;
        // if ($config->google_bind == 0) {
        //     return __return($this->errStatus, '没有绑定谷歌验证码');
        // }
        // // 验证验证码和密钥是否相同
        // if (!$this->CheckCode($config->google_secret, $request->google_code)) {
        //     return __return($this->errStatus, '谷歌验证码错误');
        // }

        $chain_coin = ChainCoin::where('pid', $request->pid)
            ->where('chain_id', $request->type)
            ->first();
        if (empty($chain_coin)) {
            return __return($this->errStatus, '币种类型错误');
        }
        // if ($chain_coin->is_withdraw != 1) {
        //     return __return($this->errStatus, '提币通道维护中');
        // }
        // 检测最小提币数量
        $min_money = config('trans.withdraw_min');
        // 检测最大提币数量
        $max_money = config('trans.withdraw_max');
        // 提币手续费
        $withdraw_handling_fee = config('trans.withdraw_fee');

        if (($post['money'] < $min_money) || ($post['money'] > $max_money)) {
            return __return($this->errStatus, "提现金额单笔为:min_money - :max_money之间,请正确选择", null, [
                "min_money" => $min_money,
                "max_money" => $max_money
            ]);
        }
        if ($post['money'] > $asset->balance) {
            return __return($this->errStatus, '账户余额不足');
        }

        $piaoAsset = UserAssets::getBalance($user->id, 4);
        if ($piaoAsset->balance < $withdraw_handling_fee) {
            return __return($this->errStatus, '票余额不足');
        }

        DB::beginTransaction();
        try {
            $re1 = UserWithdraw::create([
                'with_num'     => 'TX' . date('YmdHis') . $user->id . rand(1000, 9999),
                'uid'          => $user->id,
                'money'        => $post['money'],
                'handling_fee' => $withdraw_handling_fee,
                'last_money'   => $asset->balance - $post['money'],
                'actual'       => $post['money'],
                'mark'         => '用户提币',
                'en_mark'      => '用户提币',
                'address'      => $post['address'],
                'type'         => $post['type'],
                'pid'          => $request->pid,
            ]);
            $this->writeBalanceLog($asset, $re1->id, -$post['money'], 7, '用户提币', 'Withdrawal', $asset->pid, $asset->pname, 1);
            if ($withdraw_handling_fee > 0) {
                $piaoAsset = UserAssets::getBalance($user->id, 4, 1, true);
                $this->writeBalanceLog($piaoAsset, $re1->id, -$withdraw_handling_fee, 19, '提币手续费', 'Withdrawal fee', $piaoAsset->pid, $piaoAsset->pname, 1);
            }
            DB::commit();
            return __return($this->successStatus, '提币成功，待审核');
        } catch (\Exception $e) {
            DB::rollBack();
            return __return($this->errStatus, '提币失败!');
        }
    }

    /**
     * 提币地址删除
     *
     * @return array|\Illuminate\Http\Response
     */
    public function deleteWithdrawAddress(Request $request)
    {

        if (!$request->address_id) {
            return __return($this->errStatus, '缺少刪除的提币地址');
        }

        $user = $request->user;
        if ($user->stoped == 0) {
            return __return($this->errStatus, '该账号已经被冻结');
        }
        $post = $request->post();

        $addr = DB::table('user_qianbao_address')
            ->where('uid', $user->id)
            ->where('id', $post['address_id'])
            ->delete();

        if (!$addr) {
            return __return($this->errStatus);
        }

        return __return($this->successStatus);
    }
}