<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CfaPoolOrder extends Model
{
    use SoftDeletes;
    protected $title = 'CFA合成池订单';
    public $timestamps = true;
    protected $table = 'cfa_pool_order';
    protected $guarded = ['id'];
}