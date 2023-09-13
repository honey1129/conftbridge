<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Fbappeal
 *
 * @property int $id
 * @property string $order_no 订单编号
 * @property int|null $command 申述口令
 * @property int|null $oid 订单ID
 * @property int $appeal_uid 申述人编号
 * @property int $be_appeal_uid 被申述人
 * @property int $win_uid 胜诉人编号
 * @property int $type 申述类型
 * @property string|null $reason 申述原因
 * @property int $order_status 订单状态
 * @property int $status 申述状态  1进行中 2完成 3取消
 * @property string|null $pan_reason 判决备注
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property string|null $pan_at 判决时间
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbappeal newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbappeal newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbappeal query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbappeal whereAppealUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbappeal whereBeAppealUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbappeal whereCommand($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbappeal whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbappeal whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbappeal whereOid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbappeal whereOrderNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbappeal whereOrderStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbappeal wherePanAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbappeal wherePanReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbappeal whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbappeal whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbappeal whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbappeal whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbappeal whereWinUid($value)
 * @mixin \Eloquent
 */
class Fbappeal extends Model
{
    protected $title = '用户申诉';

    protected $table = 'fb_appeal';
    protected $guarded = ['id'];

}
