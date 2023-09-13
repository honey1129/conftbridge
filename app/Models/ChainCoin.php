<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ChainCoin
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChainCoin newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChainCoin newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChainCoin query()
 * @mixin \Eloquent
 * @property int $id
 * @property int $pid 币种ID
 * @property int $chain_id 网络类型
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string $contract 合约地址
 * @property int $status 状态
 * @property int $network_id 主网ID
 * @property float $withdraw_min 最少提币数量
 * @property float $withdraw_max 最多提币数量
 * @property float $withdraw_fee 提币手续费
 * @property int $is_pay 是否允许充值
 * @property int $is_withdraw 是否允许提现
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChainCoin whereChainId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChainCoin whereContract($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChainCoin whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChainCoin whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChainCoin whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChainCoin whereIsPay($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChainCoin whereIsWithdraw($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChainCoin whereNetworkId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChainCoin wherePid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChainCoin whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChainCoin whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChainCoin whereWithdrawFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChainCoin whereWithdrawMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChainCoin whereWithdrawMin($value)
 */
class ChainCoin extends Model
{
    //
    protected $table = 'chain_coin';

    /**
     * @param $chain_type
     * @param $token
     * @return self
     */
    public static function getInfo($network_id, $token)
    {
        return self::where('network_id', $network_id)->where('contract', $token)->first();
    }
}
