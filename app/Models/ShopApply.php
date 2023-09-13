<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ShopApply
 *
 * @property int $id
 * @property int $uid 用户id
 * @property float $money 申请成为商家金额
 * @property string|null $shop_img 商家资质照片
 * @property int $action 1申请商家 2撤销商家
 * @property int $status 1提交审核 2同意 3拒绝 4撤销审核 5同意 6拒绝
 * @property string $remark 拒绝原因
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ShopApply newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ShopApply newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ShopApply query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ShopApply whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ShopApply whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ShopApply whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ShopApply whereMoney($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ShopApply whereRemark($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ShopApply whereShopImg($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ShopApply whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ShopApply whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ShopApply whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ShopApply extends Model
{
    protected $title = '商家管理';

    const SHOP_APPLY_CHECK = 1;//申请商家待审核
    const SHOP_APPLY_AGREE = 2;//申请商家同意
    const SHOP_APPLY_REFUSE = 3;//申请商家拒绝
    const SHOP_CANCEL_CHECK = 4;//取消商家待审核
    const SHOP_CANCEL_AGREE = 5;//取消商家同意
    const SHOP_CANCEL_REFUSE = 6;//取消商家拒绝

    const SHOP_ACTION = 1;//申请商家
    const SHOP_ACTION_CANCEL = 2;//取消商家


    protected $table = 'shop_apply';
    protected $guarded = ['id'];

    public function user() {
        return $this->belongsTo(User::class, 'uid','id');
    }
}
