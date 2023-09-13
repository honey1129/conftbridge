<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\AssetRelease
 *
 * @property int $id
 * @property int $uid 用户ID
 * @property string $order_no 订单编号
 * @property int $order_type 1矿池释放 2合约收益 3期权收益
 * @property float $money 释放金额
 * @property int $status 0 未签到，1签到
 * @property string $memo 说明
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $pid
 * @property int $oid
 * @property string $en_memo
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetRelease newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetRelease newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetRelease query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetRelease whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetRelease whereEnMemo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetRelease whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetRelease whereMemo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetRelease whereMoney($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetRelease whereOid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetRelease whereOrderNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetRelease whereOrderType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetRelease wherePid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetRelease whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetRelease whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetRelease whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class AssetRelease extends Model
{
    protected $title = '奖励表';

    protected $table = 'asset_release';
    protected $guarded = ['id'];
}
