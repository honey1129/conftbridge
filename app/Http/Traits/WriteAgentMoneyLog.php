<?php

namespace App\Http\Traits;

use App\Models\AgentMoneyLog;

trait WriteAgentMoneyLog
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
    public function writeAgentBalanceLog($asset, $order_id, $ptype, $money, $type, $mark)
    {
        //增加可用余额
        $ymoney         = $asset->balance;
        $asset->balance = $asset->balance + $money;
        $bool1          = $asset->save();
        $nmoney         = $asset->balance;
        if ($bool1) {
            //写入用户资金日志
            $bool2 = AgentMoneyLog::create([
                'uid'      => $asset->uid,
                'order_id' => $order_id,
                'ptype'    => $ptype,
                'ymoney'   => $ymoney,
                'money'    => $money,
                'nmoney'   => $nmoney,
                'type'     => $type,
                'mark'     => $mark,
                'wt'       => 1,
            ]);
        }

        return ($bool1 && $bool2) ? true : false;

    }

    public function writeAgentFrostLog($asset, $order_id, $ptype, $frost, $type, $mark)
    {
        //增加冻结余额
        $ymoney       = $asset->frost;
        $asset->frost = $asset->frost + $frost;
        $bool1        = $asset->save();
        $nmoney       = $asset->frost;
        if ($bool1) {
            //写入用户资金日志
            $bool2 = AgentMoneyLog::create([
                'uid'      => $asset->uid,
                'order_id' => $order_id,
                'ptype'    => $ptype,
                'ymoney'   => $ymoney,
                'money'    => $frost,
                'nmoney'   => $nmoney,
                'type'     => $type,
                'mark'     => $mark,
                'wt'       => 2,
            ]);
        }

        return ($bool1 && $bool2) ? true : false;

    }
}
