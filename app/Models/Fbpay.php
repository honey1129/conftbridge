<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Fbpay
 *
 * @property int $id
 * @property int $uid 用户ID
 * @property string|null $name 姓名
 * @property string|null $bank 开户银行
 * @property string|null $branch 开户行支行
 * @property string|null $card_num 银行卡号
 * @property string|null $qrcode 支付二维码
 * @property int $type 1银行卡 2支付宝 3微信
 * @property string $mark 描述 银行卡 支付宝 微信
 * @property int $status 状态  0关闭 1启用
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbpay newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbpay newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbpay query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbpay whereBank($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbpay whereBranch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbpay whereCardNum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbpay whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbpay whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbpay whereMark($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbpay whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbpay whereQrcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbpay whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbpay whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbpay whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fbpay whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Fbpay extends Model
{
    protected $title = '法币交易支付方式';

    protected $table = 'fb_pay';
    protected $guarded = ['id'];

    public function user() {
        return $this->belongsTo(User::class, 'uid','id');
    }
}
