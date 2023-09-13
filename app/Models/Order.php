<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\WriteUserMoneyLog;
use App\User;

/**
 * App\Models\Order
 *
 * @property int $orders_id
 * @property int $member_id 用户ID
 * @property string|null $account 用户账号
 * @property int $currency_id 主币种ID
 * @property string|null $pname 币种名称
 * @property string|null $wtprice 委托价
 * @property float|null $wtprice1 委托价
 * @property float|null $cjprice 成交价
 * @property float|null $wtnum 委托数量
 * @property float|null $cjnum 成交数量
 * @property float|null $totalprice 成交总额
 * @property float $fee 手续费
 * @property int $type 1 买入 2卖出
 * @property int $add_time 添加时间
 * @property int|null $trade_time 成交时间
 * @property int $status 0 待交易，1交易中,2成交，-1撤销
 * @property int|null $otype 1限价 2市价
 * @property string|null $tpath 推荐关系树
 * @property string|null $l_code
 * @property string|null $r_code
 * @property int $reward_send 交易返佣奖励是否发放 0为否1为是
 * @property int $is_first 是否主流币 1是 2否
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\OrderExt[] $orderExt
 * @property-read int|null $order_ext_count
 * @property-read \App\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereAccount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereAddTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereCjnum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereCjprice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereIsFirst($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereLCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereMemberId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereOrdersId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereOtype($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order wherePname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereRCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereRewardSend($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereTotalprice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereTpath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereTradeTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereWtnum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereWtprice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereWtprice1($value)
 * @mixin \Eloquent
 */
class Order extends Model
{
    use WriteUserMoneyLog;

    protected $title = '币币交易';

    const WAIT_TRANS = 0;//待交易
    const TRANS_ING  = 1;//交易中
    const TRANS_OVER = 2;//成交
    const TRANS_REV  = -1;//撤销

    const TYPE_LIMIT = 1; //市价
    const TYPE_MARK = 2; //限价

    const BUY = 1; //买
    const SELL = 2; //卖
    
    protected $primaryKey = 'orders_id';
    protected $guarded = ['orders_id'];
    protected $table = 'xy_orders';


    public function _assetAct($user, $asset_id, $asset_code, $buynum, $frost, $memo,$en_memo,$type,$ptype=2)
    {
        //调用资产财务方法
        return $this->writeLog($user->id,0,$asset_id,strtoupper($asset_code),$buynum,$frost,$type,$memo,$en_memo,$ptype);
    }

    public function user() {
        return $this->belongsTo(User::class, 'member_id', 'id');
    }
    public function orderExt()
    {
        return $this->hasMany(OrderExt::class,'o_oid','orders_id');
    }
}
