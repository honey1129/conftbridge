<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\UserTrans
 *
 * @property int $id
 * @property string $tran_num 交易编号
 * @property int $position_id 持仓id
 * @property int $uid 用户id
 * @property string $name 产品名称
 * @property string $code 产品名称
 * @property float $buyprice 买入价格
 * @property int $sheets 购买张数
 * @property float $buynum 买入数量
 * @property float $totalprice 总计金额
 * @property int|null $from 1市价 2限价委托
 * @property int $otype 1涨 2跌
 * @property float $stopwin 产品止盈
 * @property float $stoploss 产品止损
 * @property float $sellprice 平仓价格
 * @property float $profit 盈亏金额
 * @property float $fee 交易手续费
 * @property float $dayfee 过夜费
 * @property int $pc_type 平仓类型 1手动平仓 2止盈平仓 3止损平仓 4爆仓
 * @property int $leverage 杠杆
 * @property int $distribute_income 是否发放了收益 0为否1为是
 * @property string|null $jiancang_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserTrans newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserTrans newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserTrans query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserTrans whereBuynum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserTrans whereBuyprice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserTrans whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserTrans whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserTrans whereDayfee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserTrans whereDistributeIncome($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserTrans whereFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserTrans whereFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserTrans whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserTrans whereJiancangAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserTrans whereLeverage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserTrans whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserTrans whereOtype($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserTrans wherePcType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserTrans wherePositionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserTrans whereProfit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserTrans whereSellprice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserTrans whereSheets($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserTrans whereStoploss($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserTrans whereStopwin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserTrans whereTotalprice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserTrans whereTranNum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserTrans whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserTrans whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class UserTrans extends Model
{
    protected $title = '会员平仓单';
	
    protected $table = 'user_trans';
    protected $guarded = ['id'];

    public function user() {
        return $this->belongsTo(User::class, 'uid','id');
    }
}
