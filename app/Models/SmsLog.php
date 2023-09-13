<?php

namespace App\Models;

    use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\SmsLog
 *
 * @property int $id
 * @property string|null $area_code 区域代码
 * @property string $phone 手机号
 * @property string|null $code
 * @property string $content
 * @property string $ip
 * @property string|null $result
 * @property int $used 0未使用  1已用
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SmsLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SmsLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SmsLog query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SmsLog whereAreaCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SmsLog whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SmsLog whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SmsLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SmsLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SmsLog whereIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SmsLog wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SmsLog whereResult($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SmsLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SmsLog whereUsed($value)
 * @mixin \Eloquent
 */
class SmsLog extends Model
{
    //1：用户注册 2：密码重置 3：身份验证 4：变更重要信息
    const VERIFY_CODE = 1; //验证码
    const RESET_PASSWORD = 2;//密码重置
    const AUTHENTICATION_CODE = 3;//身份验证
    const CHANGE_USERINFO_CODE = 4;//变更重要信息
    const SELL_USERINFO_CODE = 5;//法币出售信息
    const PMMA_USERINFO_CODE = 6;//法币求购信息
    const WITHDRAW_CODE = 7;//用户提币

}
