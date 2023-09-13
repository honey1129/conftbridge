<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


/**
 * App\Models\FiveMinInfo
 *
 * @property int $id
 * @property int|null $pid 商品ID
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
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FiveMinInfo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FiveMinInfo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FiveMinInfo query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FiveMinInfo whereClosingPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FiveMinInfo whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FiveMinInfo whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FiveMinInfo whereDateTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FiveMinInfo whereHighestPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FiveMinInfo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FiveMinInfo whereLowestPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FiveMinInfo whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FiveMinInfo whereOpeningPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FiveMinInfo wherePid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FiveMinInfo whereTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FiveMinInfo whereTimestamp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FiveMinInfo whereVolume($value)
 * @mixin \Eloquent
 */
class FiveMinInfo extends Model {

	protected $title = '5分钟数据';
    public $timestamps = false;
    protected $table = 'xy_5min_info';
    protected $guarded = ['id'];


}