<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\UserWithdraw
 *
 * @property int $id
 * @property int $type 1 ERC20   2TRC20
 * @property int $uid 会员ID
 * @property string $with_num 提现编号
 * @property string|null $address 提币地址
 * @property float $money 提现金额
 * @property float $handling_fee 手续费
 * @property float $last_money 剩余金额
 * @property float $actual 实际到账
 * @property int $status 1 待审核 2到账中 3已拒绝 4已到账 5失败
 * @property string|null $mark 提现备注
 * @property string|null $refuse_reason 拒绝原因
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $checked_at
 * @property int $pid
 * @property string $tx_id
 * @property string $en_mark
 * @property string $en_reason
 * @property-read \App\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWithdraw newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWithdraw newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWithdraw query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWithdraw whereActual($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWithdraw whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWithdraw whereCheckedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWithdraw whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWithdraw whereEnMark($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWithdraw whereEnReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWithdraw whereHandlingFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWithdraw whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWithdraw whereLastMoney($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWithdraw whereMark($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWithdraw whereMoney($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWithdraw wherePid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWithdraw whereRefuseReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWithdraw whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWithdraw whereTxId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWithdraw whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWithdraw whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWithdraw whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWithdraw whereWithNum($value)
 * @mixin \Eloquent
 */
class UserWithdraw extends Model
{
    protected $title = '用户提现';

    const WAIT_CHECK   = 1;//未到账
    const ARRIVING     = 2;//到帐中
    const CHECK_REFUSE = 3;//拒绝
    const CHECK_AGREE  = 4;//同意
    const CHECK_ERROR  = 5;//失败

    protected $guarded = ['id'];
    protected $table   = 'user_withdraw';

    public function user()
    {
        return $this->belongsTo(User::class, 'uid', 'id');
    }

    public function chain_networks()
    {
        return self::hasOne(ChainNetwork::class, 'id', 'type');
    }

    //订单备注文字
    public static function markLang($lang = 'cn', $key = '')
    {
        //中文
        $cnArr = [1 => '用户提币'];
        //英文
        $enArr = [1 => 'User Withdrawal'];
        if ($key == '') {
            if ($lang == 'cn') {
                return $cnArr;
            } else {
                return $enArr;
            }
        }
        if ($lang == 'cn') {
            $msg = isset($cnArr[$key]) ? $cnArr[$key] : $key;
        } else {
            $msg = isset($enArr[$key]) ? $enArr[$key] : $key;
        }
        return $msg;
    }

}
