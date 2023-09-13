<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\UserPosition
 *
 * @property int $id
 * @property int $uid 用户id
 * @property int $pid 上级id
 * @property int $lay 本人在上级的层数
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserPosition newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserPosition newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserPosition query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserPosition whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserPosition whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserPosition whereLay($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserPosition wherePid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserPosition whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserPosition whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class UserPosition extends Model
{
    protected $title = '用户关系表';
	
    protected $table = 'user_position';
    protected $guarded = ['id'];

}
