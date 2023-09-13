<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSpeedCard extends Model
{
    protected $title = '用户加速卡';

    protected $table = 'user_speed_card';
    protected $guarded = ['id'];
}