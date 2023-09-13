<?php


namespace App\Models;


use App\User;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\UserQianBaoAddress
 *
 * @property int $id
 * @property int $uid 用户ID
 * @property string $code 币种标识
 * @property string $address 钱包地址
 * @property string|null $notes 备注描述
 * @property int $type 1 ERC20 2TRC20
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserQianBaoAddress newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserQianBaoAddress newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserQianBaoAddress query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserQianBaoAddress whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserQianBaoAddress whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserQianBaoAddress whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserQianBaoAddress whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserQianBaoAddress whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserQianBaoAddress whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserQianBaoAddress whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserQianBaoAddress whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class UserQianBaoAddress extends Model
{
    protected $table = 'user_qianbao_address';
    protected $guarded = ['id'];
    protected $fillable = [
        'from_uid',
        'from_tuisuanli',
        'from_nowtuisuanli',
        'to_uid',
        'to_tuisuanli',
        'to_nowtuisuanli',
        'status',
        'created_at',
        'updated_at',
        'connected_at',
        'disconnect_at',
        'discon_uid',
        'mark',
    ];
}
