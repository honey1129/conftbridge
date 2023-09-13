<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\EmailLog
 *
 * @property int $id
 * @property string $email 邮箱
 * @property string|null $code
 * @property string $ip
 * @property int $used 0未使用  1已用
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EmailLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EmailLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EmailLog query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EmailLog whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EmailLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EmailLog whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EmailLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EmailLog whereIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EmailLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EmailLog whereUsed($value)
 * @mixin \Eloquent
 */
class EmailLog extends Model
{
    const VERIFY_CODE = 1; //验证码


}
