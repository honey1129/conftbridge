<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\FeeRebates
 *
 * @property int $id
 * @property int $recommend_id 收益人 贡献接受人
 * @property int $from_uid 贡献人
 * @property float $recommend_yongjin 佣金
 * @property float $fee 该笔手续费
 * @property string $memo 类型描述
 * @property int $type 1邀请返佣 2为自身返佣
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\User $from
 * @property-read \App\User $recommend
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeeRebates newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeeRebates newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeeRebates query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeeRebates whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeeRebates whereFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeeRebates whereFromUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeeRebates whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeeRebates whereMemo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeeRebates whereRecommendId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeeRebates whereRecommendYongjin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeeRebates whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeeRebates whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FeeRebates extends Model
{
    protected $title = '手续费返佣';

    const FEE_RECOMMEND   = 1;
    const FEE_SELF   = 2;

    protected $table = 'fee_rebates';
    protected $guarded = ['id'];

    public function from() {
        return $this->belongsTo(User::class, 'from_uid','id')->select(['id','account','phone','email','name']);
    }

    public function recommend() {
        return $this->belongsTo(User::class, 'recommend_id','id');
    }

}
