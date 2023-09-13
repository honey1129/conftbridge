<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Fbbuying
 *
 * @property int $id
 * @property int $uid
 * @property string|null $order_no 订单编号
 * @property float $trans_num 交易数量
 * @property float $deals_num 成交数量
 * @property float $price 单价
 * @property float $totalprice 总价
 * @property float $sxfee 手续费
 * @property float $min_price 最小限额
 * @property float $max_price 最大限额
 * @property int $status 1 进行中 2完成 3撤单
 * @property string|null $notes 卖家备注
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property string|null $cancel_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbbuying newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbbuying newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbbuying query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbbuying whereCancelAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbbuying whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbbuying whereDealsNum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbbuying whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbbuying whereMaxPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbbuying whereMinPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbbuying whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbbuying whereOrderNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbbuying wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbbuying whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbbuying whereSxfee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbbuying whereTotalprice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbbuying whereTransNum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbbuying whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbbuying whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Fbbuying extends Model
{
    protected $title = '法币交易求购';

    protected $table = 'fb_buying';
    protected $guarded = ['id'];

    public function user() {
        return $this->belongsTo(User::class, 'uid','id')->select(['id','avatar','account','phone','email']);
    }
}
