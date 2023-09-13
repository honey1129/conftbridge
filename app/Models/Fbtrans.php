<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Fbtrans
 *
 * @property-read \App\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbtrans newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbtrans newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbtrans query()
 * @mixin \Eloquent
 */
class Fbtrans extends Model
{
    protected $title = '法币交易订单';

    const ORDER_PENDING   = 1;
    const ORDER_PAID = 2;
    const ORDER_OVER = 3;
    const ORDER_APPEAL = 4;
    const ORDER_CANCEL = 5;

    protected $table = 'fb_trans';
    protected $guarded = ['id'];

    public function user() {
        return $this->belongsTo(User::class, 'uid','id');
    }
}
