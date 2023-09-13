<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;


class UserChat extends Model
{

    protected $title = '用户聊天';

    protected $table = 'user_chat';

    protected $guarded = ['id'];
}