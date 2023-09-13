<?php

namespace App\Http\Controllers\Api;

use App\Models\Products;
use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\{Redis, DB, Validator, Hash, Log};
use Carbon\Carbon;
use App\Models\{UserMoneyLog, UserAssets};

class OtcController extends Controller
{
    public function fabu(Request $request)
    {
        $user = $request->user;
        $now = date('H:i:s');
        $start = config("otc.start_time");
        $end = config("otc.end_time");

        if ($now < $start || $now > $end) {
            return __return($this->errStatus, '交易时间为每天:start ~ :end', null, ['start' => $start, 'end' => $end]);
        }
        if ($user->stoped) {
            return __return($this->errStatus, '您已被冻结，请联系管理员');
        }
        $validator = Validator::make(
            $request->all(),
            [
                'zongnum'          => 'required|numeric|min:1',
                'money1'           => 'required|numeric|min:1',
                'money2'           => 'required|numeric|min:1',
                //'leixing' => 'required|numeric|min:1', 1 卖  2 买
                //'type' => 'required|numeric|min:1', 资产类型：TK pid = 1
                'payment_password' => 'required',
                'jiage'            => 'required',
            ],
            [
                'zongnum.required'          => '请输入发布总数',
                'money1.required'           => '请输入最低限额',
                'money2.required'           => '请输入最高限额',
                // 'leixing.required'          => '请选择发布类型',
                //'type.required' => '请选择资产类型',
                // 'zhifu.required'            => '请选择支持的支付类型',
                'payment_password.required' => '交易密码必须',
                'jiage.required'            => '价格必须',
                'zongnum.numeric'           => '发布总数格式错误',
                'money1.numeric'            => '最低限额格式错误',
                'money2.numeric'            => '最高限额格式错误',
                // 'leixing.numeric' => '发布类型格式错误',
                // 'type.numeric'              => '资产类型错误',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }
        $user = $request->user;

        // 买单
        $request->leixing = 2;
        // 资产是门票
        $request->type = 4;
        if (empty($request->zongnum) || empty($request->money1) || empty($request->money2) || empty($request->payment_password) || empty($request->jiage)) {
            return __return($this->errStatus, '所有信息必填');
        }

        $shichang = DB::table('zzd_shichang1')->where(array('leixing' => $request->leixing, 'type' => $request->type, 'user_id' => $user->id, 'is_show' => 1))->count() + 0;
        if ($shichang >= 3) {
            return __return($this->errStatus, '每人最多挂3单');
        }
        if (!in_array($request->leixing, array(2))) {
            return __return($this->errStatus, '发布类型错误');
        }
        if (!in_array($request->type, array(4))) {
            return __return($this->errStatus, '资产类型错误');
        }

        //发布数量, 每天限额2000个
        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();
        $hasFabu = DB::table('zzd_shichang1')->where(array('leixing' => $request->leixing, 'type' => $request->type, 'user_id' => $user->id, 'is_show' => 1))->whereBetween('created_at', [$today, $tomorrow])->sum('zongnum');
        $maxBuyConfig = config("otc.max_fabu");
        if (($hasFabu + $request->zongnum) > $maxBuyConfig) {
            return __return($this->errStatus, '每日购买数量最多:max_buy', null, ['max_buy' => $maxBuyConfig]);
        }

        $config1 = config('otc.fabumin');
        $config2 = config('otc.fabumax');
        if ($request->zongnum < $config1 || $request->zongnum > $config2) {
            return __return($this->errStatus, '发布数量必须在:config1 - :config2之间', null, ['config1' => $config1, 'config2' => $config2]);
        }

        // 票价格
        $config3 = config("site.piao2usdt");
        $request->jiage = sprintf("%.2f", $request->jiage);
        $minPrice = sprintf('%2.f', $config3 * 0.8);
        if ($request->jiage <= 0 || $request->jiage < $minPrice) {
            return __return($this->errStatus, '超出发布最低价格:min_price', null, ['min_price' => $minPrice]);
        }

        $maxjiage = sprintf("%.2f", $config3 * 1.2);
        if ($request->jiage > $maxjiage) {
            return __return($this->errStatus, '超出发布最高价格:max_price', null, ['max_price' => $maxjiage]);
        }

        if ($request->money1 < 1 || $request->money2 < 1 || $request->money1 >= $request->money2) {
            return __return($this->errStatus, '限额最低为1,且最高限额必须大于最低限额');
        }
        if ($request->money2 > $request->zongnum) {
            return __return($this->errStatus, '最高限额不能大于发布总数');
        }
        if ($request->money1 * 10 % 10 != 0) {
            return __return($this->errStatus, '最低限额必须为整数');
        }
        if ($request->money2 * 10 % 10 != 0) {
            return __return($this->errStatus, '最高限额必须为整数');
        }
        //支付类型
        // $zhifu = explode(",", $request->zhifu);
        // $cuowu = 0;
        // foreach ($zhifu as $key => $val) {
        //     if (!in_array($val, array(1, 2, 3))) {
        //         $cuowu = 1;
        //         break;
        //     }
        // }
        // if (count($zhifu) > 3 || $cuowu == 1) {
        //     return __return($this->errStatus, '支付类型错误');
        // }
        //交易密码
        if (!$user->payment_password) {
            return __return($this->errStatus, '您还没有设置支付密码，请先设置支付密码');
        }
        if (!Hash::check($request->payment_password, $user->payment_password)) {
            return __return($this->errStatus, '支付密码错误');
        }
        DB::beginTransaction();
        try {
            $data = array();
            $data['user_id'] = $user->id;
            $data['zongnum'] = $request->zongnum;
            $data['num2'] = $data['zongnum'];
            $data['money'] = $request->jiage;
            $data['type'] = $request->type;
            $data['money1'] = $request->money1;
            $data['money2'] = $request->money2;
            $data['leixing'] = $request->leixing;
            $data['created_at'] = now();
            $data['updated_at'] = now();
            DB::table('zzd_shichang1')->insert($data);
            DB::commit();
            return __return($this->successStatus, '操作成功');
        } catch (\Exception $e) {
            Log::error($e);
            DB::rollBack();
            return __return($this->errStatus, '操作失败');
        }
    }


    public function shichang_list(Request $request)
    {
        /*$validator = Validator::make(
            $request->all(),
            [
                'leixing' => 'required|numeric|min:1',// 1 卖单  2 买单
                'type' => 'required|numeric|min:1',// TK pid = 1
            ],
            [
                'leixing.required' => '请选择发布类型',
                'type.required' => '请选择资产类型',
                'leixing.numeric' => '发布类型格式错误',
                'type.numeric' => '资产类型错误',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }*/

        $request->leixing = 2; // 买单
        $request->type = 4; // 资产是票
        $buy = $request->input('buy', 2);
        $area = $request->input('area', 0);
        $user = $request->user;
        if (!in_array($request->leixing, array(2))) {
            return __return($this->errStatus, '类型错误');
        }
        if (!in_array($request->type, array(4))) {
            return __return($this->errStatus, '资产类型错误');
        }

        $shichang = DB::table('zzd_shichang1')->where(array('leixing' => $request->leixing, 'type' => $request->type, 'is_show' => 1))->where('num2', '>', 0)->orderBy('money', 'desc')->paginate(10);
        foreach ($shichang as $key => $value) {
            $user = User::select('email')->where(array('id' => $value->user_id))->first();
            $shichang[$key]->zhanghao = substr_cut($user->email);
            $shichang[$key]->zongnum = $shichang[$key]->zongnum . '枚';
            $shichang[$key]->xiane = $value->money1 . '-' . $value->money2 . '枚';
            // $shichang[$key]->xiane = $shichang[$key]->zongnum;
            $shichang[$key]->money = $value->money . '';
        }
        return __return($this->successStatus, '获取成功', $shichang);
    }

    public function ordersell(Request $request)
    {
        $user = $request->user;
        $now = date('H:i:s');
        $start = config("otc.start_time");
        $end = config("otc.end_time");

        if ($now < $start || $now > $end) {
            return __return($this->errStatus, '交易时间为每天:start ~ :end', null, ['start' => $start, 'end' => $end]);
        }

        if ($user->stoped) {
            return __return($this->errStatus, '您已被冻结，请联系管理员');
        }
        $validator = Validator::make(
            $request->all(),
            [
                'sc_id'            => 'required|numeric|min:1',
                'num'              => 'required|numeric|min:1',
                'payment_password' => 'required',
            ],
            [
                'sc_id.required'            => '参数错误',
                'num.required'              => '请输入出售数量',
                'payment_password.required' => '请输入交易密码',
                'sc_id.numeric'             => '参数错误',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }
        $user = $request->user;

        if (empty($request->sc_id) || empty($request->payment_password)) {
            return __return($this->errStatus, '缺少必要参数');
        }

        $shichang = DB::table('zzd_shichang1')->where(array('id' => $request->sc_id, 'leixing' => 2, 'is_show' => 1))->where('num2', '>', 0)->first();


        if (empty($shichang)) {
            return __return($this->errStatus, '该买单已成交或撤销');
        }
        if ($shichang->user_id == $user->id) {
            return __return($this->errStatus, '不能出售自己发布');
        }
        // $request->num = $shichang->zongnum;
        $shuliang = intval($request->num);
        if ($shuliang < $shichang->money1 || $shuliang > $shichang->money2) {
            return __return($this->errStatus, '不在限额范围内');
        }
        if (empty($user->payment_password)) {
            return __return($this->errStatus, '您还没有设置支付密码，请先设置支付密码');
        }
        if (!Hash::check($request->payment_password, $user->payment_password)) {
            return __return($this->errStatus, '支付密码错误');
        }

        Db::beginTransaction();
        try {
            $shichang = DB::table('zzd_shichang1')->where(['id' => $request->sc_id])->lockForUpdate()->first();
            if ($shuliang > $shichang->num2) {
                DB::rollBack();
                return __return($this->errStatus, '剩余额度不足');
            }
            $qianbao = UserAssets::where(['uid' => $user->id, 'pid' => 4])->lockForUpdate()->first();
            $config = config("otc.shouxufei");

            $shouxufei = $config;

            if ($qianbao->balance < ($shuliang + $shouxufei)) {
                DB::rollBack();
                return __return($this->errStatus, '票余额不足');
            }
            $data = array();
            $data['leixing'] = 2;
            $data['type'] = $shichang->type;
            $data['order_sn'] = rand(000, 999) . $user->id . rand(0000, 9999);
            $data['user_id'] = $user->id;
            $data['sh_id'] = $shichang->user_id;
            $data['sc_id'] = $shichang->id;
            $data['num'] = $shuliang;
            $data['shouxufei'] = $shouxufei;
            $data['money'] = $shuliang * $shichang->money;
            $data['status'] = 1;
            $data['created_at'] = now();
            $data['updated_at'] = now();
            DB::table('zzd_buy1')->insert($data);
            // DB::table('zzd_shichang1')->where(array('id' => $shichang->id))->increment('dongjie', $shuliang);
            DB::table('zzd_shichang1')->where(array('id' => $shichang->id))->decrement('num2', $shuliang);
            DB::table('zzd_shichang1')->where(array('id' => $shichang->id))->increment('num1', $shuliang);

            $last = DB::table('zzd_shichang1')->where(['id' => $shichang->id])->value('num2');
            if ($last == 0) {
                DB::table('zzd_shichang1')->where(array('id' => $shichang->id))->update([
                    'is_show' => 0
                ]);
            }
            // 卖方扣除票
            $this->writeBalanceLog($qianbao, 0, -$shuliang, 32, 'OTC售出', 'OTC sell', $qianbao->pid, $qianbao->pname);
            // 买方加上票
            $buyPiaoAsset = UserAssets::getBalance($shichang->user_id, 4, 1, true);
            $this->writeBalanceLog($buyPiaoAsset, 0, $shuliang, 33, 'OTC购买', 'OTC buy', $buyPiaoAsset->pid, $buyPiaoAsset->pname);

            if ($shouxufei > 0) {
                // $feeAsset = UserAssets::getBalance($user->id, 4, 1, true);
                $this->writeBalanceLog($qianbao, 0, -$shouxufei, 19, 'OTC交易手续费', 'OTC fee', $qianbao->pid, $qianbao->pname);
                // $user_asset = UserAssets::where(['uid' => 1, 'pid' => 1])->lockForUpdate()->first();
                // $this->writeBalanceLog($user_asset, 0, 1, $shouxufei, 1, '交易手续费', $user_asset->pid, $user_asset->pname);
            }

            $needU = $shuliang * $shichang->money;
            $buyUAsset = UserAssets::getBalance($shichang->user_id, 8, 1, true);
            if ($buyUAsset->balance < $needU) {
                DB::rollBack();
                return __return($this->errStatus, '买方USDT不足');
            }
            $this->writeBalanceLog($buyUAsset, 0, -$needU, 33, 'OTC购买', 'OTC buy', $buyUAsset->pid, $buyUAsset->pname);
            // 卖方加U
            $sellUAsset = UserAssets::getBalance($user->id, 8, 1, true);
            $this->writeBalanceLog($sellUAsset, 0, $needU, 32, 'OTC售出', 'OTC sell', $sellUAsset->pid, $sellUAsset->pname);

            DB::commit();
            return __return($this->successStatus, '操作成功');
        } catch (\Exception $e) {
            Log::error($e);
            DB::rollBack();
            return __return($this->errStatus, '操作失败');
        }
    }


    // 卖出列表
    public function hyjiaoyi_list(Request $request)
    {
        $user = $request->user;
        $maidans = DB::table('zzd_buy1')->where(['user_id' => $user->id])->orderBy('id', 'desc')->paginate(10);
        foreach ($maidans as $key => $maidan) {
            if ($maidan->type == 4) {
                $maidans[$key]->type = 'CFT';
            }
            $otherUser = User::where(['id' => $maidan->sh_id])->first();
            $maidans[$key]->zhanghao = $otherUser->email;
        }
        return __return($this->successStatus, '获取成功', $maidans);
    }

    //我的发布
    public function myfabulist(Request $request)
    {
        //        $validator = Validator::make(
        //            $request->all(),
        //            [
        //                'type' => 'required|numeric|min:1'
        //            ],
        //            [
        //                'type.required' => '请选择类型',
        //                'type.numeric' => '类型格式错误',
        //            ]
        //        );
        //        if ($validator->fails()) {
        //            $errors = $validator->errors()->all();
        //            return __return($this->errStatus, $errors[0]);
        //        }
        $user = $request->user;
        $request->type = 2;
        if (!in_array($request->type, array(1, 2))) {
            return __return($this->errStatus, '类型错误');
        }
        $wap = array();
        if ($request->type == 1) {
            $wap = array('user_id' => $user->id, 'leixing' => 1);
        } else {
            $wap = array('user_id' => $user->id, 'leixing' => 2);
        }
        $maidan = DB::table('zzd_shichang1')->where($wap)->orderBy('is_show', 'desc')->orderBy('id', 'desc')->paginate(10);
        foreach ($maidan as $key => $value) {
            $maidan[$key]->zong1 = $value->num1 * $value->money;
            $maidan[$key]->zong2 = $value->zongnum * $value->money;

            $user = User::select('email')->where(array('id' => $value->user_id))->first();
            $maidan[$key]->zhanghao = substr_cut($user->email);
            $maidan[$key]->zongnum = $maidan[$key]->zongnum . '枚';
            $maidan[$key]->xiane = $value->money1 . '-' . $value->money2 . '枚';
            // $shichang[$key]->xiane = $shichang[$key]->zongnum;
            $maidan[$key]->money = $value->money . '';
        }
        return __return($this->successStatus, '获取成功', $maidan);
    }


    //一个发布单对应的对应的多个交易单
    public function shjiaoyi_list(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'sc_id' => 'required|numeric|min:1',
                // 'type'  => 'required',
                //默认买单
            ],
            [
                'sc_id.required' => '参数错误',
                'sc_id.numeric'  => '参数错误',
                // 'type.required'  => '订单类型必须',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }
        $user = $request->user;
        if (empty($request->sc_id)) {
            return __return($this->errStatus, '缺少必要参数');
        }

        $wap = array('sc_id' => $request->sc_id, 'sh_id' => $user->id, 'status' => 1);
        $maidan = DB::table('zzd_buy1')->where($wap)->orderBy('id', 'desc')->paginate(10);
        foreach ($maidan as $key => $value) {
            $userinfo = DB::table('users')->where(array('id' => $value->user_id))->first();
            $maidan[$key]->zhanghao = $userinfo->phone ? $userinfo->phone : $userinfo->email;
            if ($value->type == 4) {
                $maidan[$key]->type = 'CFT';
            }
        }
        return __return($this->successStatus, '获取成功', $maidan);
    }


    //商家下架
    public function chedan(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'sc_id' => 'required|numeric|min:1',
            ],
            [
                'sc_id.required' => '参数错误',
                'sc_id.numeric'  => '参数错误',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }
        $user = $request->user;
        if (empty($request->sc_id)) {
            return __return($this->errStatus, '缺少必要参数');
        }
        $shichang = DB::table('zzd_shichang1')->where(array('id' => $request->sc_id, 'user_id' => $user->id, 'is_show' => 1))->first();
        if (empty($shichang)) {
            return __return($this->errStatus, '该订单不存在或已下架');
        }
        $cha = time() - $shichang->add_time;
        if ($cha < 1800) {
            return __return($this->errStatus, '发布订单30分钟内无法取消');
        }
        Db::beginTransaction();
        try {
            $shichang = DB::table('zzd_shichang1')->where(['id' => $request->sc_id])->lockForUpdate()->first();
            if ($shichang->dongjie != 0) {
                DB::rollBack();
                return __return($this->errStatus, '有交易中订单,不可下架');
            }
            DB::table('zzd_shichang1')->where(array('id' => $shichang->id))->delete();
            DB::commit();
            return __return($this->successStatus, '操作成功');
        } catch (\Exception $e) {
            Log::error($e);
            DB::rollBack();
            return __return($this->errStatus, '操作失败');
        }
    }

    public function shiinfo(Request $request)
    {
        $data = [];
        $hangqing = DB::table('zzd_zoushi')->orderBy('id', 'desc')->first();
        $data['jiage'] = round($hangqing->price, 4) . '';
        $data['jiagemax'] = round($hangqing->max_price, 4) . '';
        $data['jiagemin'] = round($hangqing->min_price, 4) . '';
        //$data['fabumax']   = sprintf("%.2f", $hangqing->jiage * 1.2);
        //$data['miaoshu']   = '发布价格不得超过' . $data['fabumax'];
        $start = strtotime(date('Y-m-d', time()));
        $end = $start + 86399;
        $todayTradeNum = DB::table('zzd_buy1')->where(array('status' => 1))->whereBetween('created_at', [$start, $end])->sum('num') + 0;
        $totalTradeNum = config("jiaoyi.total_trade_num");
        $data['zongliang'] = $totalTradeNum + $todayTradeNum;
        $data['zongliang'] = sprintf("%.2f", $data['zongliang']);
        $zoushi = DB::table('zzd_zoushi')->orderBy('id', 'desc')->limit(7)->get()->toarray();
        $info = array_reverse($zoushi);
        foreach ($info as $key => $value) {
            $info[$key]->id = $key + 1;
            $info[$key]->date = date('m-d', strtotime($value->date));
        }
        $data['zoushi'] = $info;
        return __return($this->successStatus, '获取成功', $data);
    }


}