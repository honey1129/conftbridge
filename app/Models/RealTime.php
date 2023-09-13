<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


/**
 * App\Models\RealTime
 *
 * @property int $id
 * @property int|null $pid 商品id
 * @property string|null $code 商品代码
 * @property string|null $pname 商品名称
 * @property float|null $price 实时价格
 * @property float|null $volume 交易量
 * @property int|null $addtime 添加时间
 * @property string|null $ctime 当前日期
 * @property int $type 1 buy  2sell
 * @property int $b_uid
 * @property int $kongtou_reward_send 空投奖励是否发放 0为否1为是
 * @property int $reward_send 交易返佣奖励是否发放 0为否1为是
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RealTime newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RealTime newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RealTime query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RealTime whereAddtime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RealTime whereBUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RealTime whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RealTime whereCtime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RealTime whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RealTime whereKongtouRewardSend($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RealTime wherePid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RealTime wherePname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RealTime wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RealTime whereRewardSend($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RealTime whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RealTime whereVolume($value)
 * @mixin \Eloquent
 */
class RealTime extends Model {

	protected $title = '交易实时数据';
	
    protected $table = 'xy_realtime';
    protected $guarded = ['id'];


}