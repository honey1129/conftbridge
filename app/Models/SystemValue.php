<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\User;

/**
 * App\Models\SystemValue
 *
 * @property int $id
 * @property string $name 变量名
 * @property string $value 变量值
 * @property string $locale
 * @property string $mark 备注
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SystemValue newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SystemValue newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SystemValue query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SystemValue whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SystemValue whereLocale($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SystemValue whereMark($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SystemValue whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SystemValue whereValue($value)
 * @mixin \Eloquent
 */
class SystemValue extends Model
{

    protected $title = '系统变量';

    protected $table = 'system_value';
    protected $guarded = ['id'];



}