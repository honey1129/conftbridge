<?php

namespace App\Http\Traits;

use App\Models\UserAssets;
use App\Models\UserMoneyLog;
use Illuminate\Support\Facades\DB;

trait WriteUserMoneyLog
{


    /**
     * @param $asset -资产
     * @param $order_id - 订单id
     * @param $ptype - 币种类型
     * @param $money - 金额
     * @param $type - 明细类型
     * @param $mark - 单笔标识
     * @return bool
     */
    public function writeBalanceLog(
        $asset,
        $order_id,
        $money,
        $type,
        $mark,
        $en_mark,
        $pid = 8,
        $pname = 'usdt',
        $ptype = 1
    ) {
        //增加可用余额
        $ymoney = $asset->balance;
        $asset->balance = $asset->balance + $money;
        $bool1 = $asset->save();
        $nmoney = $asset->balance;
        if ($bool1) {
            //写入用户资金日志
            $bool2 = UserMoneyLog::create([
                'uid'      => $asset->uid,
                'order_id' => $order_id,
                'pname'    => strtoupper($pname),
                'pid'      => $pid,
                'ptype'    => $ptype,
                'ymoney'   => $ymoney,
                'money'    => $money,
                'nmoney'   => $nmoney,
                'type'     => $type,
                'mark'     => $mark,
                'en_mark'  => $en_mark,
                'wt'       => 1,
            ]);
        }

        return ($bool1 && $bool2) ? true : false;

    }

    /**
     * @param $asset -资产
     * @param $order_id - 订单id
     * @param $ptype - 币种类型
     * @param $money - 金额
     * @param $type - 明细类型
     * @param $mark - 单笔标识
     * @return bool
     */


    public function writeFrostLog(
        $asset,
        $order_id,
        $money,
        $type,
        $mark,
        $en_mark,
        $pid = 8,
        $pname = 'usdt',
        $ptype = 1
    ) {
        //增加可用余额
        $ymoney = $asset->frost;
        $asset->frost = $asset->frost + $money;
        $bool1 = $asset->save();
        $nmoney = $asset->frost;
        if ($bool1) {
            //写入用户资金日志
            $bool2 = UserMoneyLog::create([
                'uid'      => $asset->uid,
                'order_id' => $order_id,
                'pname'    => strtoupper($pname),
                'ptype'    => $ptype,
                'pid'      => $pid,
                'ymoney'   => $ymoney,
                'money'    => $money,
                'nmoney'   => $nmoney,
                'type'     => $type,
                'mark'     => $mark,
                'en_mark'  => $en_mark,
                'wt'       => 2,
            ]);
        }
        return ($bool1 && $bool2) ? true : false;

    }

    //更新最小资产包信息
    public function updateAssetOrder($uid, $money)
    {
        $temp_money = $money;
        $order_list = AssetOrder::where(['uid' => $uid, 'state' => 1])->orderBy('usdt_num')->first();
        if ($order_list) {
            if ($order_list->total_num + $temp_money > $order_list->total_usdt) {
                $temp_money = $order_list->total_usdt - $order_list->total_num;
                $order_list->state = 2;
                $order_list->total_num = $order_list->total_usdt;
            } else {
                $order_list->total_num = $order_list->total_num + $temp_money;
            }
            $order_list->save();
            $money -= $temp_money;
            if ($money > 0) {
                self::updateAssetOrder($uid, $money);
            } else {
                return 0;
            }
        } else {
            return $temp_money;
        }
    }

    public function divisionData($data, $count = 5000)
    {
        $returnData = [];
        #分割添加众筹静态分红--每组5000批量添加
        $num = count($data);
        $j = (int)ceil($num / $count);
        $start = 0;
        for ($i = 1; $i <= $j; $i++) {
            $shuliang = (int)ceil(($num - $start) / ($j - $i + 1));
            $returnData[$i - 1] = array_slice($data, $start, $shuliang);
            $start += $shuliang;
        }

        return $returnData;
    }

    public function writeFeeLog($asset, $order_id, $ptype, $fee, $type, $mark)
    {
        //增加佣金余额
        $ymoney = $asset->fee;
        $asset->fee = $asset->fee + $fee;
        $bool1 = $asset->save();
        $nmoney = $asset->fee;
        if ($bool1) {
            //写入用户资金日志
            $bool2 = UserMoneyLog::create([
                'uid'      => $asset->uid,
                'order_id' => $order_id,
                'ptype'    => $ptype,
                'ymoney'   => $ymoney,
                'money'    => $fee,
                'nmoney'   => $nmoney,
                'type'     => $type,
                'mark'     => $mark,
                'wt'       => 3,
            ]);
        }

        return ($bool1 && $bool2) ? true : false;

    }

    public function writeLog($uid, $order_id, $pid, $pname, $money = 0, $frost = 0, $type, $mark, $en_mark, $ptype = 2)
    {
        //查询当前资产信息表
        //不存在就创建
        $asset = UserAssets::where(['uid' => $uid, 'pid' => $pid, 'ptype' => $ptype])->first();
        if (empty($asset)) {
            //创建个人资产信息表
            $asset = UserAssets::create([
                'uid'     => $uid,
                'pid'     => $pid,
                'balance' => $money,
                'frost'   => $frost,
                'ptype'   => $ptype,
                'pname'   => $pname
            ]);
            $log_r1 = true;
            if ($money != 0) {
                $ymoney = 0;
                $nmoney = $asset->balance;
                //写入用户资金日志
                $log_r1 = UserMoneyLog::create([
                    'uid'      => $asset->uid,
                    'order_id' => $order_id,
                    'pid'      => $pid,
                    'pname'    => $pname,
                    'ptype'    => $ptype,
                    'ymoney'   => $ymoney,
                    'money'    => $money,
                    'nmoney'   => $nmoney,
                    'type'     => $type,
                    'mark'     => $mark,
                    'en_mark'  => $en_mark,
                    'wt'       => 1,
                ]);
            }
            //增加冻结余额
            $log_r2 = true;
            if ($frost != 0) {
                $ymoney = 0;
                $nmoney = $asset->frost;
                //写入用户资金日志
                $log_r2 = UserMoneyLog::create([
                    'uid'      => $asset->uid,
                    'order_id' => $order_id,
                    'pid'      => $pid,
                    'pname'    => $pname,
                    'ptype'    => $ptype,
                    'ymoney'   => $ymoney,
                    'money'    => $money,
                    'nmoney'   => $nmoney,
                    'type'     => $type,
                    'mark'     => $mark,
                    'en_mark'  => $en_mark,
                    'wt'       => 2,
                ]);
            }
            $r1 = $asset;
            $r2 = true;
        } else {
            $r1 = $log_r1 = true;
            //增加账户余额
            if ($money != 0) {
                $ymoney = $asset->balance;
                $asset->balance = $asset->balance + $money;
                $r1 = $asset->save();
                $nmoney = $asset->balance;
                //写入用户资金日志
                $log_r1 = UserMoneyLog::create([
                    'uid'      => $asset->uid,
                    'order_id' => $order_id,
                    'pid'      => $pid,
                    'pname'    => $pname,
                    'ptype'    => $ptype,
                    'ymoney'   => $ymoney,
                    'money'    => $money,
                    'nmoney'   => $nmoney,
                    'type'     => $type,
                    'mark'     => $mark,
                    'en_mark'  => $en_mark,
                    'wt'       => 1,
                ]);

            }
            $r2 = $log_r2 = true;
            //增加冻结余额
            if ($frost != 0) {
                $ymoney = $asset->frost;
                $asset->frost = $asset->frost + $frost;
                $money = $frost;
                $r2 = $asset->save();
                $nmoney = $asset->frost;
                //写入用户资金日志
                $log_r2 = UserMoneyLog::create([
                    'uid'      => $asset->uid,
                    'order_id' => $order_id,
                    'pid'      => $pid,
                    'pname'    => $pname,
                    'ptype'    => 1,
                    'ymoney'   => $ymoney,
                    'money'    => $money,
                    'nmoney'   => $nmoney,
                    'type'     => $type,
                    'mark'     => $mark,
                    'en_mark'  => $en_mark,
                    'wt'       => 2,
                ]);
            }
        }
        if (!$log_r1 || !$log_r2)
            return false;
        if (!$r1 || !$r2)
            return false;
        return true;
    }

}