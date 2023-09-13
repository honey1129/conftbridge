<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\UserPositions
 *
 * @property int $id
 * @property string $hold_num 交易编号
 * @property int $uid 用户id
 * @property string $name 币种名称展示用
 * @property string $code 币种CODE 查询用
 * @property float $buyprice 买入价格
 * @property int $sheets 购买张数
 * @property float $buynum 买入数量
 * @property float $market_price 委托时的 市价
 * @property float $totalprice 总计金额
 * @property int $from 1市价 2限价委托
 * @property int $otype 买入方向1涨 2跌
 * @property float $stopwin 产品止赢
 * @property float $stoploss 产品止损
 * @property float $fee 交易手续费
 * @property float $dayfee 过夜费
 * @property int $leverage 杠杆倍数
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property float $spread 点差
 * @property-read \App\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserPositions newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserPositions newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserPositions query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserPositions whereBuynum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserPositions whereBuyprice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserPositions whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserPositions whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserPositions whereDayfee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserPositions whereFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserPositions whereFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserPositions whereHoldNum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserPositions whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserPositions whereLeverage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserPositions whereMarketPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserPositions whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserPositions whereOtype($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserPositions whereSheets($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserPositions whereSpread($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserPositions whereStoploss($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserPositions whereStopwin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserPositions whereTotalprice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserPositions whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserPositions whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class UserPositions extends Model
{
    protected $title = '会员持仓单';
	
    protected $table = 'user_positions';
    protected $guarded = ['id'];

    public function user() {
        return $this->belongsTo(User::class, 'uid','id');
    }

    public function createSN()
    {
        return date('YmdHis') . $this->id . mt_rand(1000, 9999);
    }
}
