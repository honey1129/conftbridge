<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Authentication
 *
 * @property int $id
 * @property int $uid
 * @property string $name 姓名
 * @property string $card_id 身份证号
 * @property string|null $front_img 身份证正面照
 * @property string|null $back_img 身份证背面
 * @property string|null $handheld_img 手持身份证
 * @property int|null $status 1为待审核 2为通过 3拒绝
 * @property string|null $real_name_result 实名认证结果
 * @property string|null $refuse_reason 拒绝原因
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $checked_at
 * @property-read \App\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Authentication newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Authentication newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Authentication query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Authentication whereBackImg($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Authentication whereCardId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Authentication whereCheckedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Authentication whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Authentication whereFrontImg($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Authentication whereHandheldImg($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Authentication whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Authentication whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Authentication whereRealNameResult($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Authentication whereRefuseReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Authentication whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Authentication whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Authentication whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Authentication extends Model
{
	const PRIMARY_CHECK = 1;//初级认证
    const ADVANCED_WAIT_CHECK = 2;//高级认证待审核
    const ADVANCED_CHECK_AGREE = 3;//高级认证通过
    const ADVANCED_CHECK_REFUSE = 4;//高级认证拒绝

    protected $guarded = ['id'];
    
    public function user() {
        return $this->belongsTo(User::class, 'uid', 'id');
    }
}
