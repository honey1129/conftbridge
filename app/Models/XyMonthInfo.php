<?php

namespace App\Models;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\XyMonthInfo
 *
 * @property int $id
 * @property int|null $pid 商品iD
 * @property string|null $code 股票id
 * @property string|null $name 股票类型
 * @property string|null $openingPrice 开盘价
 * @property string|null $closingPrice 收盘价
 * @property string|null $highestPrice 最高价
 * @property string|null $lowestPrice 最低价
 * @property string|null $volume 成交量
 * @property string|null $date 日期
 * @property string|null $time 时间
 * @property string|null $dateTime 日期时间
 * @property int|null $timestamp 时间戳
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\XyMonthInfo disableCache()
 * @method static \GeneaLabs\LaravelModelCaching\CachedBuilder|\App\Models\XyMonthInfo newModelQuery()
 * @method static \GeneaLabs\LaravelModelCaching\CachedBuilder|\App\Models\XyMonthInfo newQuery()
 * @method static \GeneaLabs\LaravelModelCaching\CachedBuilder|\App\Models\XyMonthInfo query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\XyMonthInfo whereClosingPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\XyMonthInfo whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\XyMonthInfo whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\XyMonthInfo whereDateTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\XyMonthInfo whereHighestPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\XyMonthInfo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\XyMonthInfo whereLowestPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\XyMonthInfo whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\XyMonthInfo whereOpeningPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\XyMonthInfo wherePid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\XyMonthInfo whereTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\XyMonthInfo whereTimestamp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\XyMonthInfo whereVolume($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\XyMonthInfo withCacheCooldownSeconds($seconds = null)
 * @mixin \Eloquent
 */
class XyMonthInfo extends Model
{
    use Cachable;
    protected $table      = 'xy_month_info';
    public    $timestamps = false;
    protected $fillable   = [
        'pid',
        'code',
        'name',
        'openingPrice',
        'highestPrice',
        'closingPrice',
        'lowestPrice',
        'volume',
        'date',
        'time',
        'dateTime',
        'timestamp',
    ];
}
