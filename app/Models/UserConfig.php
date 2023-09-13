<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\UserConfig
 *
 * @property int $id
 * @property int $uid
 * @property string|null $google_secret 谷歌密钥
 * @property int $google_verify 谷歌验证是否开启 0为否 1为是
 * @property int $google_bind 是否绑定谷歌验证码 0为否 1为是
 * @property int $sms_verify 短信验证码是否开启 0为否 1为是
 * @property int $payment_password_set 资金密码是否被设置，0为否 1为是
 * @property int $bank_set 0未设置 1已设置
 * @property int $phone_bind 是否绑定了手机 0为否1为是
 * @property string|null $phone_verify_at
 * @property int $email_bind 是否绑定了邮箱 0为否1为是
 * @property string|null $email_verify_at
 * @property int|null $security_level 0无等级 1初级 2中级 3高级
 * @property int $shoushi_set 手势密码设置
 * @property int $shoushi_open 手势密码是否开启
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserConfig newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserConfig newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserConfig query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserConfig whereBankSet($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserConfig whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserConfig whereEmailBind($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserConfig whereEmailVerifyAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserConfig whereGoogleBind($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserConfig whereGoogleSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserConfig whereGoogleVerify($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserConfig whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserConfig wherePaymentPasswordSet($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserConfig wherePhoneBind($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserConfig wherePhoneVerifyAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserConfig whereSecurityLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserConfig whereShoushiOpen($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserConfig whereShoushiSet($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserConfig whereSmsVerify($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserConfig whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserConfig whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class UserConfig extends Model
{
    protected $table = 'user_config';
    protected $guarded = ['id'];

//    protected $hidden = [
//         'id','uid','google_secret',
//    ];
    protected $hidden = [
         'id','uid','google_secret',
    ];
}
