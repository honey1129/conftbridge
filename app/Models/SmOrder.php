<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\SmOrder
 *
 * @property int $id
 * @property int $uid 用户ID
 * @property string|null $account 用户账号
 * @property int $pid 主币种ID
 * @property string|null $pname 币种名称
 * @property float $price 成交价
 * @property float $num 成交数量
 * @property float $totalprice 成交总额
 * @property float $fee 手续费
 * @property int $status 1进行中 2已结束
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SmOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SmOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SmOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SmOrder whereAccount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SmOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SmOrder whereFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SmOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SmOrder whereNum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SmOrder wherePid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SmOrder wherePname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SmOrder wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SmOrder whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SmOrder whereTotalprice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SmOrder whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SmOrder whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class SmOrder extends Model
{
    protected $title = '';
    protected $table = 'sm_order';
    protected $guarded = ['id'];

    public function user() {
        return $this->belongsTo(User::class, 'uid','id');
    }

}
