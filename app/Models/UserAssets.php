<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\User;

/**
 * App\Models\UserAssets
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
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserAssets newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserAssets newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserAssets query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserAssets whereBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserAssets whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserAssets whereDealMoney($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserAssets whereFrost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserAssets whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserAssets wherePid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserAssets wherePname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserAssets wherePtype($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserAssets whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserAssets whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class UserAssets extends Model {

	protected $title = '用户资产';
	
    protected $table = 'user_assets';
    protected $guarded = ['id'];

    public function user() {
        return $this->belongsTo(User::class, 'uid','id');
    }

    public static function getBalance($uid,$pid,$ptype=1,$lock = false)
    {
        $assets_info =  self::where(['uid'=>$uid,'pid'=>$pid,'ptype'=>$ptype])
            ->first();
        if(empty($assets_info))
        {
             $pname = Products::where('pid',$pid)->value('pname');
             $code =  explode('_', $pname);;
             UserAssets::create(
                 [
                     'uid' => $uid,
                     'pid' => $pid,
                     'pname' => $code[0],
                     'ptype' => $ptype,
                     'created_at' => now(),
                     'updated_at' => now(),
                 ]
             );
        }
        if ($lock)
        {
            return self::where(['uid'=>$uid,'pid'=>$pid,'ptype'=>$ptype])
                ->lockForUpdate()
                ->first();
        } else {
            return self::where(['uid'=>$uid,'pid'=>$pid,'ptype'=>$ptype])
                ->first();
        }
    }
}