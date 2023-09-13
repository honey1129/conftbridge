<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\QiqOrder
 *
 * @property int $id
 * @property int $uid 用户ID
 * @property string|null $account 用户账号
 * @property int $pid 主币种ID
 * @property string|null $pname 币种名称
 * @property string|null $wtprice 委托价
 * @property float $buynum
 * @property float|null $cjprice 成交价
 * @property float|null $totalprice 成交总额
 * @property float $fee 手续费
 * @property int $type 1涨 2跌
 * @property int $status 1交易中,2成交，3撤销
 * @property float|null $earnprice 收益金额
 * @property int $cycle 秒数
 * @property string $endtime 到期时间
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $is_robot
 * @property-read \App\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\QiqOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\QiqOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\QiqOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\QiqOrder whereAccount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\QiqOrder whereBuynum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\QiqOrder whereCjprice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\QiqOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\QiqOrder whereCycle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\QiqOrder whereEarnprice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\QiqOrder whereEndtime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\QiqOrder whereFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\QiqOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\QiqOrder whereIsRobot($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\QiqOrder wherePid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\QiqOrder wherePname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\QiqOrder whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\QiqOrder whereTotalprice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\QiqOrder whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\QiqOrder whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\QiqOrder whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\QiqOrder whereWtprice($value)
 * @mixin \Eloquent
 */
class QiqOrder extends Model
{
    protected $title = '';
    protected $table = 'qiq_order';
    protected $guarded = ['id'];

    public function user() {
        return $this->belongsTo(User::class, 'uid','id');
    }

}
