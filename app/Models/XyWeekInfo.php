<?php

namespace App\Models;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\XyWeekInfo
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
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\XyWeekInfo disableCache()
 * @method static \GeneaLabs\LaravelModelCaching\CachedBuilder|\App\Models\XyWeekInfo newModelQuery()
 * @method static \GeneaLabs\LaravelModelCaching\CachedBuilder|\App\Models\XyWeekInfo newQuery()
 * @method static \GeneaLabs\LaravelModelCaching\CachedBuilder|\App\Models\XyWeekInfo query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\XyWeekInfo whereClosingPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\XyWeekInfo whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\XyWeekInfo whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\XyWeekInfo whereDateTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\XyWeekInfo whereHighestPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\XyWeekInfo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\XyWeekInfo whereLowestPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\XyWeekInfo whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\XyWeekInfo whereOpeningPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\XyWeekInfo wherePid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\XyWeekInfo whereTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\XyWeekInfo whereTimestamp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\XyWeekInfo whereVolume($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\XyWeekInfo withCacheCooldownSeconds($seconds = null)
 * @mixin \Eloquent
 */
class XyWeekInfo extends Model
{
    use Cachable;
    protected $table      = 'xy_week_info';
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
