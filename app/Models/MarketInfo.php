<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\MarketInfo
 *
 * @property int $id
 * @property int $pid pid
 * @property string $code code
 * @property string $pname pname
 * @property float $open_price 开盘价
 * @property float $close_price 收盘价
 * @property float $price 当前价格
 * @property int $number 涨的次数
 * @property float $ero_volume 涨跌幅量
 * @property int $status 1 已涨 2待涨
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MarketInfo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MarketInfo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MarketInfo query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MarketInfo whereClosePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MarketInfo whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MarketInfo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MarketInfo whereEroVolume($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MarketInfo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MarketInfo whereNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MarketInfo whereOpenPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MarketInfo wherePid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MarketInfo wherePname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MarketInfo wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MarketInfo whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MarketInfo whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class MarketInfo extends Model
{
    protected $table = 'market_info';
    protected $guarded = ['id'];
}
