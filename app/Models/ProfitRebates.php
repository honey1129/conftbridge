<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ProfitRebates
 *
 * @property int $id
 * @property int $center_id 受益-运营中心
 * @property int $unit_id 受益-会员单位
 * @property int $agent_id 受益-代理商
 * @property int $staff_id 受益-员工
 * @property int $recommend_id 受益-推荐人
 * @property int $from_uid 贡献人
 * @property float $fee 该笔手续费
 * @property float $fee_yongjin 该笔产生的佣金
 * @property float $center_yongjin 运营中心佣金
 * @property float $unit_yongjin 会员单位佣金
 * @property float $agent_yongjin 代理商佣金
 * @property float $staff_yongjin 员工佣金
 * @property int $status 0为冻结状态 1为解冻
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\User $agent
 * @property-read \App\User $center
 * @property-read \App\User $from
 * @property-read \App\User $staff
 * @property-read \App\User $unit
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProfitRebates newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProfitRebates newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProfitRebates query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProfitRebates whereAgentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProfitRebates whereAgentYongjin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProfitRebates whereCenterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProfitRebates whereCenterYongjin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProfitRebates whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProfitRebates whereFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProfitRebates whereFeeYongjin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProfitRebates whereFromUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProfitRebates whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProfitRebates whereRecommendId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProfitRebates whereStaffId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProfitRebates whereStaffYongjin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProfitRebates whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProfitRebates whereUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProfitRebates whereUnitYongjin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProfitRebates whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProfitRebates extends Model
{
    protected $title = '盈亏返佣';
	
    protected $table = 'profit_rebates';
    protected $guarded = ['id'];

    public function from() {
        return $this->belongsTo(User::class, 'from_uid','id');
    }

    public function staff() {
        return $this->belongsTo(User::class, 'staff_id','id');
    }

    public function agent() {
        return $this->belongsTo(User::class, 'agent_id','id');
    }

    public function unit() {
        return $this->belongsTo(User::class, 'unit_id','id');
    }
    
    public function center() {
        return $this->belongsTo(User::class, 'center_id','id');
    }
}
