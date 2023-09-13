<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\UserBank
 *
 * @property int $id
 * @property int $uid 用户ID
 * @property string|null $name 姓名
 * @property string|null $bank 开户银行
 * @property string|null $branch 开户行支行
 * @property string|null $card_num 银行卡号
 * @property string|null $mark 客户备注
 * @property int $status 状态  0关闭 1启用
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserBank newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserBank newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserBank query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserBank whereBank($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserBank whereBranch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserBank whereCardNum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserBank whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserBank whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserBank whereMark($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserBank whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserBank whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserBank whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserBank whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class UserBank extends Model
{
    protected $title = '用户银行卡';

    protected $table = 'user_bank';
    protected $guarded = ['id'];

    public function user() {
        return $this->belongsTo(User::class, 'uid','id');
    }
}
