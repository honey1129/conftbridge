<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\User;

/**
 * App\Models\Recharge
 *
 * @property int $id
 * @property int $uid 用户ID
 * @property string $ordnum 订单编号
 * @property string $wallet_address 钱包地址
 * @property string $hash 哈希
 * @property float $rmb 充值人民币金额
 * @property float $usdt 充值usdt金额
 * @property float $exchange_rate 汇率
 * @property int $status 1 未支付 2已支付
 * @property string $mark 备注说明
 * @property int $type 1 后台充值 2在线充值
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $arrival_at
 * @property int $pid
 * @property int $ptype 1资产账号 2币币 3合约 4期权 5矿池
 * @property int $cz_type 1ERC20  2TRC20
 * @property string $en_mark
 * @property int|null $atype 充值类型1余额2冻结余额
 * @property-read \App\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Recharge newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Recharge newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Recharge query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Recharge whereArrivalAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Recharge whereAtype($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Recharge whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Recharge whereCzType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Recharge whereEnMark($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Recharge whereExchangeRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Recharge whereHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Recharge whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Recharge whereMark($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Recharge whereOrdnum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Recharge wherePid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Recharge wherePtype($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Recharge whereRmb($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Recharge whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Recharge whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Recharge whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Recharge whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Recharge whereUsdt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Recharge whereWalletAddress($value)
 * @mixin \Eloquent
 */
class Recharge extends Model
{
   const SYSTEM_RECHARGE = 1;
   const ONLINE_RECHARGE = 2;
   const WALLET_RECHARGE = 3;
   const FEE_RECHARGE = 4;

   const WAIT_PAY = 1; //支付状态 未支付
   const PAYED = 2; //已支付

    protected $table = 'recharges';
    protected $guarded = ['id'];

    public function user() {
        return $this->belongsTo(User::class, 'uid', 'id');
    }

    public static function markLang($lang = 'cn', $key = '')
    {
        //中文
        $cnArr = [1 => '链上播币', 2 => '链上充值'];
        //英文
        $enArr = [1 => 'On-chain currency', 2 => 'Chain recharge'];
        if ($key == '') {
            if ($lang == 'cn') {
                return $cnArr;
            } else {
                return $enArr;
            }
        }
        if ($lang == 'cn') {
            $msg = isset($cnArr[$key]) ? $cnArr[$key] : $key;
        } else {
            $msg = isset($enArr[$key]) ? $enArr[$key] : $key;
        }
        return $msg;
    }
}
