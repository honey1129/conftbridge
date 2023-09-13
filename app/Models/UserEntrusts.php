<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\UserEntrusts
 *
 * @property int $id
 * @property string $en_num 委托编号
 * @property int $uid 用户id
 * @property string $name 币种名称展示用
 * @property string $code 币种CODE查询用
 * @property float $buyprice 买入价格
 * @property int $sheets 购买张数
 * @property float $buynum 买入数量
 * @property float $totalprice 总计金额
 * @property int $from 1市价 2限价委托
 * @property int $otype 买入方向 1涨 2跌
 * @property float $stopwin 止盈
 * @property float $stoploss 止损
 * @property float $fee 交易手续费
 * @property int $status 1 委托中 2已完成 3已取消
 * @property float $market_price 委托时的 市价
 * @property float $spread 点差
 * @property int $leverage 杠杆倍数
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $handle_at
 * @property-read \App\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserEntrusts newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserEntrusts newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserEntrusts query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserEntrusts whereBuynum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserEntrusts whereBuyprice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserEntrusts whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserEntrusts whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserEntrusts whereEnNum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserEntrusts whereFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserEntrusts whereFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserEntrusts whereHandleAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserEntrusts whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserEntrusts whereLeverage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserEntrusts whereMarketPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserEntrusts whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserEntrusts whereOtype($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserEntrusts whereSheets($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserEntrusts whereSpread($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserEntrusts whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserEntrusts whereStoploss($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserEntrusts whereStopwin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserEntrusts whereTotalprice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserEntrusts whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserEntrusts whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class UserEntrusts extends Model
{
    protected $title = '会员委托单';
	
    protected $table = 'user_entrusts';
    protected $guarded = ['id'];

    // 1 委托中 2已完成 3已取消
    const STATE_ING  = 1;
    const STATE_OVER = 2;
    const STATE_REV  = 3;

    public function user() {
        return $this->belongsTo(User::class, 'uid','id');
    }

    public function createSN()
    {
        return 'ENNUM' . date('YmdHis') . $this->id . mt_rand(1000, 9999);
    }
}
