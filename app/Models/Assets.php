<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\User;

/**
 * App\Models\Assets
 *
 * @property int $id
 * @property int $uid 用户id
 * @property float $balance 余额
 * @property float $frost 冻结
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $pid pid
 * @property int $ptype 1资产账号 2币币 3合约 4期权 5矿池
 * @property string $pname
 * @property float $deal_money 交易金额
 * @property-read \App\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Assets newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Assets newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Assets query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Assets whereBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Assets whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Assets whereDealMoney($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Assets whereFrost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Assets whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Assets wherePid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Assets wherePname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Assets wherePtype($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Assets whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Assets whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Assets extends Model
{

    protected $title = '用户资产';

    protected $table = 'user_assets';
    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class, 'uid', 'id');
    }

}