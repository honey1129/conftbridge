<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserPoolOrder extends Model
{
    use SoftDeletes;
    protected $title = '用户参与池子订单';
    public $timestamps = true;
    protected $table = 'user_pool_order';
    protected $guarded = ['id'];

}