<?php

namespace App\Models;

use App\User;
use Extend\Wallet\EthInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\UserAddress
 *
 * @property int $id
 * @property int $uid 用户id
 * @property string|null $address 以太坊系列地址
 * @property string|null $salt 令牌
 * @property string|null $zjc 助记词
 * @property int $pid 币种ID
 * @property int $type 主网类型
 * @property int $fee_time
 * @property int $guiji_time
 * @property int $pay_query_time
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserAddress newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserAddress newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserAddress query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserAddress whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserAddress whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserAddress whereFeeTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserAddress whereGuijiTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserAddress whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserAddress wherePayQueryTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserAddress wherePid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserAddress whereSalt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserAddress whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserAddress whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserAddress whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserAddress whereZjc($value)
 * @mixin \Eloquent
 */
class UserAddress extends Model
{
    protected $guarded = ['id'];
    protected $table = 'user_address';
    public $timestamps = true;

    public function user()
    {
        return $this->belongsTo(User::class, 'uid', 'id');
    }

    public function getBalance($address,$contract_address,$code){
        $eth_obj = new EthInterface();

        $balance_arr = $eth_obj->userMoney_coin($address,$code,$contract_address);

        if ($balance_arr['status'] != 200){
            return false;
        }

        return $balance_arr['data']['balance'];

    }
}
