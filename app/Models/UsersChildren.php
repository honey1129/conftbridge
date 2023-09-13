<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\UsersChildren
 *
 * @property int $id
 * @property int $master_uid 主账户id
 * @property int $sub_uid 子账号ID
 * @property string $sub_account 子账户邮箱或手机号
 * @property string $password 加密密码
 * @property int $save_days 保存天数
 * @property \Illuminate\Support\Carbon|null $created_at 关联日期
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UsersChildren newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UsersChildren newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UsersChildren query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UsersChildren whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UsersChildren whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UsersChildren whereMasterUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UsersChildren wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UsersChildren whereSaveDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UsersChildren whereSubAccount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UsersChildren whereSubUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UsersChildren whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class UsersChildren extends Model
{
    protected $table = 'users_children';
    protected $fillable = [
        'master_uid', 'sub_uid', 'sub_account', 'password', 'save_days', 'created_at'
    ];
}
