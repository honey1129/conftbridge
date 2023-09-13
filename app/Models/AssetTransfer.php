<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\AssetTransfer
 *
 * @property int $id
 * @property string $ordnum 订单编号
 * @property int $uid 用户ID
 * @property float $price 充值金额
 * @property string $memo 备注说明
 * @property int $ptype
 * @property int $type 11资金账户到币币12资金账户到合约13资金账户到期权21币币到资金22币币到合约23币币到期权31合约到资金32合约到币币33合约到期权41期权到资金42期权到币币43期权到合约
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $pid
 * @property string $en_memo
 * @property-read \App\User|null $toUser
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetTransfer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetTransfer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetTransfer query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetTransfer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetTransfer whereEnMemo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetTransfer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetTransfer whereMemo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetTransfer whereOrdnum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetTransfer wherePid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetTransfer wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetTransfer wherePtype($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetTransfer whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetTransfer whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetTransfer whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class AssetTransfer extends Model
{
    protected $title = '会员转账';

    protected $table   = 'asset_transfer';
    protected $guarded = ['id'];

    public function toUser()
    {
        return $this->hasOne(User::class, 'id', 'other_uid');
    }
    //账户类型 lang cn中文 en英文
    public static function assetTypeLang($lang = 'cn', $key = '')
    {
        //中文
        $cnArr = [
            1 => '资金账户',
            2 => '币币账户',
            3 => '合约账户',
            4 => '期权账户',
            5 => '矿池账户',
        ];
        //英文
        $enArr = [
            1 => 'Fund account',
            2 => 'Currency account',
            3 => 'Contractual account',
            4 => 'Option account',
            5 => 'Ore pool account',
        ];
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
